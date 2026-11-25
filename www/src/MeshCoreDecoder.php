<?php

namespace MeshCore;

/**
 * Class MeshCoreDecoder
 *
 * Dekoduoja neapdorotus (RAW) MeshCore LoRa paketus ir pagal poreikį
 * dešifruoja jų krovinius (payload) naudojant Curve25519 bendrąjį raktą (shared_secret_hex).
 */
class MeshCoreDecoder
{
    // Maršrutizavimo tipai (Header bitai 0-1)
    public const ROUTE_TYPE_TRANSPORT_FLOOD  = 0x00;
    public const ROUTE_TYPE_FLOOD            = 0x01;
    public const ROUTE_TYPE_DIRECT           = 0x02;
    public const ROUTE_TYPE_TRANSPORT_DIRECT = 0x03;

    // Krovinio tipai (Header bitai 2-5)
    public const PAYLOAD_TYPE_REQ            = 0x00; // Užklausa
    public const PAYLOAD_TYPE_RESPONSE       = 0x01; // Atsakas į užklausą
    public const PAYLOAD_TYPE_TXT_MSG        = 0x02; // Tekstinis pranešimas
    public const PAYLOAD_TYPE_ACK            = 0x03; // Patvirtinimas
    public const PAYLOAD_TYPE_ADVERT         = 0x04; // Mazgo tapatybės reklama / skelbimas
    public const PAYLOAD_TYPE_ANON_REQ       = 0x07; // Anoniminė užklausa / Login
    public const PAYLOAD_TYPE_PATH           = 0x08; // Kelias (Path)
    public const PAYLOAD_TYPE_CONTROL        = 0x0B; // Valdymo paketas

    // Užklausų tipai (Request types)
    public const REQ_TYPE_GET_STATUS         = 0x01;
    public const REQ_TYPE_KEEP_ALIVE         = 0x02;
    public const REQ_TYPE_GET_TELEMETRY_DATA = 0x03;
    public const REQ_TYPE_GET_ACCESS_LIST    = 0x05;
    public const REQ_TYPE_GET_NEIGHBOURS     = 0x06;
    public const REQ_TYPE_GET_OWNER_INFO     = 0x07;

    // Atsakų kodai
    public const RESP_SERVER_LOGIN_OK        = 0x00;

