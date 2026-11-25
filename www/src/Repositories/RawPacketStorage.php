<?php

namespace MeshCore\Repositories;

use MeshCore\Contracts\NodeRepositoryInterface;
use MeshCore\Contracts\RawPacketRepositoryInterface;
use MeshCore\Contracts\TelemetryRepositoryInterface;
use MeshCore\MeshCoreDecoder;
use PDO;

/**
 * Class RawPacketStorage
 *
 * Atsakinga už RAW paketų eiliškumą, saugojimą, apdorojimo statusų valdymą ir paiešką pagal SRP.
 */
class RawPacketStorage implements RawPacketRepositoryInterface
{
    private PDO $pdo;
    private string $table;
    private ?NodeRepositoryInterface $nodeRepo;
    private ?TelemetryRepositoryInterface $telemetryRepo;

    public function __construct(
        PDO $pdo,
        string $table = 'raw_packets',
        ?NodeRepositoryInterface $nodeRepo = null,
        ?TelemetryRepositoryInterface $telemetryRepo = null
    ) {
        $this->pdo           = $pdo;
        $this->table         = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $this->nodeRepo      = $nodeRepo;
        $this->telemetryRepo = $telemetryRepo;
    }

    public function setNodeRepository(NodeRepositoryInterface $nodeRepo): void
    {
        $this->nodeRepo = $nodeRepo;
    }

    public function setTelemetryRepository(TelemetryRepositoryInterface $telemetryRepo): void
    {
        $this->telemetryRepo = $telemetryRepo;
    }

    /**
     * Išsaugo naują atėjusį neapdorotą (RAW) įrašą su dublikatų / aidų prevencija.
     */
    public function saveRaw($data, ?string $sharedSecretHex = null): int
    {
        if (is_string($data)) {
            $rawHex = trim($data);
            $payload = [
                'raw_hex'           => $rawHex,
                'shared_secret_hex' => $sharedSecretHex,
            ];
        } else {
            $payload = $data;
            $rawHex  = trim((string)($payload['raw_hex'] ?? ($payload['raw'] ?? '')));
            if (!empty($sharedSecretHex) && empty($payload['shared_secret_hex'])) {
                $payload['shared_secret_hex'] = $sharedSecretHex;
            }
        }

        if (empty($rawHex)) {
            throw new \InvalidArgumentException('Nepateiktas privalomas raw_hex laukelis.');
        }

        $direction       = !empty($payload['direction']) ? substr((string)$payload['direction'], 0, 16) : null;
        $length          = isset($payload['length']) ? (int)$payload['length'] : (int)(strlen($rawHex) / 2);
        $rssi            = isset($payload['rssi']) ? (float)$payload['rssi'] : null;
        $snr             = isset($payload['snr']) ? (float)$payload['snr'] : null;
        $targetName      = !empty($payload['target_name']) ? (string)$payload['target_name'] : null;
        $targetPubKey    = !empty($payload['target_public_key']) ? (string)$payload['target_public_key'] : null;
        $senderName      = !empty($payload['sender_name']) ? (string)$payload['sender_name'] : null;
        $senderPubKey    = !empty($payload['sender_public_key']) ? (string)$payload['sender_public_key'] : null;
        $myPubKey        = !empty($payload['my_public_key']) ? (string)$payload['my_public_key'] : null;
        $secret          = !empty($payload['shared_secret_hex']) ? (string)$payload['shared_secret_hex'] : $sharedSecretHex;
        $isEncrypted     = !empty($payload['is_encrypted']) || !empty($secret);
        $botId           = !empty($payload['bot_id']) ? strtoupper(substr(trim((string)$payload['bot_id']), 0, 16)) : 'KLP';
        $payloadJson     = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Apsauga nuo RX dublikatų
        if ($direction === 'RX') {
            $dedupWindowSec = 20;
            $timeCondition = ($driver === 'pgsql')
                ? "created_at >= NOW() - INTERVAL '{$dedupWindowSec} seconds'"
                : "created_at >= NOW() - INTERVAL {$dedupWindowSec} SECOND";
            $exactHexOp = ($driver === 'pgsql') ? "raw_hex ILIKE :raw_hex" : "raw_hex = :raw_hex";

            $checkSql = "SELECT id FROM {$this->table}
                         WHERE bot_id = :bot_id 
                           AND direction = :direction 
                           AND {$exactHexOp} 
                           AND {$timeCondition} 
                         ORDER BY id DESC LIMIT 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([
                ':bot_id'    => $botId,
                ':direction' => $direction,
                ':raw_hex'   => $rawHex,
            ]);
            $existingId = $checkStmt->fetchColumn();
            if ($existingId) {
                return (int)$existingId;
            }

            $extractedPayload = MeshCoreDecoder::extractPayloadHex($rawHex);
            if (!empty($extractedPayload) && strlen($extractedPayload) >= 8) {
                $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
                $payloadSql = "SELECT id FROM {$this->table}
                               WHERE bot_id = :bot_id 
                                 AND direction = :direction 
                                 AND raw_hex {$likeOp} :payload_suffix 
                                 AND {$timeCondition} 
                               ORDER BY id DESC LIMIT 1";
                $pStmt = $this->pdo->prepare($payloadSql);
                $pStmt->execute([
                    ':bot_id'         => $botId,
                    ':direction'      => $direction,
                    ':payload_suffix' => '%' . $extractedPayload,
                ]);
                $existingPayloadId = $pStmt->fetchColumn();
                if ($existingPayloadId) {
                    return (int)$existingPayloadId;
                }
            }
        }
        
        $sql = "INSERT INTO {$this->table} (
                    direction, raw_hex, length, rssi, snr,
                    target_name, target_public_key, sender_name, sender_public_key,
                    my_public_key, shared_secret_hex, is_encrypted, bot_id, payload_json,
                    processed, created_at, processed_at
                ) VALUES (
                    :direction, :raw_hex, :length, :rssi, :snr,
                    :target_name, :target_public_key, :sender_name, :sender_public_key,
                    :my_public_key, :shared_secret_hex, :is_encrypted, :bot_id, :payload_json,
                    false, CURRENT_TIMESTAMP, NULL
                )";

