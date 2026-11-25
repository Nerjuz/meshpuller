# Seeed Studio XIAO ESP32-S3 + Wio-SX1262 MeshCore Telemetry Bot

Pilna sistemos dokumentacija, veikimo principai, parametrų aprašymas ir duomenų formatai.

---

## 📋 Turinys

 1. [Projekto apžvalga](#1-projekto-ap%C5%BEvalga)
 2. [Failų struktūra ir paskirtis](#2-fail%C5%B3-strukt%C5%ABra-ir-paskirtis)
 3. [Konfigūracija (config.h)](#3-konfig%C5%ABracija-configh)
 4. [Dinaminis kartotuvų sąrašas (r.json)](#4-dinaminis-kartotuv%C5%B3-s%C4%85ra%C5%A1as-rjson)
 5. [Pagrindinis veikimo ciklas](#5-pagrindinis-veikimo-ciklas)
 6. [Nuosekli 4 žingsnių kartotuvo apklausa](#6-nuosekli-4-%C5%BEingsni%C5%B3-kartotuvo-apklausa)
 7. [Pasyvus ADVERT paketų klausymasis ir dekodavimas](#7-pasyvus-advert-paket%C5%B3-klausymasis-ir-dekodavimas)
 8. [API JSON duomenų formatai](#8-api-json-duomen%C5%B3-formatai)
 9. [MeshCore kriptografija ir tapatybė](#9-meshcore-kriptografija-ir-tapatyb%C4%97)
10. [Diagnostika ir problemų sprendimas](#10-diagnostika-ir-problem%C5%B3-sprendimas)

---

## 1. Projekto apžvalga

Šis projektas yra **MeshCore protokolo telemetrijos ir diagnostikos botas**, veikiantis **Seeed Studio XIAO ESP32-S3** mikrovaldiklyje su integruotu **Wio-SX1262 LoRa** radijo moduliu.

### Pagrindiniai uždaviniai:

- **Pasyvus stebėjimas:** Nuolatinis LoRa eterio klausymasis 869.618 MHz dažniu, priimant visų tinkle esančių mazgų ir kartotuvų **ADVERT** (skelbimų) paketus.
- **Aktyvi periodinė apklausa:** Kas nustatytą laiko tarpą (pvz. kas 60 min.) bot'as iš serverio parsisiunčia stebimų kartotuvų sąrašą ir kiekvienam paeiliui atlieka autorizuotą telemetrijos užklausą (baterija, temperatūra, ryšio kokybė, eterio apkrova).
- **Centralizuotas duomenų persiuntimas:** Visi iššifruoti ir apdoroti duomenys per Wi-Fi (HTTP POST) persiunčiami į telemetrijos serverį (`https://telemetry.ite.lt`).

---

## 2. Failų struktūra ir paskirtis

| Failas / Aplankas | Paskirtis |
| --- | --- |
| `XIAO_S3_Mesh_Bot.ino` | Pagrindinis programinės įrangos failas: valdiklio inicializavimas, `loop()` ciklas, paketų siuntimas/priėmimas, ADVERT dekodavimas, CayenneLPP ir RepeaterStats parsavimas, HTTP klientas. |
| `config.h` | Visi naudotojo konfigūraciniai nustatymai: Wi-Fi, API URL, laiko intervalai, LoRa dažnis, ESP32 raktai ir GPIO kontaktai. |
| `MeshCrypto.h` | MeshCore kriptografinės funkcijos: Ed25519 raktų porų valdymas, Curve25519 ECDH bendro paslapties rakto skaičiavimas, ChaCha20-Poly1305 šifravimas bei MAC tikrinimas. |
| `repeaters.json` | Vietinio atsarginio / testinio kartotuvų sąrašo šablonas (atitinka serverio `r.json` formatą). |
| `ed25519/` | C biblioteka Ed25519 / Curve25519 skaičiavimams. |
| `src/` | Pagalbinės kriptografijos priklausomybės (ChaCha, Poly1305). |

---

## 3. Konfigūracija (config.h)

Visi keičiami parametrai yra sutelkti faile `config.h`.

**MeshCore regionas.** Įjungus `ENABLE_SCOPE`, į `SCOPE_NAME` įrašykite viešo regiono
pavadinimą, pvz., `kun` arba `#kun` (abu lygiaverčiai). Regionas turi leisti FLOOD
persiuntimą maršruto kartotuvuose. Transporto kodas skaičiuojamas kiekvienam FLOOD
paketui; žinomam keliui naudojamas įprastas DIRECT maršrutas be transporto kodų.
Privatūs `$` regionai nepalaikomi. Suderinamumui su MeshCore palikite `SCOPE_CODE_1`
ir `SCOPE_CODE_2` lygius nuliui. Nenulinis `SCOPE_CODE_1` įjungia seną fiksuotų FLOOD
kodų režimą; `SCOPE_CODE_2` vienas neveikia.

Scope paketo baitus galima patikrinti kompiuteryje su C++ ir mbed TLS kūrimo
bibliotekomis. Komanda iš repozitorijos šakninio katalogo:

```sh
mkdir -p .tmp
c++ -std=c++11 -Wall -Wextra -Werror tests/meshcore_packet_scope.cpp -lmbedcrypto -o .tmp/meshcore_packet_scope
./.tmp/meshcore_packet_scope
```

### 3.1. Wi-Fi ir API nustatymai

```cpp
const char* WIFI_SSID     = "2.4GHz";
const char* WIFI_PASSWORD = "************";

// URL, į kurį siunčiami surinkti duomenys per HTTP POST
const char* API_URL       = "https://telemetry.ite.lt";
const char* API_AUTH_KEY  = ""; // Pvz. "Bearer TOKEN", jei serveris reikalauja

// URL, iš kurio prieš kiekvieną ciklą parsiunčiamas kartotuvų sąrašas
const char* REPEATERS_URL = "https://telemetry.ite.lt/r.json";
```

### 3.2. Ciklo ir laukimo parametrai

| Parametras | Reikšmė | Paskirtis |
| --- | --- | --- |
| `TELEMETRY_REQUEST_INTERVAL_MS` | `60000 * 60` (1 val.) | Kaip dažnai atliekamas kartotuvų apklausos ciklas. |
| `REPEATER_REPLY_TIMEOUT_MS` | `10000` (10 s) | Kiek laiko laukiama atsakymo iš kartotuvo po kiekvieno žingsnio. |
| `INTER_REQUEST_DELAY_MS` | `10000` (10 s) | Pauzė po būsenos gavimo prieš siunčiant telemetrijos užklausą. |
| `REPEATER_COOLDOWN_MS` | `10000` (10 s) | Pauzė po visų duomenų išsiuntimo į API prieš pradedant dirbti su kitu kartotuvu. |
| `MAX_MONITORED_REPEATERS` | `16` | Maksimalus kartotuvų kiekis atmintyje. |

### 3.3. ESP32 tapatybės raktai (Mūsų mazgas)

Mūsų ESP32 mazgas MeshCore tinkle turi unikalų Ed25519 raktų komplektą:

```cpp
const char* MY_SEED_HEX   = "4a9232cb075ea937e5ff59e14eb89b595acfc82e9ccf4d7347372bff56393e09";
const char* MY_PUBKEY_HEX = "0bbd84b028bdf755edd8f30c6f67839c3f81846c9f0079f377ea32a1e93410f3";
const char* MY_PRVKEY_HEX = "4a9232cb...0bbd84b0..."; // 64 baitų privatus raktas
```

Mazgo trumpasis adresas (Hash) apskaičiuojamas kaip pirmasis viešojo rakto baitas: `myNodeHash = myPublicKey[0]` = `0x0B`.

### 3.4. LoRa radijo parametrai (EU standartas)

```cpp
#define LORA_FREQ         869.618  // MHz (Oficialus MeshCore ES dažnis)
#define LORA_BW           62.5     // kHz
#define LORA_SF           8        // Spreading Factor (SF8)
#define LORA_CR           8        // Coding Rate 4/8
#define LORA_SYNC_WORD    0x12     // MeshCore / SX1262 Private Sync Word
#define LORA_TX_POWER     22       // dBm (maksimali galia)
#define LORA_PREAMBLE_LEN 16       // MeshCore standartas
```

### 3.5. Aparatūros kontaktai (Seeed XIAO ESP32-S3 + Wio-SX1262)

```cpp
#define PIN_LORA_NSS   41   // SPI CS
#define PIN_LORA_RESET 42   // Reset Pin
#define PIN_LORA_BUSY  40   // BUSY Pin
#define PIN_LORA_DIO1  39   // DIO1 Pertrauktis (RX/TX indikacija)
#define PIN_LORA_RXEN  38   // RF jungiklio valdymas (HIGH = RX, LOW = TX)
```

---

## 4. Dinaminis kartotuvų sąrašas (r.json)

Prieš kiekvieną apklausos ciklą ESP32 atlieka HTTP GET užklausą į `REPEATERS_URL` (`https://telemetry.ite.lt/r.json`).

### JSON formatas:

```json
[
  {
    "name": "b9dd",
    "public_id": "b9ddd788b7a59eab40eaa49a2534fa9e7fc0ef7d920ef4811139998cddd631db",
    "password": "hello",
    "isDirect": true,
    "status": true,
    "telemetry": true
  },
  {
    "name": "1bbd",
    "public_id": "1bbd45c1ae2b2241ae997f0723066461bc8b0283abeee165d67ef9b35e9afb64",
    "password": "hello",
    "isDirect": false,
    "status": true,
    "telemetry": true
  }
]
```

### Laukų reikšmės:

- `name`: Trumpas kartotuvo vardas / žyma.
- `public_id`: 32 baitų (64 hex simbolių) kartotuvo Ed25519 viešasis raktas. Pirmasis baitas nustato kartotuvo `NodeHash` (pvz. `0xB9` arba `0x1B`).
- `password`: Kartotuvo administratoriaus slaptažodis, naudojamas šifruotai sesijai atidaryti.
- `isDirect`:
  - `true` – Tiesiogiai pasiekiamas kartotuvas (0 apynių / hops). Siunčiamas `PATH 0` registravimas ir `ROUTE_TYPE_DIRECT` užklausos.
  - `false` – Nutolęs kartotuvas, pasiekiamas per kitus kartotuvus (`ROUTE_TYPE_FLOOD`, praleidžiamas tiesioginis PATH registravimas).
- `status` *(neprivalomas, numatytoji reikšmė* `true`*)*:
  - `true` – Siųsti būsenos ir radijo statistikos užklausą (`REQ_TYPE_GET_STATUS`).
  - `false` – Praleisti būsenos užklausą.
- `telemetry` *(neprivalomas, numatytoji reikšmė* `true`*)*:
  - `true` – Siųsti CayenneLPP telemetrijos užklausą (`REQ_TYPE_GET_TELEMETRY_DATA` – temperatūrai ir jutikliams).
  - `false` – Praleisti telemetrijos užklausą.

> **Pastaba:** Jei įjungtos abi užklausos (`status: true` ir `telemetry: true`), tarp jų laukimo padaroma **10 sekundžių pauzė**, o gauti duomenys sujungiami į vieną bendrą JSON pranešimą, kuriame yra ir `status`, ir `telemetry`.

> **Apsauga:** Jei serveris nepasiekiamas, grąžina HTTP klaidą arba sąrašas tuščias (`[]`), bot'as saugiai nutraukia ciklą, neteršia eterio ir laukia kito periodo, toliau pasyviai klausydamasis ADVERT paketų.

---

## 5. Pagrindinis veikimo ciklas

```
                 +-----------------------------+
                 |          BOOT / SETUP       |
                 |  - Wi-Fi + NTP laikas       |
                 |  - SX1262 LoRa inicializav. |
                 |  - Sugeneruojamas Node ID   |
                 +--------------+--------------+
                                |
                                v
           +--------------------+--------------------+
           |                                         |
           v                                         v
+-----------------------+              +---------------------------+
|      LOOP: FONE       |              |  PERIODINIS CIKLAS        |
|  Nuolatinis klausym.  |              |  (Kas 60 minučių)         |
|  Gautas paketas?      |              +-------------+-------------+
|  --> ADVERT dekodav.  |                            |
|  --> Momentinis API   |                            v
+-----------------------+              +---------------------------+
                                       | Parsiunčiamas r.json      |
                                       | (Jei klaida -> laukti)    |
                                       +-------------+-------------+
                                                     |
                                                     v
                                       +---------------------------+
                                       | Apskaičiuojami ECDH       |
                                       | bendri raktai kartotuvams |
                                       +-------------+-------------+
                                                     |
                                                     v
                                       +---------------------------+
                                       | Nuosekli kartotuvų        |
                                       | apklausa (1 .. N)         |
                                       | - 4 žingsniai             |
                                       | - Konsoliduotas API POST  |
                                       | - 3s pauzė tarp mazgų     |
                                       +---------------------------+
```

---

## 6. Nuosekli 4 žingsnių kartotuvo apklausa

Kad nebūtų perkrautas ESP32 procesorius ir LoRa eteris, su kiekvienu kartotuvu dirbama **atskirai ir nuosekliai**:

```
ESP32 (Bot)                                        Kartotuvas (Repeater)
    |                                                         |
    | ----- 1. Užšifruotas Login (ANON_REQ su slaptažodžiu) -> |
    | <---- Patvirtinimas (RESP_SERVER_LOGIN_OK) ------------ |
    |                                                         |
    | ----- 2. PATH registravimas (Tik jei isDirect == true) ->|
    |                                                         |
    | ----- 3. Būsenos užklausa (REQ_TYPE_GET_STATUS) -------> |
    | <---- RepeaterStats (52 baitų struktūra) -------------- |
    |                                                         |
    | ----- 4. Telemetrijos užklausa (GET_TELEMETRY_DATA) ---> |
    | <---- CayenneLPP jutiklių duomenys (Baterija, Temp) --- |
    |                                                         |
[Duomenys konsoliduojami į currentRepDoc]                      |
    |                                                         |
    | =====> HTTP POST į API (Tik su tikrais duomenimis)       |
    |                                                         |
[Pauzė 3s prieš sekantį kartotuvą]
```

### Žingsnių detalės:

1. **Prisijungimas (**`sendMeshCoreLogin`**):** Siunčiamas `PAYLOAD_TYPE_ANON_REQ` su kartotuvo Hash, mūsų mazgo viešuoju raktu ir ChaCha20 užšifruotu slaptažodžiu bei `timestamp`. Sėkmingai prisijungus gausime `RESP_SERVER_LOGIN_OK`. Tarpinis pranešimas į API **nėra siunčiamas**.
2. **Kelio registracija (**`sendMeshCorePath`**):** Jei `isDirect == true`, kartotuvui išsiunčiamas 0 apynių kelias, kad jis žinotų, jog esame tiesioginiame radijo ryšyje.
3. **Statistikos užklausa (**`sendMeshCoreStatusRequest`**):** Užklausa `REQ_TYPE_GET_STATUS` (0x01). Kartotuvas atsako binarine `RepeaterStats` struktūra:
   - `batt_milli_volts`: Baterijos įtampa mV
   - `total_up_time_secs`: Veikimo laikas sekundėmis
   - `noise_floor`: Triukšmo lygis dBm
   - `total_air_time_secs`: Radijo siuntimo laikas
   - `n_packets_recv`, `n_packets_sent`: Gautų ir išsiųstų paketų skaitikliai
   - `n_recv_errors`, `curr_tx_queue_len`: Diagnostika
4. **Jutiklių telemetrijos užklausa (**`sendMeshCoreTelemetryRequest`**):** Užklausa `REQ_TYPE_GET_TELEMETRY_DATA` (0x03). Iššifruotame atsakyme skenuojami CayenneLPP telemetrijos laukai:
   - Tipas `0x02` arba `0x74`: Baterijos įtampa (0.01V žingsniu).
   - Tipas `0x67`: MCU temperatūra (°C, 0.1°C žingsniu).
5. **Siuntimas į API:** Tik pasibaigus visiems užklausų žingsniams, jei buvo surinkti tikri telemetrijos duomenys (`battery`, `uptime` ar `status`), suformuojamas vienas konsoliduotas JSON ir išsiunčiamas į `API_URL`.

---

## 7. Pasyvus ADVERT paketų klausymasis ir dekodavimas

Kai nevyksta aktyvus apklausos ciklas (arba laukiant atsakymo lango metu), radijas yra nuolatinio priėmimo režime (`radio.startReceive()`).

Gauti `PAYLOAD_TYPE_ADVERT` (0x04) paketai iškart iškoduojami ir išsiunčiami į API.

### 7.1. Dviejų ADVERT formatų palaikymas

MeshCore naudoja du ADVERT variantus:

- **Formatas A (su 64B parašu,** `payloadLen >= 101`**):**
  - `[0..31]` Viešasis raktas (32 baitai)
  - `[32..35]` Laiko žyma (timestamp, 4 baitai LE)
  - `[36..99]` Ed25519 parašas (64 baitai)
  - `[100]` `appFlags` (vėliavėlės)
  - `[101..104]` `lat` (int32 fixed-point)
  - `[105..108]` `lon` (int32 fixed-point)
  - `[109..]` Mazgo pavadinimas (UTF-8 tekstas)
- **Formatas B (be parašo,** `payloadLen < 101`**):**
  - `[0..31]` Viešasis raktas
  - `[32..35]` Laiko žyma
  - `[36]` `appFlags`
  - `[37..40]` `lat`, `[41..44]` `lon`
  - `[45..]` Mazgo pavadinimas

### 7.2. `appFlags` bitų reikšmės

- `bits[0-1]`: Mazgo tipas (`0` = generic, `1` = chat, `2` = repeater, `3` = room).
- `bit[2]`: `sensor` (jutiklis).
- `bit[3]`: `hasFeat1`.
- `bit[4]`: `hasLocation` (ar pakete yra GPS koordinatės).
- `bit[5]`: `hasFeat2`.
- `bit[7]`: `hasName` (ar pakete yra pavadinimas).

### 7.3. Koordinačių formatavimas

Koordinatės MeshCore protokole saugomos kaip fiksuoto kablelio sveikieji skaičiai (`int32_t`), padauginti iš $10^7$ (pvz. `55705207` atitinka `55.705207°`). Bot'as jas padalina iš `10000000.0` ir į JSON įrašo kaip **tekstines eilutes (string)**, išsaugodamas tikslumą:

```json
"lat": "55.705207",
"lon": "21.145874"
```

> **Pastaba dėl apklausos eigos:** Apdorojus ADVERT paketą aktyvaus laukimo metu, funkcija grąžina `false`. Tai užtikrina, kad `waitForReply()` nesustos laukti tikrojo kartotuvo atsakymo į siųstą užklausą.

---

## 8. API JSON duomenų formatai

### 8.1. Aktyvios apklausos telemetrijos JSON (POST į serverį)

```json
{
  "protocol": "MeshCore",
  "node_id": "b9dd",
  "public_id": "b9ddd788b7a59eab40eaa49a2534fa9e7fc0ef7d920ef4811139998cddd631db",
  "signal": {
    "rssi": -78.5,
    "snr": 9.2
  },
  "telemetry": {
    "battery": {
      "voltage_v": 4.12,
      "voltage_mv": 4120
    },
    "temperature_c": 21.4,
    "uptime_seconds": 184520,
    "uptime_hours": 51.25,
    "status": {
      "air_time_secs": 1420,
      "packets_recv": 8421,
      "packets_sent": 3120,
      "noise_floor": -118,
      "rx_errors": 14,
      "tx_queue": 0
    }
  }
}
```

### 8.2. Pasyviai pagauto ADVERT skelbimo JSON (POST į serverį)

```json
{
  "protocol": "MeshCore",
  "type": "ADVERT",
  "advert_format": "A",
  "public_key": "c7363db9291ef8803f8456c19352ae9a4d71ff357d244457ec651a11c32e65fa",
  "timestamp": 1790696885,
  "node_name": "LT-LM Tilžė OBS",
  "signal": {
    "rssi": -71.0,
    "snr": 12.0
  },
  "flags": {
    "raw": 147,
    "type": 3,
    "type_str": "room",
    "chat": false,
    "repeater": false,
    "room": true,
    "sensor": false,
    "hasFeat1": false,
    "hasLocation": true,
    "hasFeat2": false,
    "hasName": true
  },
  "lat": "55.705207",
  "raw_hex": "1200C7363DB9291EF8803F8456C19352AE9A4D71FF357D244457EC651A11C32E65FAB5DDBB6A306753E002C7A1ACC7EBF27FCCBC48E943788BFAEF9DC435A61A89E0F303427C452B8C331CEE3B00F6C2D8B329FE40E9146FE5DC5A13996049D0A271E15870039300000000000000004C542D4C4D2054696CC5BEC497204F4253"
}
```

---

## 9. MeshCore kriptografija ir tapatybė

MeshCore tinkle naudojami modernūs asimetriniai ir simetriniai algoritmai:

### 9.1. Tapatybė ir raktai

- **Ed25519:** Kiekvienas mazgas turi 32 baitų viešąjį raktą.
- **NodeHash:** Mazgo adresas tinkle yra pirmasis viešojo rakto baitas (`pubKey[0]`).
- **ECDH (Elliptic Curve Diffie-Hellman):** Konvertavus Ed25519 raktus į Curve25519 koordinates, tarp dviejų mazgų apskaičiuojamas unikalus 32 baitų bendras šifravimo raktas (`sharedSecret`).

### 9.2. Simetrinis šifravimas (ChaCha20-Poly1305)

Visi privatūs duomenys (slaptažodžiai, užklausos, telemetrijos atsakai) šifruojami simetriniu ChaCha20 algoritmu, pridedant Poly1305 MAC autentifikacijos kodą.

### 9.3. Replay apsauga (Laiko žymos)

Kartotuvai atmeta užklausas su laiko žyma, mažesne arba lygia paskutinei matytai iš to paties mazgo. Funkcija `getNextTimestamp()`:

- Sinchronizuojama su NTP serveriais per Wi-Fi.
- Pridedamas poslinkis virš `1790700000UL`, garantuojant, kad užklausos nebus atmestos po ankstesnių testavimų.
- Užtikrinamas griežtas didėjimas (`ts > lastTxTimestamp`).

---

## 10. Diagnostika ir problemų sprendimas

### 10.1. Serial Monitor pranešimų žymos (115200 baud)

| Žyma | Reikšmė |
| --- | --- |
| `[Wi-Fi Skenavimas]` | Aplinkinių Wi-Fi tinklų skenavimas ir atvaizdavimas (SSID, RSSI, kanalas, šifravimas). |
| `[Wi-Fi]` / `[NTP]` | Ryšio su internetu ir realaus laiko sinchronizavimo būsena. |
| `[FetchRepeaters]` | Kartotuvų sąrašo parsiuntimas iš `r.json`. |
| `[LoRa TX]` | Siunčiamo radijo paketo tipas, dydis ir HEX baitai. |
| `[LoRa RX]` | Gautas radijo paketas, RSSI ir SNR stiprumas. |
| `[ADVERT]` | Sėkmingai iškoduotas ir į API išsiųstas skelbimas. |
| `[MeshCore SĖKMĖ]` | Sėkmingas Login patvirtinimas arba RepeaterStats gavimas. |
| `[CayenneLPP]` | Iškoduota jutiklio įtampa arba temperatūra. |
| `[API]` | Duomenų siuntimas per HTTP POST į serverį. |

### 10.2. Dažniausios problemos ir sprendimai

| Problema | Tikėtina priežastis | Sprendimas |
| --- | --- | --- |
| `RadioLib klaida -2` arba pakimba `initLoRa()` | Blogi GPIO kontaktai arba maitinimas | Patikrinkite `PIN_LORA_NSS` (41), `PIN_LORA_DIO1` (39), `PIN_LORA_BUSY` (40) ir ar Wio modulis gerai įstatytas į XIAO lizdą. |
| `LoRa TX KLAIDA` arba kartotuvas negirdi užklausų | RXEN kontaktas (Pin 38) | Wio-SX1262 reikalauja RF Switch valdymo: siuntimo metu Pin 38 privalo būti LOW, priėmimo metu – HIGH. Tai valdoma automatiškai `sendMeshCoreRaw()`. |
| Kartotuvas neatsako į Login (`RX Laukimas baigėsi`) | Neteisingas `password` arba `public_id` | Patikrinkite kartotuvo viešąjį raktą ir slaptažodį `r.json` faile. Taip pat patikrinkite, ar kartotuvo dažnis sutampa (869.618 MHz). |
| `JSON klaida parsiunčiant r.json` | Serverio formatas neatitinka | Užtikrinkite, kad `https://telemetry.ite.lt/r.json` grąžina validų JSON masyvą `[...]` su laukais `name`, `public_id`, `password`, `isDirect`. |
| ADVERT gaunamas, bet koordinatės tuščios | Mazgas nesiunčia GPS koordinačių | `flags.hasLocation` yra 0 arba baitai lygūs 0. Bot'as siunčia koordinates į JSON tik tada, kai jos yra validžios. |