    /**
     * Dekoduoja API gautą JSON užklausą (iš /api/raw, /api/adverts arba /api/telemetry).
     *
     * @param array|string $input JSON eilutė arba jau iškoduotas asociatyvus masyvas
     * @return array Iškoduoti duomenys
     */
    public function decodeApiPayload($input): array
    {
        if (is_string($input)) {
            $data = json_decode($input, true);
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'error'   => 'Neteisingas JSON formatas'
                ];
            }
        } elseif (is_array($input)) {
            $data = $input;
        } else {
            return [
                'success' => false,
                'error'   => 'Neteisingas įvesties tipas'
            ];
        }

        // Jei tai yra /api/raw paketas su raw_hex lauku:
        if (!empty($data['raw_hex'])) {
            $sharedSecret = $data['shared_secret_hex'] ?? null;
            $myPublicKey  = $data['my_public_key'] ?? null;
            $decoded = $this->decodeRawPacket($data['raw_hex'], $sharedSecret, $myPublicKey);

            // Prisegame originalią API meta informaciją (kryptį, signalą, laiko žymą ir t.t.)
            return array_merge([
                'api_metadata' => [
                    'direction'         => $data['direction'] ?? null,
                    'timestamp'         => $data['timestamp'] ?? null,
                    'length'            => $data['length'] ?? null,
                    'rssi'              => $data['rssi'] ?? null,
                    'snr'               => $data['snr'] ?? null,
                    'target_name'       => $data['target_name'] ?? null,
                    'target_public_key' => $data['target_public_key'] ?? null,
                    'sender_name'       => $data['sender_name'] ?? null,
                    'sender_public_key' => $data['sender_public_key'] ?? null,
                    'my_public_key'     => $data['my_public_key'] ?? null,
                ]
            ], $decoded);
        }

        return [
            'success' => true,
            'type'    => 'PRE_DECODED_API_DATA',
            'data'    => $data
        ];
    }

    /**
     * Pagrindinis dekodavimo metodas:
     * Priima neapdorotą šešioliktainę eilutę (raw_hex) arba binarinius baitus,
     * išanalizuoja MeshCore protokolą ir, jei pateiktas bendras raktas, dešifruoja krovinį.
     *
     * @param string $rawHexOrBytes Šešioliktainė eilutė (pvz. "1200B9...") arba binariniai baitai
     * @param string|null $sharedSecretHex 32 baitų (64 hex simbolių) ECDH bendrasis raktas
     * @param string|null $myPublicKeyHex Mūsų mazgo viešasis raktas (kad būtų galima patikrinti ar paketas skirtas mums)
     * @return array Pilnai išanalizuota paketo struktūra
     */
    public function decodeRawPacket(string $rawHexOrBytes, ?string $sharedSecretHex = null, ?string $myPublicKeyHex = null): array
    {
        $rawBytes = $this->ensureBinaryBytes($rawHexOrBytes);
        $totalLen = strlen($rawBytes);

        if ($totalLen < 2) {
            return [
                'success' => false,
                'error'   => 'Paketas per trumpas (mažiau nei 2 baitai)'
            ];
        }

        $headerByte = ord($rawBytes[0]);
        $version = ($headerByte >> 6) & 0x03;
        $payloadType = ($headerByte >> 2) & 0x0F;
        $routeType = $headerByte & 0x03;

        $payloadTypeNames = [
            self::PAYLOAD_TYPE_REQ      => 'REQ (Užklausa)',
            self::PAYLOAD_TYPE_RESPONSE => 'RESPONSE (Atsakas)',
            self::PAYLOAD_TYPE_TXT_MSG  => 'TXT_MSG (Tekstinis pranešimas)',
            self::PAYLOAD_TYPE_ACK      => 'ACK (Patvirtinimas)',
            self::PAYLOAD_TYPE_ADVERT   => 'ADVERT (Mazgo skelbimas)',
            self::PAYLOAD_TYPE_ANON_REQ => 'ANON_REQ (Anoniminė užklausa / Login)',
            self::PAYLOAD_TYPE_PATH     => 'PATH (Kelias)',
            self::PAYLOAD_TYPE_CONTROL  => 'CONTROL (Valdymas)',
        ];

        $routeTypeNames = [
            self::ROUTE_TYPE_TRANSPORT_FLOOD  => 'TRANSPORT_FLOOD',
            self::ROUTE_TYPE_FLOOD            => 'FLOOD',
            self::ROUTE_TYPE_DIRECT           => 'DIRECT',
            self::ROUTE_TYPE_TRANSPORT_DIRECT => 'TRANSPORT_DIRECT',
        ];

        $offset = 1;
        $transportCodes = null;

        // 1. Transport kodai (jei ROUTE_TYPE_TRANSPORT_FLOOD arba TRANSPORT_DIRECT)
        if ($routeType === self::ROUTE_TYPE_TRANSPORT_FLOOD || $routeType === self::ROUTE_TYPE_TRANSPORT_DIRECT) {
            if ($totalLen < $offset + 4) {
                return ['success' => false, 'error' => 'Pakete trūksta transporto kodų'];
            }
            $t1 = $this->unpackUInt16LE(substr($rawBytes, $offset, 2));
            $t2 = $this->unpackUInt16LE(substr($rawBytes, $offset + 2, 2));
            $transportCodes = [
                'code1' => sprintf('0x%04X', $t1),
                'code2' => sprintf('0x%04X', $t2),
            ];
            $offset += 4;
        }

        // 2. Kelio (Path) dekodavimas
        if ($totalLen < $offset + 1) {
            return ['success' => false, 'error' => 'Trūksta kelio ilgio baito'];
        }

        $pathLenByte = ord($rawBytes[$offset]);
        $offset += 1;

        $pathHashSize = (($pathLenByte >> 6) & 0x03) + 1; // 1..3 baitai
        $pathHopCount = $pathLenByte & 0x3F;             // 0..63 šuoliai
        $pathByteLength = $pathHopCount * $pathHashSize;

        $remaining = $totalLen - $offset;
        if ($pathHashSize > 3 || $pathByteLength > $remaining) {
            // Legacy format fallback
            $pathByteLength = min($pathLenByte, $remaining);
            $pathHopCount = $pathByteLength;
            $pathHashSize = 1;
        }

        $hopsArray = [];
        if ($pathByteLength > 0 && ($offset + $pathByteLength) <= $totalLen) {
            for ($i = 0; $i < $pathByteLength; $i += $pathHashSize) {
                $hopBytes = substr($rawBytes, $offset + $i, $pathHashSize);
                $hopsArray[] = strtoupper(bin2hex($hopBytes));
            }
            $offset += $pathByteLength;
        }

        $payloadBytes = substr($rawBytes, $offset);
        $payloadLen = strlen($payloadBytes);

        $result = [
            'success'      => true,
            'raw_hex'      => strtoupper(bin2hex($rawBytes)),
            'total_length' => $totalLen,
            'header'       => [
                'raw_byte'         => sprintf('0x%02X', $headerByte),
                'version'          => $version,
                'payload_type'     => $payloadType,
                'payload_type_str' => $payloadTypeNames[$payloadType] ?? 'NEŽINOMAS (' . $payloadType . ')',
                'route_type'       => $routeType,
                'route_type_str'   => $routeTypeNames[$routeType] ?? 'NEŽINOMAS (' . $routeType . ')',
                'transport_codes'  => $transportCodes,
            ],
            'path' => [
                'hop_count' => $pathHopCount,
                'hash_size' => $pathHashSize,
                'hops'      => $hopsArray,
            ],
            'payload_length' => $payloadLen,
            'payload_hex'    => strtoupper(bin2hex($payloadBytes)),
        ];

        // 3. Krovinio analizė pagal tipą
        if ($payloadType === self::PAYLOAD_TYPE_ADVERT) {
            $result['advert'] = $this->decodeAdvert($payloadBytes);
        } elseif (in_array($payloadType, [self::PAYLOAD_TYPE_REQ, self::PAYLOAD_TYPE_RESPONSE, self::PAYLOAD_TYPE_ANON_REQ, self::PAYLOAD_TYPE_PATH, self::PAYLOAD_TYPE_TXT_MSG])) {
            $result['encryption'] = $this->handleEncryptedPayload($payloadType, $payloadBytes, $sharedSecretHex, $myPublicKeyHex);
        }

        return $result;
    }

    /**
     * Iškoduoja ADVERT skelbimo krovinį.
     *
     * @param string $payload Binariniai krovinio baitai
     * @return array Iškoduoti skelbimo laukai
     */
    public function decodeAdvert(string $payload): array
    {
        $len = strlen($payload);
        if ($len < 32) {
            return ['error' => 'ADVERT paketas per trumpas (mažiau 32 baitų)'];
        }

        $pubKey = substr($payload, 0, 32);
        $pubKeyHex = strtolower(bin2hex($pubKey));

        $timestamp = null;
        if ($len >= 36) {
            $timestamp = $this->unpackUInt32LE(substr($payload, 32, 4));
        }

        // Formatas A (>= 101B su 64B parašu) arba B (< 101B be parašo)
        $formatA = ($len >= 101);
        $flagsOffset = $formatA ? 100 : 36;
        $signatureHex = null;
        $signatureValid = null;

        if ($formatA && $len >= 100) {
            $signatureBytes = substr($payload, 36, 64);
            $signatureHex = strtolower(bin2hex($signatureBytes));
            if (function_exists('sodium_crypto_sign_verify_detached')) {
                try {
                    // MeshCore ADVERT Ed25519 parašas skaičiuojamas nuo: pub_key (32B) + timestamp (4B) + app_data (nuo 100B iki galo)
                    $signedData = substr($payload, 0, 36) . substr($payload, 100);
                    $signatureValid = sodium_crypto_sign_verify_detached($signatureBytes, $signedData, $pubKey);
                } catch (\Throwable $e) {
                    $signatureValid = false;
                }
            }
        }

        $advertInfo = [
            'format'          => $formatA ? 'A' : 'B',
            'public_key'      => $pubKeyHex,
            'timestamp'       => $timestamp,
            'signature'       => $signatureHex,
            'signature_valid' => $signatureValid,
            'flags'           => null,
            'node_name'       => null,
            'location'        => null,
            'battery'         => null,
        ];

        if ($signatureValid === false) {
            $advertInfo['error'] = 'Neteisingas ADVERT Ed25519 kriptografinis parašas (eteryje sugadintas paketas su bitų klaidomis)';
        }

        if ($len > $flagsOffset) {
            $appFlags = ord($payload[$flagsOffset]);
            $appType = $appFlags & 0x03; // 0=generic, 1=chat, 2=repeater, 3=room
            $appTypeNames = [0 => 'generic', 1 => 'chat', 2 => 'repeater', 3 => 'room'];

            $hasLocation = (bool)(($appFlags >> 4) & 1);
            $hasFeat1    = (bool)(($appFlags >> 5) & 1); // Baterija
            $hasFeat2    = (bool)(($appFlags >> 6) & 1); // Rezervuota
            $hasName     = (bool)(($appFlags >> 7) & 1); // Tekstinis pavadinimas

            $advertInfo['flags'] = [
                'raw'          => sprintf('0x%02X', $appFlags),
                'type'         => $appType,
                'type_str'     => $appTypeNames[$appType] ?? 'unknown',
                'chat'         => ($appType === 1),
                'repeater'     => ($appType === 2),
                'room'         => ($appType === 3),
                'hasLocation'  => $hasLocation,
                'hasFeat1'     => $hasFeat1,
                'hasFeat2'     => $hasFeat2,
                'hasName'      => $hasName,
            ];

            $currOffset = $flagsOffset + 1;

            // GPS koordinatės (int32 / 1000000.0)
            if ($hasLocation && $len >= $currOffset + 8) {
                $rawLat = $this->unpackInt32LE(substr($payload, $currOffset, 4));
                $rawLon = $this->unpackInt32LE(substr($payload, $currOffset + 4, 4));
                $currOffset += 8;

                if ($rawLat !== 0 || $rawLon !== 0) {
                    $advertInfo['location'] = [
                        'latitude'  => round($rawLat / 1000000.0, 6),
                        'longitude' => round($rawLon / 1000000.0, 6),
                    ];
                }
            }

            // Baterija (uint16 mV)
            if ($hasFeat1 && $len >= $currOffset + 2) {
                $mV = $this->unpackUInt16LE(substr($payload, $currOffset, 2));
                $currOffset += 2;
                $advertInfo['battery'] = [
                    'voltage_mv' => $mV,
                    'voltage_v'  => round($mV / 1000.0, 2),
                ];
            }

            // Rezervuotas Feature2 (2 baitai)
            if ($hasFeat2 && $len >= $currOffset + 2) {
                $currOffset += 2;
            }

            // Pavadinimas (nuo currOffset iki galo)
            if ($hasName && $len > $currOffset) {
                $nameBytes = substr($payload, $currOffset);
                // Pašaliname galimus null-terminatorius
                $cleanName = rtrim($nameBytes, "\0");
                $advertInfo['node_name'] = mb_convert_encoding($cleanName, 'UTF-8', 'UTF-8');
            }
        }

        return $advertInfo;
    }

    /**
     * Apdoroja šifruotą krovinį: ištraukia siuntėjo/gavėjo mazgus ir, jei pateiktas raktas, dešifruoja.
     */
    protected function handleEncryptedPayload(int $payloadType, string $payload, ?string $sharedSecretHex, ?string $myPublicKeyHex = null): array
    {
        $len = strlen($payload);
        $res = [
            'is_encrypted'       => true,
            'dest_hash'          => null,
            'src_hash'           => null,
            'sender_public_key'  => null,
            'decrypted'          => false,
            'decryption_error'   => null,
            'plaintext_hex'      => null,
            'parsed_data'        => null,
        ];

        $encOffset = 0;

        if ($payloadType === self::PAYLOAD_TYPE_ANON_REQ) {
            // [dest_hash 1B][sender_pubkey 32B][ciphertext...][mac 4B]
            if ($len < 33 + 4) {
                $res['decryption_error'] = 'Krovinys per trumpas ANON_REQ formatui';
                return $res;
            }
            $res['dest_hash'] = sprintf('%02X', ord($payload[0]));
            $res['sender_public_key'] = strtolower(bin2hex(substr($payload, 1, 32)));
            $encOffset = 33;
        } else {
            // [dest_hash 1B][src_hash 1B][ciphertext...][mac 4B]
            if ($len < 2 + 4) {
                $res['decryption_error'] = 'Krovinys per trumpas [dest, src, mac] formatui';
                return $res;
            }
            $res['dest_hash'] = sprintf('%02X', ord($payload[0]));
            $res['src_hash']  = sprintf('%02X', ord($payload[1]));
            $encOffset = 2;
        }

        $encryptedBlock = substr($payload, $encOffset);

        if (empty($sharedSecretHex)) {
            $msg = 'Nepateiktas bendrasis šifravimo raktas (shared_secret_hex)';
            if ($myPublicKeyHex && !empty($res['src_hash'])) {
                $cleanMyKey = trim($myPublicKeyHex);
                if (strlen($cleanMyKey) >= 2) {
                    $myHash = sprintf('%02X', hexdec(substr($cleanMyKey, 0, 2)));
                    if (strcasecmp($res['src_hash'], $myHash) === 0) {
                        $msg = "Tai yra mūsų pačių mazgo ({$myHash}) išsiųstos užklausos aidas (Echo), kurį kartotuvas pakartojo per eterį.";
                    }
                }
            }
            $res['decryption_error'] = $msg;
            return $res;
        }

        try {
            $plaintext = $this->decryptPayload($encryptedBlock, $sharedSecretHex);
            $res['decrypted'] = true;
            $res['plaintext_hex'] = strtoupper(bin2hex($plaintext));
            $res['parsed_data'] = $this->parseDecryptedPlaintext($plaintext, $payloadType);
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            if ($myPublicKeyHex && !empty($res['dest_hash'])) {
                $cleanMyKey = trim($myPublicKeyHex);
                if (strlen($cleanMyKey) >= 2) {
                    $myHash = sprintf('%02X', hexdec(substr($cleanMyKey, 0, 2)));
                    if (strcasecmp($res['dest_hash'], $myHash) !== 0) {
                        $errorMsg = "Paketas adresuotas ne mūsų mazgui (Taikinio hash: {$res['dest_hash']}, o mūsų mazgo hash: {$myHash}). Kartotuvas šį atsaką užšifravo su gavėjo ({$res['dest_hash']}) bendruoju raktu, todėl šis mazgas jo iššifruoti negali.";
                    }
                }
            }
            $res['decryption_error'] = $errorMsg;
        }

        return $res;
    }

    /**
     * Dešifruoja MeshCore šifruotą bloką pagal MeshCrypto specifikaciją.
     *
     * Standartinis MeshCore formatas (MeshCrypto.h):
     *   [MAC (2 baitai)][Ciphertext (16 * k baitų)]
     *   - MAC apskaičiuojamas per HMAC-SHA256(ciphertext, sharedSecret 32B) -> pirmieji 2 baitai
     *   - Šifravimas: AES-128-ECB, raktas: sharedSecret[0..15]
     *
     * Taip pat palaikomas alternatyvus formatas:
     *   [Ciphertext][MAC (4 baitai gale)] su AES-128-CBC
     *
     * @param string $encryptedBlock Šifruotas blokas (su MAC)
     * @param string $sharedSecretHex 32 baitų bendrasis raktas (64 hex simboliai)
     * @return string Iššifruotas tekstas (Plaintext)
     */
    public function decryptPayload(string $encryptedBlock, string $sharedSecretHex): string
    {
        $blockLen = strlen($encryptedBlock);
        if ($blockLen < 18) {
            throw new \InvalidArgumentException('Užšifruotas blokas per trumpas (mažiau 18 baitų)');
        }

        $sharedSecret = $this->ensureBinaryBytes($sharedSecretHex);
        if (strlen($sharedSecret) !== 32) {
            throw new \InvalidArgumentException('Bendrasis raktas privalo būti lygiai 32 baitai (64 hex simboliai)');
        }

        $encKey = substr($sharedSecret, 0, 16);

        // ---------------------------------------------------------------------
        // 1. STANDARTINIS MESHCORE METODAS (MeshCrypto.h):
        // 2 baitų MAC priekyje: [MAC 2B][Ciphertext 16*k B]
        // ---------------------------------------------------------------------
        if ($blockLen >= 2 + 16 && (($blockLen - 2) % 16 === 0)) {
            $receivedMac = substr($encryptedBlock, 0, 2);
            $ciphertext  = substr($encryptedBlock, 2);

            $computedFullHmac = hash_hmac('sha256', $ciphertext, $sharedSecret, true);
            $computedMac      = substr($computedFullHmac, 0, 2);

            if (hash_equals($receivedMac, $computedMac)) {
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'aes-128-ecb',
                    $encKey,
                    OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
                );

                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }

        // ---------------------------------------------------------------------
        // 2. ALTERNATYVA: 4 baitų MAC paketo gale [Ciphertext][MAC 4B] su AES-128-CBC
        // ---------------------------------------------------------------------
        if ($blockLen >= 16 + 4 && (($blockLen - 4) % 16 === 0)) {
            $ciphertext  = substr($encryptedBlock, 0, $blockLen - 4);
            $receivedMac = substr($encryptedBlock, $blockLen - 4, 4);

            $macKey = substr($sharedSecret, 16, 16);
            $computedMac1 = substr(hash_hmac('sha256', $ciphertext, $macKey, true), 0, 4);
            $computedMac2 = substr(hash_hmac('sha256', $ciphertext, $sharedSecret, true), 0, 4);

            if (hash_equals($receivedMac, $computedMac1) || hash_equals($receivedMac, $computedMac2)) {
                $iv = str_repeat("\0", 16);
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'aes-128-cbc',
                    $encKey,
                    OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
                    $iv
                );

                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }

        // ---------------------------------------------------------------------
        // 3. ALTERNATYVA: 2 baitų MAC paketo gale [Ciphertext][MAC 2B]
        // ---------------------------------------------------------------------
        if ($blockLen >= 16 + 2 && (($blockLen - 2) % 16 === 0)) {
            $ciphertext  = substr($encryptedBlock, 0, $blockLen - 2);
            $receivedMac = substr($encryptedBlock, $blockLen - 2, 2);

            $computedMac = substr(hash_hmac('sha256', $ciphertext, $sharedSecret, true), 0, 2);
            if (hash_equals($receivedMac, $computedMac)) {
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'aes-128-ecb',
                    $encKey,
                    OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
                );

                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }

        throw new \RuntimeException('HMAC-SHA256 patikra nepavyko! Neteisingas bendras raktas arba pažeistas paketas.');
    }

    /**
     * Išanalizuoja dešifruotą turinį priklausomai nuo MeshCore paketo tipo.
     */
    protected function parseDecryptedPlaintext(string $plain, int $payloadType): array
    {
        $len = strlen($plain);
        if ($len < 4) {
            return ['raw_plaintext' => bin2hex($plain)];
        }

        $timestamp = $this->unpackUInt32LE(substr($plain, 0, 4));
        $res = [
            'timestamp' => $timestamp,
        ];

        // 1. ANON_REQ (Prisijungimas su slaptažodžiu)
        if ($payloadType === self::PAYLOAD_TYPE_ANON_REQ) {
            $pwdBytes = substr($plain, 4);
            $res['type'] = 'LOGIN_REQUEST';
            $res['password'] = rtrim($pwdBytes, "\0");
            return $res;
        }

        // 2. REQ (Užklausos: GET_STATUS, GET_TELEMETRY ir kt.)
        if ($payloadType === self::PAYLOAD_TYPE_REQ && $len >= 5) {
            $reqType = ord($plain[4]);
            $reqNames = [
                self::REQ_TYPE_GET_STATUS         => 'GET_STATUS (Būsenos užklausa)',
                self::REQ_TYPE_KEEP_ALIVE         => 'KEEP_ALIVE (Sesijos palaikymas)',
                self::REQ_TYPE_GET_TELEMETRY_DATA => 'GET_TELEMETRY_DATA (Telemetrijos užklausa)',
                self::REQ_TYPE_GET_ACCESS_LIST    => 'GET_ACCESS_LIST',
                self::REQ_TYPE_GET_NEIGHBOURS     => 'GET_NEIGHBOURS',
                self::REQ_TYPE_GET_OWNER_INFO     => 'GET_OWNER_INFO',
            ];
            $res['request_type'] = $reqType;
            $res['request_type_str'] = $reqNames[$reqType] ?? sprintf('0x%02X', $reqType);
            return $res;
        }

        // 3. RESPONSE arba PATH
        if ($payloadType === self::PAYLOAD_TYPE_RESPONSE || $payloadType === self::PAYLOAD_TYPE_PATH) {
            $dataPtr = $plain;
            $dataLen = $len;

            // Jei PATH paketas: išsipakuojame vidinį maršrutą ir papildomą atsaką
            if ($payloadType === self::PAYLOAD_TYPE_PATH) {
                $pLenByte = ord($plain[0]);
                $hSize = (($pLenByte >> 6) & 0x03) + 1;
                $hCount = $pLenByte & 63;
                $k = 1 + $hSize * $hCount;
                if ($k <= $len) {
                    $innerHops = [];
                    for ($i = 1; $i + $hSize <= $k; $i += $hSize) {
                        $innerHops[] = strtoupper(bin2hex(substr($plain, $i, $hSize)));
                    }
                    $res['returned_path'] = [
                        'hop_count' => $hCount,
                        'hash_size' => $hSize,
                        'hops'      => $innerHops,
                    ];
                }
                if ($k < $len) {
                    $res['path_extra_type'] = ord($plain[$k]);
                    $dataPtr = substr($plain, $k + 1);
                    $dataLen = strlen($dataPtr);
                }
            }

            // A) Tikriname LOGIN_OK patvirtinimą
            if ($dataLen >= 5 && ord($dataPtr[4]) === self::RESP_SERVER_LOGIN_OK) {
                $res['response_type'] = 'LOGIN_SUCCESS';
                $res['permissions'] = ($dataLen >= 8) ? sprintf('0x%02X', ord($dataPtr[7])) : '0x00';
                return $res;
            }

            // B) Tikriname RepeaterStats būsenos struktūrą (44..52 baitai po 4B antraštės)
            if ($dataLen >= 4 + 44) {
                $statsBytes = substr($dataPtr, 4);
                $stats = $this->parseRepeaterStats($statsBytes);
                if (empty($stats['error'])) {
                    $res['response_type'] = 'REPEATER_STATS';
                    $res['repeater_stats'] = $stats;
                    return $res;
                }
            }

            // C) Skenuojame CayenneLPP jutiklių telemetriją (arba tuščią telemetrijos atsaką)
            $cayenne = $this->parseCayenneLPP(substr($dataPtr, 4));
            $res['response_type'] = 'CAYENNE_LPP_TELEMETRY';
            $res['sensors'] = $cayenne;
            return $res;
        }

        // 4. Tekstinis pranešimas (TXT_MSG)
        if ($payloadType === self::PAYLOAD_TYPE_TXT_MSG) {
            $res['type'] = 'TEXT_MESSAGE';
            $res['message'] = mb_convert_encoding(substr($plain, 4), 'UTF-8', 'UTF-8');
            return $res;
        }

        return $res;
    }

    /**
     * Išpakuoja MeshCore RepeaterStats C struktūrą.
     */
    public function parseRepeaterStats(string $bytes): array
    {
        $len = strlen($bytes);
        if ($len < 44) {
            return ['error' => 'Per mažai baitų RepeaterStats struktūrai'];
        }

        $battMv = $this->unpackUInt16LE(substr($bytes, 0, 2));
        $txQueue = $this->unpackUInt16LE(substr($bytes, 2, 2));
        $noiseFloor = $this->unpackInt16LE(substr($bytes, 4, 2));
        $lastRssi = $this->unpackInt16LE(substr($bytes, 6, 2));
        $pktsRecv = $this->unpackUInt32LE(substr($bytes, 8, 4));
        $pktsSent = $this->unpackUInt32LE(substr($bytes, 12, 4));
        $airTimeSecs = $this->unpackUInt32LE(substr($bytes, 16, 4));
        $upTimeSecs = $this->unpackUInt32LE(substr($bytes, 20, 4));
        $sentFlood = $this->unpackUInt32LE(substr($bytes, 24, 4));
        $sentDirect = $this->unpackUInt32LE(substr($bytes, 28, 4));
        $recvFlood = $this->unpackUInt32LE(substr($bytes, 32, 4));
        $recvDirect = $this->unpackUInt32LE(substr($bytes, 36, 4));
        $errEvents = $this->unpackUInt16LE(substr($bytes, 40, 2));
        $lastSnrRaw = $this->unpackInt16LE(substr($bytes, 42, 2));
        $lastSnrDb = round($lastSnrRaw / 4.0, 2);

        // Sanity checks (duomenų patikimumo tikrinimas):
        // 1. Baterija: Li-Ion / LiFePO4 / 12V saulės sistema turi būti tarp 2000 mV (2.0V) ir 15000 mV (15.0V).
        // Jokiu būdu negali būti 42080 mV (42.08V)!
        if ($battMv < 2000 || $battMv > 15000) {
            return ['error' => "Nekorektiška baterijos įtampa RepeaterStats struktūroje: {$battMv} mV"];
        }

        // 2. RSSI: radijo signalas tarp -150 dBm ir 0 dBm
        if ($lastRssi < -150 || $lastRssi > 0) {
            return ['error' => "Nekorektiškas RSSI RepeaterStats struktūroje: {$lastRssi} dBm"];
        }

        // 3. SNR: signalo/triukšmo santykis tarp -35 dB ir +35 dB
        if ($lastSnrDb < -35.0 || $lastSnrDb > 35.0) {
            return ['error' => "Nekorektiškas SNR RepeaterStats struktūroje: {$lastSnrDb} dB"];
        }

        // 4. Noise floor: eterio triukšmas tarp -150 dBm ir -20 dBm
        if ($noiseFloor < -150 || $noiseFloor > -20) {
            return ['error' => "Nekorektiškas Noise Floor RepeaterStats struktūroje: {$noiseFloor} dBm"];
        }

        // 5. TxQueue: eilės ilgis negali viršyti 2000 paketų
        if ($txQueue > 2000) {
            return ['error' => "Nekorektiškas TxQueue ilgis: {$txQueue}"];
        }

        // 6. Uptime: veikimo laikas iki 10 metų
        if ($upTimeSecs > 315360000) {
            return ['error' => "Nekorektiškas Uptime sekundėmis: {$upTimeSecs}"];
        }

        $stats = [
            'battery_mv'          => $battMv,
            'battery_volts'       => round($battMv / 1000.0, 2),
            'uptime_seconds'      => $upTimeSecs,
            'uptime_hours'        => round($upTimeSecs / 3600.0, 1),
            'air_time_seconds'    => $airTimeSecs,
            'noise_floor_dbm'     => $noiseFloor,
            'last_rssi_dbm'       => $lastRssi,
            'last_snr_db'         => $lastSnrDb,
            'packets_received'    => $pktsRecv,
            'packets_sent'        => $pktsSent,
            'sent_flood'          => $sentFlood,
            'sent_direct'         => $sentDirect,
            'recv_flood'          => $recvFlood,
            'recv_direct'         => $recvDirect,
            'tx_queue_len'        => $txQueue,
            'error_events'        => $errEvents,
        ];

        // Papildomi laukai v1.11 / v1.12+ (jei baitų >= 48)
        if ($len >= 48) {
            $stats['direct_dups'] = $this->unpackUInt16LE(substr($bytes, 44, 2));
            $stats['flood_dups']  = $this->unpackUInt16LE(substr($bytes, 46, 2));
        }
        if ($len >= 52) {
            $stats['rx_air_time_secs'] = $this->unpackUInt32LE(substr($bytes, 48, 4));
        }
        if ($len >= 56) {
            $stats['recv_errors'] = $this->unpackUInt32LE(substr($bytes, 52, 4));
        }

        return $stats;
    }

    /**
     * Išanalizuoja CayenneLPP jutiklių telemetrijos kadrus.
     */
    public function parseCayenneLPP(string $bytes): array
    {
        $sensors = [];
        $len = strlen($bytes);
        $offset = 0;

        while ($offset + 2 <= $len) {
            $channel = ord($bytes[$offset]);
            $type    = ord($bytes[$offset + 1]);
            $offset += 2;

            // 0x02 arba 0x74: Analog Input / Įtampa (2 baitai signed int16, padalinta iš 100)
            if (($type === 0x02 || $type === 0x74) && $offset + 2 <= $len) {
                $valRaw = $this->unpackInt16BE(substr($bytes, $offset, 2));
                $offset += 2;
                $v = $valRaw * 0.01;
                $sensors["channel_{$channel}_voltage"] = [
                    'type'       => 'analog_voltage',
                    'channel'    => $channel,
                    'voltage_v'  => round($v, 2),
                    'voltage_mv' => round($v * 1000),
                ];
                continue;
            }

            // 0x67: Temperatūra (2 baitai signed int16, padalinta iš 10)
            if ($type === 0x67 && $offset + 2 <= $len) {
                $tempRaw = $this->unpackInt16BE(substr($bytes, $offset, 2));
                $offset += 2;
                $tempC = $tempRaw * 0.1;
                $sensors["channel_{$channel}_temperature"] = [
                    'type'          => 'temperature',
                    'channel'       => $channel,
                    'temperature_c' => round($tempC, 1),
                ];
                continue;
            }

            // 0x65: Apšviestumas (Illuminance, 2 baitai uint16)
            if ($type === 0x65 && $offset + 2 <= $len) {
                $lux = $this->unpackUInt16BE(substr($bytes, $offset, 2));
                $offset += 2;
                $sensors["channel_{$channel}_illuminance"] = [
                    'type'    => 'illuminance',
                    'channel' => $channel,
                    'lux'     => $lux,
                ];
                continue;
            }

            // 0x68: Santykinė drėgmė (Humidity, 1 baitas uint8, padalinta iš 2)
            if ($type === 0x68 && $offset + 1 <= $len) {
                $hum = ord($bytes[$offset]) * 0.5;
                $offset += 1;
                $sensors["channel_{$channel}_humidity"] = [
                    'type'       => 'humidity',
                    'channel'    => $channel,
                    'humidity_p' => round($hum, 1),
                ];
                continue;
            }

            // 0x73: Barometras / Slėgis (Barometer, 2 baitai uint16, padalinta iš 10)
            if ($type === 0x73 && $offset + 2 <= $len) {
                $press = $this->unpackUInt16BE(substr($bytes, $offset, 2)) * 0.1;
                $offset += 2;
                $sensors["channel_{$channel}_pressure"] = [
                    'type'         => 'pressure',
                    'channel'      => $channel,
                    'pressure_hpa' => round($press, 1),
                ];
                continue;
            }

            // Neatpažintas jutiklio tipas - nutraukiame skenavimą
            break;
        }

        return $sensors;
    }

    // =========================================================================
    // 🛠️ PAGALBINIAI METODAI (Baitų ir Skaičių Konvertavimas)
    // =========================================================================

    protected function ensureBinaryBytes(string $input): string
    {
        $clean = trim($input);
        if (strpos($clean, '0x') === 0 || strpos($clean, '0X') === 0) {
            $clean = substr($clean, 2);
        }

        // Tikriname ar tai taisyklinga hex eilutė
        if (ctype_xdigit($clean) && (strlen($clean) % 2 === 0)) {
            return hex2bin($clean);
        }

        return $input;
    }

    protected function unpackUInt16LE(string $bytes): int
    {
        return unpack('v', $bytes)[1];
    }

    protected function unpackInt16LE(string $bytes): int
    {
        $val = unpack('v', $bytes)[1];
        if ($val >= 0x8000) {
            $val -= 0x10000;
        }
        return $val;
    }

    protected function unpackUInt16BE(string $bytes): int
    {
        return unpack('n', $bytes)[1];
    }

    protected function unpackInt16BE(string $bytes): int
    {
        $val = unpack('n', $bytes)[1];
        if ($val >= 0x8000) {
            $val -= 0x10000;
        }
        return $val;
    }

    protected function unpackUInt32LE(string $bytes): int
    {
        return unpack('V', $bytes)[1];
    }

    protected function unpackInt32LE(string $bytes): int
    {
        $val = unpack('V', $bytes)[1];
        if ($val >= 0x80000000) {
            $val -= 0x100000000;
        }
        return $val;
    }

    /**
     * Greitai ištraukia krovinio (payload) heksą iš neapdoroto paketo be pilno dešifravimo.
     * Naudojama dublikatų aptikimui (deduplication) serveryje.
     */
    public static function extractPayloadHex(string $rawHex): ?string
    {
        $clean = preg_replace('/[^0-9A-Fa-f]/', '', $rawHex);
        if (strlen($clean) < 4 || (strlen($clean) % 2) !== 0) {
            return null;
        }
        $rawBytes = hex2bin($clean);
        if (!$rawBytes) {
            return null;
        }
        $totalLen = strlen($rawBytes);
        $headerByte = ord($rawBytes[0]);
        $routeType = $headerByte & 0x03;
        $hasTransportCodes = ($routeType === self::ROUTE_TYPE_TRANSPORT_FLOOD || $routeType === self::ROUTE_TYPE_TRANSPORT_DIRECT);
        $offset = 1;
        if ($hasTransportCodes) {
            $offset += 4;
        }
        if ($totalLen < $offset + 1) {
            return null;
        }
        $pathLenByte = ord($rawBytes[$offset]);
        $offset += 1;

        $pathHashSize = (($pathLenByte >> 6) & 0x03) + 1;
        $pathHopCount = $pathLenByte & 0x3F;
        $pathByteLength = $pathHopCount * $pathHashSize;
        $remaining = $totalLen - $offset;
        if ($pathHashSize > 3 || $pathByteLength > $remaining) {
            $pathByteLength = min($pathLenByte, $remaining);
        }
        $offset += $pathByteLength;
        if ($offset >= $totalLen) {
            return null;
        }
        return strtoupper(bin2hex(substr($rawBytes, $offset)));
    }
}