        if ($driver === 'pgsql') {
            $sql .= " RETURNING id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':direction', $direction);
            $stmt->bindValue(':raw_hex', $rawHex);
            $stmt->bindValue(':length', $length, PDO::PARAM_INT);
            $stmt->bindValue(':rssi', $rssi);
            $stmt->bindValue(':snr', $snr);
            $stmt->bindValue(':target_name', $targetName);
            $stmt->bindValue(':target_public_key', $targetPubKey);
            $stmt->bindValue(':sender_name', $senderName);
            $stmt->bindValue(':sender_public_key', $senderPubKey);
            $stmt->bindValue(':my_public_key', $myPubKey);
            $stmt->bindValue(':shared_secret_hex', $secret);
            $stmt->bindValue(':is_encrypted', $isEncrypted, PDO::PARAM_BOOL);
            $stmt->bindValue(':bot_id', $botId);
            $stmt->bindValue(':payload_json', $payloadJson);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':direction', $direction);
        $stmt->bindValue(':raw_hex', $rawHex);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
        $stmt->bindValue(':rssi', $rssi);
        $stmt->bindValue(':snr', $snr);
        $stmt->bindValue(':target_name', $targetName);
        $stmt->bindValue(':target_public_key', $targetPubKey);
        $stmt->bindValue(':sender_name', $senderName);
        $stmt->bindValue(':sender_public_key', $senderPubKey);
        $stmt->bindValue(':my_public_key', $myPubKey);
        $stmt->bindValue(':shared_secret_hex', $secret);
        $stmt->bindValue(':is_encrypted', $isEncrypted ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':bot_id', $botId);
        $stmt->bindValue(':payload_json', $payloadJson);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Paima sekantį įrašą apdorojimui pagal prioritetą.
     */
    public function fetchNextForProcessing(): ?array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $sql = <<<SQL
            WITH next_item AS (
                SELECT id 
                FROM {$this->table}
                WHERE processed = false
                ORDER BY 
                    CASE WHEN processed_at IS NULL THEN 0 ELSE 1 END ASC,
                    processed_at DESC,
                    id ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED
            )
            UPDATE {$this->table}
            SET processed_at = CURRENT_TIMESTAMP
            FROM next_item
            WHERE {$this->table}.id = next_item.id
            RETURNING {$this->table}.*;
SQL;
            $stmt = $this->pdo->query($sql);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        $this->pdo->beginTransaction();
        try {
            $selectSql = <<<SQL
            SELECT * FROM {$this->table}
            WHERE processed = 0 OR processed = false
            ORDER BY 
                CASE WHEN processed_at IS NULL THEN 0 ELSE 1 END ASC,
                processed_at DESC,
                id ASC
            LIMIT 1
            FOR UPDATE
SQL;
            $stmt = $this->pdo->query($selectSql);
            $row = $stmt->fetch();

            if ($row) {
                $updateSql = "UPDATE {$this->table} SET processed_at = CURRENT_TIMESTAMP WHERE id = :id";
                $uStmt = $this->pdo->prepare($updateSql);
                $uStmt->execute([':id' => $row['id']]);
                $this->pdo->commit();
                $row['processed_at'] = date('Y-m-d H:i:s');
                return $row;
            }

            $this->pdo->commit();
            return null;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Pagal heksadecimalinį prefiksą (2 arba 4 simbolių) arba pilną raktą suranda mazgą 'nodes' lentelėje.
     */
    private function resolveNodeByPrefixOrKey(string $prefixOrKey, ?string $botId = null): ?array
    {
        $clean = strtolower(trim($prefixOrKey));
        if ($clean === '' || !ctype_xdigit($clean)) {
            return null;
        }

        try {
            $bId = strtoupper(trim((string)($botId ?? '')));
            if (strlen($clean) >= 32) {
                $stmt = $this->pdo->prepare("
                    SELECT public_key, node_id, node_name, is_repeater
                    FROM nodes
                    WHERE LOWER(public_key) = :pk
                    ORDER BY is_repeater DESC, id ASC
                    LIMIT 1
                ");
                $stmt->execute([':pk' => $clean]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if (strlen($clean) === 4) {
                $stmt = $this->pdo->prepare("
                    SELECT public_key, node_id, node_name, is_repeater
                    FROM nodes
                    WHERE LOWER(node_id) = :nid OR LOWER(public_key) LIKE :pref
                    ORDER BY is_repeater DESC,
                             (COALESCE(is_disabled, 0) = 0) DESC,
                             (UPPER(COALESCE(bot_id, '')) = :bot_id) DESC,
                             requested_date DESC,
                             last_seen_at DESC
                    LIMIT 1
                ");
                $stmt->execute([
                    ':nid'    => $clean,
                    ':pref'   => $clean . '%',
                    ':bot_id' => $bId,
                ]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $stmt = $this->pdo->prepare("
                SELECT public_key, node_id, node_name, is_repeater
                FROM nodes
                WHERE LOWER(public_key) LIKE :pref
                ORDER BY is_repeater DESC,
                         (COALESCE(is_disabled, 0) = 0) DESC,
                         (UPPER(COALESCE(bot_id, '')) = :bot_id) DESC,
                         requested_date DESC,
                         last_seen_at DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':pref'   => $clean . '%',
                ':bot_id' => $bId,
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Iš iškoduoto paketo nustato siuntėjo ir taikinio vardus bei viešuosius raktus.
     */
    private function resolvePacketEndpoints(array $decoded, array $record): array
    {
        $direction = strtoupper(trim((string)($record['direction'] ?? 'RX')));
        $botId = !empty($record['bot_id']) ? strtoupper(trim((string)$record['bot_id'])) : 'KLP';
        $myPubKey = !empty($record['my_public_key']) ? strtolower(trim((string)$record['my_public_key'])) : null;

        $senderName = !empty($record['sender_name']) ? trim((string)$record['sender_name']) : null;
        $senderKey  = !empty($record['sender_public_key']) ? strtolower(trim((string)$record['sender_public_key'])) : null;
        $targetName = !empty($record['target_name']) ? trim((string)$record['target_name']) : null;
        $targetKey  = !empty($record['target_public_key']) ? strtolower(trim((string)$record['target_public_key'])) : null;

        // 1. ADVERT paketas: siuntėjas yra pats skelbimą išsiuntęs mazgas
        if (!empty($decoded['advert']) && is_array($decoded['advert']) && !empty($decoded['advert']['public_key'])) {
            $senderKey = strtolower(trim((string)$decoded['advert']['public_key']));
            $senderName = !empty($decoded['advert']['node_name'])
                ? trim((string)$decoded['advert']['node_name'])
                : substr($senderKey, 0, 4);
        }

        // 2. ANON_REQ (Login) paketas: siuntėjo pilnas 32 B raktas yra šifravimo antraštėje
        if (empty($senderKey) && !empty($decoded['encryption']['sender_public_key'])) {
            $senderKey = strtolower(trim((string)$decoded['encryption']['sender_public_key']));
        }

        // 3. Jei tai TX paketas iš mūsų boto
        if ($direction === 'TX') {
            if (empty($senderName)) {
                $senderName = $botId;
            }
            if (empty($senderKey) && !empty($myPubKey)) {
                $senderKey = $myPubKey;
            }
        }

        // 4. Šifruoto paketo src_hash / dest_hash
        $srcHash = !empty($decoded['encryption']['src_hash']) ? strtolower(trim((string)$decoded['encryption']['src_hash'])) : null;
        $destHash = !empty($decoded['encryption']['dest_hash']) ? strtolower(trim((string)$decoded['encryption']['dest_hash'])) : null;

        if ($direction === 'RX' && empty($senderKey) && !empty($srcHash)) {
            $node = $this->resolveNodeByPrefixOrKey($srcHash, $botId);
            if ($node) {
                $senderKey = strtolower(trim((string)$node['public_key']));
                if (empty($senderName)) {
                    $senderName = !empty($node['node_id']) ? (string)$node['node_id'] : substr($senderKey, 0, 4);
                }
            } elseif (empty($senderName)) {
                $senderName = $srcHash;
            }
        }

        // 5. Jei siuntėjas vis dar nežinomas, bet turime senderKey – surandame vardą iš 'nodes'
        if (!empty($senderKey) && empty($senderName)) {
            $node = $this->resolveNodeByPrefixOrKey($senderKey, $botId);
            if ($node) {
                $senderName = !empty($node['node_id']) ? (string)$node['node_id'] : substr($senderKey, 0, 4);
            } else {
                $senderName = substr($senderKey, 0, 4);
            }
        }

        // 6. Jei RX pakete nėra nei advert, nei src_hash, bet yra maršruto šuoliai (pvz. GRP_TXT ar PATH) –
        // rodome pirmąjį maršruto kartotuvą (hops[0])
        if ($direction === 'RX' && empty($senderKey) && empty($senderName) && !empty($decoded['path']['hops']) && is_array($decoded['path']['hops'])) {
            $firstHop = strtolower(trim((string)$decoded['path']['hops'][0]));
            if ($firstHop !== '') {
                $node = $this->resolveNodeByPrefixOrKey($firstHop, $botId);
                if ($node) {
                    $senderKey = strtolower(trim((string)$node['public_key']));
                    $senderName = !empty($node['node_id']) ? (string)$node['node_id'] : substr($senderKey, 0, 4);
                } else {
                    $senderName = $firstHop;
                }
            }
        }

        // 7. Taikinio (Target) nustatymas pagal dest_hash
        if (empty($targetKey) && !empty($destHash)) {
            if ($direction === 'RX' && !empty($myPubKey) && strpos($myPubKey, $destHash) === 0) {
                $targetKey = $myPubKey;
                if (empty($targetName)) {
                    $targetName = $botId;
                }
            } else {
                $node = $this->resolveNodeByPrefixOrKey($destHash, $botId);
                if ($node) {
                    $targetKey = strtolower(trim((string)$node['public_key']));
                    if (empty($targetName)) {
                        $targetName = !empty($node['node_id']) ? (string)$node['node_id'] : substr($targetKey, 0, 4);
                    }
                } elseif (empty($targetName)) {
                    $targetName = $destHash;
                }
            }
        }

        if (!empty($targetKey) && empty($targetName)) {
            $node = $this->resolveNodeByPrefixOrKey($targetKey, $botId);
            if ($node) {
                $targetName = !empty($node['node_id']) ? (string)$node['node_id'] : substr($targetKey, 0, 4);
            } else {
                $targetName = substr($targetKey, 0, 4);
            }
        }

        return [
            'sender_name'       => $senderName,
            'sender_public_key' => $senderKey,
            'target_name'       => $targetName,
            'target_public_key' => $targetKey,
        ];
    }

    /**
     * Atnaujina raw_packets įrašo sender/target stulpelius, jei jie buvo nustatyti dekodavimo metu.
     */
    private function updatePacketEndpointsInDb(int $id, array $endpoints): void
    {
        try {
            $sql = "UPDATE {$this->table}
                    SET sender_name = COALESCE(NULLIF(sender_name, ''), :s_name),
                        sender_public_key = COALESCE(NULLIF(sender_public_key, ''), :s_key),
                        target_name = COALESCE(NULLIF(target_name, ''), :t_name),
                        target_public_key = COALESCE(NULLIF(target_public_key, ''), :t_key)
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':s_name' => $endpoints['sender_name'] ?? null,
                ':s_key'  => $endpoints['sender_public_key'] ?? null,
                ':t_name' => $endpoints['target_name'] ?? null,
                ':t_key'  => $endpoints['target_public_key'] ?? null,
                ':id'     => $id,
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Pažymi įrašą kaip sėkmingai apdorotą.
     */
    public function completeProcessing(int $id, array $decodedData, ?string $errorMessage = null): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $decodedJson = json_encode($decodedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sql = "UPDATE {$this->table}
                SET processed = :processed,
                    completed_at = CURRENT_TIMESTAMP,
                    decoded_json = :decoded_json,
                    error_message = :error_message
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':decoded_json', $decodedJson);
        $stmt->bindValue(':error_message', $errorMessage);

        if ($driver === 'pgsql') {
            $stmt->bindValue(':processed', true, PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(':processed', 1, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Pažymi apdorojimo klaidą.
     */
    public function failProcessing(int $id, string $errorMessage, ?array $partialDecoded = null): bool
    {
        $decodedJson = $partialDecoded ? json_encode($partialDecoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $sql = "UPDATE {$this->table}
                SET error_message = :error_message,
                    processed_at = CURRENT_TIMESTAMP" .
                    ($decodedJson !== null ? ", decoded_json = :decoded_json" : "") .
                " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':error_message', $errorMessage);
        if ($decodedJson !== null) {
            $stmt->bindValue(':decoded_json', $decodedJson);
        }

        return $stmt->execute();
    }

    /**
     * Apdoroja konkretų įrašą su MeshCoreDecoder.
     */
    public function processRecord(int $id, MeshCoreDecoder $decoder): array
    {
        $markSql = "UPDATE {$this->table} SET processed_at = CURRENT_TIMESTAMP WHERE id = :id AND processed_at IS NULL";
        $mStmt = $this->pdo->prepare($markSql);
        $mStmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        if (!$record) {
            throw new \RuntimeException("Įrašas ID #{$id} nerastas duomenų bazėje.");
        }

        $rawHex = $record['raw_hex'];
        $secret = $record['shared_secret_hex'];

        $payload = null;
        if (!empty($record['payload_json'])) {
            $payload = json_decode($record['payload_json'], true);
        }

        try {
            if (is_array($payload)) {
                $decoded = $decoder->decodeApiPayload($payload);
            } else {
                $decoded = $decoder->decodeRawPacket($rawHex, $secret, $record['my_public_key'] ?? null);
            }

            if (!empty($decoded['advert']) && is_array($decoded['advert']) && !empty($decoded['advert']['public_key'])) {
                if ($this->nodeRepo !== null) {
                    $recordWithMeta = is_array($record) ? $record : [];
                    if (!empty($decoded['path'])) {
                        $recordWithMeta['path'] = $decoded['path'];
                    }
                    $this->nodeRepo->upsertNodeFromAdvert($decoded['advert'], $recordWithMeta);
                }
            }

            $savedTelemetryId = null;
            if ($this->telemetryRepo !== null) {
                $savedTelemetryId = $this->telemetryRepo->extractAndSaveTelemetry($id, $decoded, $record);
            }

            $originalSenderPubKey = !empty($record['sender_public_key']) ? (string)$record['sender_public_key'] : null;
            $originalSenderName   = !empty($record['sender_name']) ? (string)$record['sender_name'] : null;

            $endpoints = $this->resolvePacketEndpoints($decoded, is_array($record) ? $record : []);
            $this->updatePacketEndpointsInDb($id, $endpoints);
            if (empty($record['sender_name']) && !empty($endpoints['sender_name'])) {
                $record['sender_name'] = $endpoints['sender_name'];
            }
            if (empty($record['sender_public_key']) && !empty($endpoints['sender_public_key'])) {
                $record['sender_public_key'] = $endpoints['sender_public_key'];
            }
            if (empty($record['target_name']) && !empty($endpoints['target_name'])) {
                $record['target_name'] = $endpoints['target_name'];
            }
            if (empty($record['target_public_key']) && !empty($endpoints['target_public_key'])) {
                $record['target_public_key'] = $endpoints['target_public_key'];
            }

            if ($savedTelemetryId === null && empty($decoded['advert']) && !empty($record['direction']) && strtoupper((string)$record['direction']) === 'RX') {
                // SVARBU: touchNodeSeen ir maršruto (returnPath) atnaujinimą vykdome TIK tada, kai
                // siuntėjas buvo tiksliai žinomas (iš boto arba iš ANON_REQ sender_public_key), o NE atspėtas iš hops[0]!
                $senderKey = $originalSenderPubKey
                    ?: (!empty($decoded['encryption']['sender_public_key'])
                        ? (string)$decoded['encryption']['sender_public_key']
                        : $originalSenderName);
                if ($senderKey && $this->nodeRepo !== null) {
                    $bId = !empty($record['bot_id']) ? (string)$record['bot_id'] : 'KLP';
                    $r = isset($record['rssi']) ? (float)$record['rssi'] : null;
                    $s = isset($record['snr']) ? (float)$record['snr'] : null;

                    $returnPath = $this->nodeRepo->extractReturnPathString($decoded, $r, $s);

                    $this->nodeRepo->touchNodeSeen($senderKey, $bId, null, null, $r, $s, false, null, $returnPath);
                }
            }

            $errorMsg = null;
            if (!empty($decoded['error'])) {
                $errorMsg = (string)$decoded['error'];
            } elseif (!empty($decoded['encryption']['decryption_error'])) {
                $errorMsg = (string)$decoded['encryption']['decryption_error'];
            } elseif (!empty($decoded['advert']['error'])) {
                $errorMsg = (string)$decoded['advert']['error'];
            }

            if (!empty($decoded['success'])) {
                $this->completeProcessing($id, $decoded, $errorMsg);
            } else {
                $err = $errorMsg ?: 'Nepavyko iškoduoti paketo';
                $this->failProcessing($id, $err, $decoded);
            }

            return $decoded;
        } catch (\Throwable $e) {
            $this->failProcessing($id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sinchronizuoja ir atnaujina 'error_message' stulpelį esamiems įrašams.
     */
    public function syncErrorsFromDecodedJson(int $limit = 500): int
    {
        $count = 0;
        try {
            $sql = "SELECT id, decoded_json, error_message FROM {$this->table} 
                    WHERE decoded_json IS NOT NULL 
                    ORDER BY id DESC LIMIT " . (int)$limit;
            $stmt = $this->pdo->query($sql);
            $updateStmt = $this->pdo->prepare("UPDATE {$this->table} SET error_message = :err WHERE id = :id");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dec = is_string($row['decoded_json']) ? json_decode($row['decoded_json'], true) : $row['decoded_json'];
                if (!is_array($dec)) continue;

                $err = null;
                if (!empty($dec['error'])) {
                    $err = (string)$dec['error'];
                } elseif (!empty($dec['encryption']['decryption_error'])) {
                    $err = (string)$dec['encryption']['decryption_error'];
                } elseif (!empty($dec['advert']['error'])) {
                    $err = (string)$dec['advert']['error'];
                }

                if ($err !== null && (empty($row['error_message']) || $row['error_message'] !== $err)) {
                    $updateStmt->execute([':err' => $err, ':id' => $row['id']]);
                    $count++;
                }
            }
        } catch (\Throwable $e) {}
        return $count;
    }

    /**
     * Paima sekantį prioritetinį įrašą iš eilės ir jį apdoroja.
     */
    public function processNext(MeshCoreDecoder $decoder): ?array
    {
        $item = $this->fetchNextForProcessing();
        if (!$item) {
            return null;
        }

        return $this->processRecord((int)$item['id'], $decoder);
    }

    public function updateDecodedJsonWithoutCompleting(int $id, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET decoded_json = :json WHERE id = :id");
        $stmt->execute([':id' => $id, ':json' => $json]);
    }

    /**
     * Grąžina bendrą įrašų ir eilių statistiką.
     */
    public function getStats(): array
    {
        $cacheFile = sys_get_temp_dir() . '/meshcore_raw_stats_' . md5($this->table) . '.json';
        if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < 60) {
            $cached = @json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['total_count'])) {
                // Tik greitai (< 1 ms per indeksą idx_raw_packets_queue) atnaujiname processed = 0 eilutę:
                try {
                    $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                    $unprocCond = ($driver === 'pgsql') ? 'processed = false' : 'processed = 0';
                    $qStmt = $this->pdo->query("
                        SELECT 
                            COALESCE(SUM(CASE WHEN processed_at IS NULL THEN 1 ELSE 0 END), 0) AS pending_never_processed,
                            COALESCE(SUM(CASE WHEN processed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS in_progress_or_retry
                        FROM {$this->table}
                        WHERE {$unprocCond}
                    ");
                    if ($qRow = $qStmt->fetch(PDO::FETCH_ASSOC)) {
                        $p1 = (int)$qRow['pending_never_processed'];
                        $p2 = (int)$qRow['in_progress_or_retry'];
                        $cached['pending_never_processed'] = $p1;
                        $cached['in_progress_or_retry'] = $p2;
                        $cached['pending'] = $p1 + $p2;
                    }
                } catch (\Throwable $e) {}
                return $cached;
            }
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $unprocCond = ($driver === 'pgsql') ? 'processed = false' : 'processed = 0';

            // 1. Žaibiška užklausa per idx_raw_packets_queue indeksą (tik neapdoroti paketai, < 1 ms)
            $p1 = 0;
            $p2 = 0;
            $qStmt = $this->pdo->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN processed_at IS NULL THEN 1 ELSE 0 END), 0) AS pending_never_processed,
                    COALESCE(SUM(CASE WHEN processed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS in_progress_or_retry
                FROM {$this->table}
                WHERE {$unprocCond}
            ");
            if ($qRow = $qStmt->fetch(PDO::FETCH_ASSOC)) {
                $p1 = (int)$qRow['pending_never_processed'];
                $p2 = (int)$qRow['in_progress_or_retry'];
            }

            // 2. Bendras įrašų skaičius per siauriausią indeksą (neskaitant LONGTEXT/JSON duomenų puslapių!)
            $totalStmt = $this->pdo->query("SELECT COUNT(id) FROM {$this->table}");
            $total = (int)$totalStmt->fetchColumn();
            $completed = max(0, $total - $p1 - $p2);

            $result = [
                'total_count'             => $total,
                'total'                   => $total,
                'pending_never_processed' => $p1,
                'in_progress_or_retry'    => $p2,
                'pending'                 => $p1 + $p2,
                'completed_count'         => $completed,
                'error_count'             => 0,
                'errors'                  => 0,
            ];

            @file_put_contents($cacheFile, json_encode($result));
            return $result;
        } catch (\Throwable $e) {
            return [
                'total_count'             => 0,
                'total'                   => 0,
                'pending_never_processed' => 0,
                'in_progress_or_retry'    => 0,
                'pending'                 => 0,
                'completed_count'         => 0,
                'error_count'             => 0,
                'errors'                  => 0,
            ];
        }
    }

    /**
     * Grąžina filtruotus raw_packets įrašus debugginimui.
     */
    public function getFilteredRawPackets(array $filters = [], int $limit = 50, int $offset = 0, ?string $sortBy = null, ?string $sortDir = 'DESC'): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['node'])) {
            $n = '%' . trim(strtolower($filters['node'])) . '%';
            $where[] = "(LOWER(rp.target_name) LIKE :node_search1 
                         OR LOWER(rp.target_public_key) LIKE :node_search2 
                         OR LOWER(rp.sender_name) LIKE :node_search3 
                         OR LOWER(rp.sender_public_key) LIKE :node_search4
                         OR EXISTS (SELECT 1 FROM nodes n WHERE ((n.public_key = rp.sender_public_key AND n.public_key != '') OR (n.public_key = rp.target_public_key AND n.public_key != '') OR (n.node_id = rp.sender_name AND n.node_id != '') OR (n.node_id = rp.target_name AND n.node_id != '')) AND LOWER(n.node_name) LIKE :node_search5))";
            $params[':node_search1'] = $n;
            $params[':node_search2'] = $n;
            $params[':node_search3'] = $n;
            $params[':node_search4'] = $n;
            $params[':node_search5'] = $n;
        }

        if (!empty($filters['direction'])) {
            $where[] = "rp.direction = :direction";
            $params[':direction'] = strtoupper(trim($filters['direction']));
        }

        if (isset($filters['packet_type']) && $filters['packet_type'] !== '' && is_numeric($filters['packet_type'])) {
            $pt = (int)$filters['packet_type'];
            if ($pt >= 0 && $pt <= 15) {
                $base = ($pt << 2);
                $params[':pt0'] = sprintf('%02X', $base | 0);
                $params[':pt1'] = sprintf('%02X', $base | 1);
                $params[':pt2'] = sprintf('%02X', $base | 2);
                $params[':pt3'] = sprintf('%02X', $base | 3);
                $where[] = "UPPER(SUBSTRING(rp.raw_hex, 1, 2)) IN (:pt0, :pt1, :pt2, :pt3)";
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'errors') {
                $where[] = "(rp.error_message IS NOT NULL AND rp.error_message != '')";
            } elseif ($filters['status'] === 'pending') {
                $where[] = "(rp.processed = 0 OR rp.processed = false)";
            } elseif ($filters['status'] === 'processed') {
                $where[] = "(rp.processed = 1 OR rp.processed = true)";
            }
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim(strtolower($filters['search'])) . '%';
            $where[] = "(LOWER(rp.raw_hex) LIKE :text_search1 
                         OR LOWER(rp.target_name) LIKE :text_search2 
                         OR LOWER(rp.sender_name) LIKE :text_search3 
                         OR LOWER(rp.error_message) LIKE :text_search4
                         OR EXISTS (SELECT 1 FROM nodes n WHERE ((n.public_key = rp.sender_public_key AND n.public_key != '') OR (n.public_key = rp.target_public_key AND n.public_key != '') OR (n.node_id = rp.sender_name AND n.node_id != '') OR (n.node_id = rp.target_name AND n.node_id != '')) AND LOWER(n.node_name) LIKE :text_search5))";
            $params[':text_search1'] = $s;
            $params[':text_search2'] = $s;
            $params[':text_search3'] = $s;
            $params[':text_search4'] = $s;
            $params[':text_search5'] = $s;
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) FROM {$this->table} rp {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();

        $allowedCols = [
            'id'            => 'rp.id',
            'bot_id'        => 'rp.bot_id',
            'direction'     => 'rp.direction',
            'sender'        => 'rp.sender_name',
            'sender_name'   => 'rp.sender_name',
            'target'        => 'rp.target_name',
            'target_name'   => 'rp.target_name',
            'length'        => 'rp.length',
            'rssi'          => 'rp.rssi',
            'snr'           => 'rp.snr',
            'status'        => 'rp.processed',
            'processed'     => 'rp.processed',
            'error'         => 'rp.error_message',
            'error_message' => 'rp.error_message',
            'created_at'    => 'rp.id',
        ];
        $orderCol = $allowedCols[$sortBy ?? ''] ?? 'rp.id';
        $orderDir = (strtoupper((string)$sortDir) === 'ASC') ? 'ASC' : 'DESC';

        $sql = "SELECT rp.id, rp.bot_id, rp.direction, rp.length, rp.rssi, rp.snr, 
                       rp.target_name, rp.target_public_key, 
                       rp.sender_name, rp.sender_public_key, 
                       rp.my_public_key, rp.raw_hex,
                       COALESCE(
                           (SELECT n1.node_name FROM nodes n1 WHERE (n1.public_key = rp.sender_public_key AND n1.public_key != '') OR (n1.node_id = rp.sender_name AND n1.node_id != '') ORDER BY (n1.public_key = rp.sender_public_key) DESC, n1.is_repeater DESC LIMIT 1),
                           rp.sender_name
                       ) AS sender_node_name,
                       COALESCE(
                           (SELECT n2.node_name FROM nodes n2 WHERE (n2.public_key = rp.target_public_key AND n2.public_key != '') OR (n2.node_id = rp.target_name AND n2.node_id != '') ORDER BY (n2.public_key = rp.target_public_key) DESC, n2.is_repeater DESC LIMIT 1),
                           rp.target_name
                       ) AS target_node_name,
                       rp.is_encrypted, rp.processed, rp.created_at, rp.processed_at, rp.completed_at, rp.error_message, 
                       SUBSTRING(rp.raw_hex, 1, 80) AS raw_hex_preview 
                FROM {$this->table} rp 
                {$whereSql} 
                ORDER BY {$orderCol} {$orderDir} 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $packets = $stmt->fetchAll() ?: [];

        $typeLabels = [
            0  => 'REQ',
            1  => 'RESPONSE',
            2  => 'TXT_MSG',
            3  => 'ACK',
            4  => 'ADVERT',
            5  => 'GRP_TXT',
            6  => 'GRP_DATA',
            7  => 'ANON_REQ',
            8  => 'PATH',
            9  => 'TRACE',
            10 => 'MULTIPART',
            11 => 'CONTROL',
            15 => 'RAW_CUSTOM',
        ];
        $routeLabels = [
            0 => 'TRANSPORT_FLOOD',
            1 => 'FLOOD',
            2 => 'DIRECT',
            3 => 'TRANSPORT_DIRECT',
        ];

        // Jei tarp grąžinamų įrašų yra jau apdorotų paketų be nustatyto sender/target –
        // iškart nustatome iš raw_hex ir išsaugome į DB, kad sąraše visiems paketams rodytų siuntėją
        $lazyDecoder = null;
        foreach ($packets as &$pkt) {
            $rawHexStr = (string)($pkt['raw_hex'] ?? ($pkt['raw_hex_preview'] ?? ''));
            if (strlen($rawHexStr) >= 2 && ctype_xdigit(substr($rawHexStr, 0, 2))) {
                $hdrByte = hexdec(substr($rawHexStr, 0, 2));
                $ptCode = ($hdrByte >> 2) & 0x0F;
                $rtCode = $hdrByte & 0x03;
                $pkt['packet_type_code'] = $ptCode;
                $pkt['packet_type'] = $typeLabels[$ptCode] ?? ("TYPE_{$ptCode}");
                $pkt['route_type_code'] = $rtCode;
                $pkt['route_type'] = $routeLabels[$rtCode] ?? 'FLOOD';
                $pathOffset = ($rtCode === 0 || $rtCode === 3) ? 10 : 2;
                if (strlen($rawHexStr) >= $pathOffset + 2 && ctype_xdigit(substr($rawHexStr, $pathOffset, 2))) {
                    $pathByte = hexdec(substr($rawHexStr, $pathOffset, 2));
                    $pkt['hop_count'] = $pathByte & 0x3F;
                } else {
                    $pkt['hop_count'] = null;
                }
            } else {
                $pkt['packet_type_code'] = null;
                $pkt['packet_type'] = null;
                $pkt['route_type_code'] = null;
                $pkt['route_type'] = null;
                $pkt['hop_count'] = null;
            }

            if (empty($pkt['sender_name']) && empty($pkt['sender_public_key']) && !empty($pkt['raw_hex'])) {
                if ($lazyDecoder === null) {
                    $lazyDecoder = new MeshCoreDecoder();
                }
                try {
                    $dec = $lazyDecoder->decodeRawPacket((string)$pkt['raw_hex'], null, $pkt['my_public_key'] ?? null);
                    $ep = $this->resolvePacketEndpoints($dec, $pkt);
                    if (!empty($ep['sender_name']) || !empty($ep['sender_public_key']) || !empty($ep['target_name']) || !empty($ep['target_public_key'])) {
                        $this->updatePacketEndpointsInDb((int)$pkt['id'], $ep);
                        if (empty($pkt['sender_name']) && !empty($ep['sender_name'])) {
                            $pkt['sender_name'] = $ep['sender_name'];
                        }
                        if (empty($pkt['sender_public_key']) && !empty($ep['sender_public_key'])) {
                            $pkt['sender_public_key'] = $ep['sender_public_key'];
                        }
                        if (empty($pkt['target_name']) && !empty($ep['target_name'])) {
                            $pkt['target_name'] = $ep['target_name'];
                        }
                        if (empty($pkt['target_public_key']) && !empty($ep['target_public_key'])) {
                            $pkt['target_public_key'] = $ep['target_public_key'];
                        }
                        if (!empty($pkt['sender_public_key']) || !empty($pkt['sender_name'])) {
                            $sNode = $this->resolveNodeByPrefixOrKey($pkt['sender_public_key'] ?: $pkt['sender_name'], $pkt['bot_id'] ?? null);
                            if ($sNode && !empty($sNode['node_name'])) {
                                $pkt['sender_node_name'] = $sNode['node_name'];
                            } elseif (empty($pkt['sender_node_name'])) {
                                $pkt['sender_node_name'] = $pkt['sender_name'];
                            }
                        }
                        if (!empty($pkt['target_public_key']) || !empty($pkt['target_name'])) {
                            $tNode = $this->resolveNodeByPrefixOrKey($pkt['target_public_key'] ?: $pkt['target_name'], $pkt['bot_id'] ?? null);
                            if ($tNode && !empty($tNode['node_name'])) {
                                $pkt['target_node_name'] = $tNode['node_name'];
                            } elseif (empty($pkt['target_node_name'])) {
                                $pkt['target_node_name'] = $pkt['target_name'];
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }
            unset($pkt['raw_hex'], $pkt['my_public_key']);
        }
        unset($pkt);

        return [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'packets' => $packets
        ];
    }

    /**
     * Grąžina pilną vieno raw_packet įrašo informaciją peržiūrai.
     */
    public function getRawPacketDetails(int $id): ?array
    {
        $table = $this->table;
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) return null;

            $row['sender_node_name'] = null;
            $row['target_node_name'] = null;
            if (!empty($row['sender_public_key']) || !empty($row['sender_name'])) {
                try {
                    $sStmt = $this->pdo->prepare("SELECT node_name FROM nodes WHERE (public_key = :pk AND public_key != '') OR (node_id = :name AND node_id != '') ORDER BY (public_key = :pk_exact) DESC, is_repeater DESC LIMIT 1");
                    $sStmt->execute([':pk' => (string)$row['sender_public_key'], ':name' => (string)$row['sender_name'], ':pk_exact' => (string)$row['sender_public_key']]);
                    $row['sender_node_name'] = $sStmt->fetchColumn() ?: null;
                } catch (\Throwable $e) {}
            }
            if (!empty($row['target_public_key']) || !empty($row['target_name'])) {
                try {
                    $tStmt = $this->pdo->prepare("SELECT node_name FROM nodes WHERE (public_key = :pk AND public_key != '') OR (node_id = :name AND node_id != '') ORDER BY (public_key = :pk_exact) DESC, is_repeater DESC LIMIT 1");
                    $tStmt->execute([':pk' => (string)$row['target_public_key'], ':name' => (string)$row['target_name'], ':pk_exact' => (string)$row['target_public_key']]);
                    $row['target_node_name'] = $tStmt->fetchColumn() ?: null;
                } catch (\Throwable $e) {}
            }

            $row['telemetry_records'] = [];
            try {
                $telStmt = $this->pdo->prepare("SELECT * FROM telemetry WHERE raw_packet_id = :id");
                $telStmt->execute([':id' => $id]);
                $row['telemetry_records'] = $telStmt->fetchAll() ?: [];
            } catch (\Throwable $ignored) {}

            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
