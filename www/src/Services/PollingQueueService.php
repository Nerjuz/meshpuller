<?php

namespace MeshCore\Services;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use MeshCore\Contracts\GeolocationServiceInterface;
use MeshCore\Contracts\PollingQueueServiceInterface;
use MeshCore\Contracts\SettingsRepositoryInterface;
use MeshCore\Services\GeolocationService;
use PDO;

/**
 * Class PollingQueueService
 *
 * Atsakinga už ESP32 kartotuvų telemetrijos užklausų eilės valdymą, botų koordinavimą ir bandymų apskaitą pagal SRP.
 */
class PollingQueueService implements PollingQueueServiceInterface
{
    private PDO $pdo;
    private ?SettingsRepositoryInterface $settingsRepo;
    private ?GeolocationServiceInterface $geoService;

    public function __construct(
        PDO $pdo,
        ?SettingsRepositoryInterface $settingsRepo = null,
        ?GeolocationServiceInterface $geoService = null
    ) {
        $this->pdo          = $pdo;
        $this->settingsRepo = $settingsRepo;
        $this->geoService   = $geoService;
    }

    public function setSettingsRepository(SettingsRepositoryInterface $settingsRepo): void
    {
        $this->settingsRepo = $settingsRepo;
    }

    public function setGeolocationService(GeolocationServiceInterface $geoService): void
    {
        $this->geoService = $geoService;
    }

    public function getGeolocationService(): ?GeolocationServiceInterface
    {
        if ($this->geoService === null && $this->settingsRepo !== null) {
            $this->geoService = new GeolocationService($this->settingsRepo, $this->pdo);
        }
        return $this->geoService;
    }

    private function getSetting(string $key, $default = null)
    {
        if ($this->settingsRepo !== null) {
            return $this->settingsRepo->getSetting($key, $default);
        }
        return $default;
    }

    /**
     * Patikrina, ar dabartinė Lietuvos valanda (0..23) patenka į boto aktyvumo langą [startHour..endHour).
     * Pvz.:
     * - start=23, end=6 -> aktyvus nuo 23:00 iki 05:59 (per vidurnaktį)
     * - start=1, end=5  -> aktyvus nuo 01:00 iki 04:59
     * - jei bent vienas NULL arba start === end -> aktyvus 24/7 (visą parą).
     */
    public function isWithinBotActiveHours(?int $startHour, ?int $endHour): bool
    {
        if ($startHour === null || $endHour === null || $startHour === $endHour) {
            return true;
        }
        try {
            $dt = new DateTime('now', new DateTimeZone('Europe/Vilnius'));
            $currentHour = (int)$dt->format('G');
        } catch (\Throwable $e) {
            $currentHour = (int)date('G');
        }

        if ($startHour < $endHour) {
            return ($currentHour >= $startHour && $currentHour < $endHour);
        }
        // Per vidurnaktį (pvz. nuo 23h iki 6h)
        return ($currentHour >= $startHour || $currentHour < $endHour);
    }

    /**
     * Nuskaito konkretaus boto apklausos politiką: ar įjungtas, ar šiuo metu patenka į darbo valandas,
     * koks yra jo individualus (arba globalus) telemetrijos intervalas bei pakartotinio bandymo po fail intervalas minutėmis.
     *
     * @return array{enabled: bool, within_active_hours: bool, telemetry_interval_mins: int, retry_interval_mins: int, active_hour_start: ?int, active_hour_end: ?int}
     */
    public function getBotPollingPolicy(string $botId): array
    {
        $globalInterval = (int)$this->getSetting('min_telemetry_interval_mins', 60);
        $globalRetryInterval = (int)$this->getSetting('min_retry_interval_mins', 60);
        $cleanBotId = strtoupper(substr(trim($botId), 0, 16));
        $policy = [
            'enabled'                 => true,
            'within_active_hours'     => true,
            'telemetry_interval_mins' => $globalInterval,
            'retry_interval_mins'     => $globalRetryInterval,
            'active_hour_start'       => null,
            'active_hour_end'         => null,
        ];

        if (!$this->pdo || $cleanBotId === '') {
            return $policy;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT enabled, telemetry_interval_mins, retry_interval_mins, active_hour_start, active_hour_end FROM bots WHERE UPPER(bot_id) = :b_id LIMIT 1");
            $stmt->execute([':b_id' => $cleanBotId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $policy['enabled'] = !empty($row['enabled']);
                if (isset($row['telemetry_interval_mins']) && $row['telemetry_interval_mins'] !== null && (int)$row['telemetry_interval_mins'] > 0) {
                    $policy['telemetry_interval_mins'] = (int)$row['telemetry_interval_mins'];
                }
                if (isset($row['retry_interval_mins']) && $row['retry_interval_mins'] !== null && (int)$row['retry_interval_mins'] > 0) {
                    $policy['retry_interval_mins'] = (int)$row['retry_interval_mins'];
                }
                $startH = (isset($row['active_hour_start']) && $row['active_hour_start'] !== null && $row['active_hour_start'] !== '')
                    ? (int)$row['active_hour_start']
                    : null;
                $endH = (isset($row['active_hour_end']) && $row['active_hour_end'] !== null && $row['active_hour_end'] !== '')
                    ? (int)$row['active_hour_end']
                    : null;
                $policy['active_hour_start'] = $startH;
                $policy['active_hour_end'] = $endH;
                $policy['within_active_hours'] = $this->isWithinBotActiveHours($startH, $endH);
            }
        } catch (\Throwable $e) {}

        return $policy;
    }

    /**
     * Grąžina SQL sąlygą Lietuvos geolokacijos filtravimui pagal sistemos nustatymus.
     * Jei filtras išjungtas, grąžina null.
     */
    private function getLithuaniaCondition(string $columnPrefix = '', string $driver = 'mysql'): ?string
    {
        $enabled = (bool)$this->getSetting('filter_lithuania_enabled', false);
        if (!$enabled) {
            return null;
        }

        $includeNoCoords = (bool)$this->getSetting('filter_lithuania_include_no_coords', false);
        $latMin = (float)$this->getSetting('lithuania_lat_min', 53.85);
        $latMax = (float)$this->getSetting('lithuania_lat_max', 56.45);
        $lonMin = (float)$this->getSetting('lithuania_lon_min', 20.90);
        $lonMax = (float)$this->getSetting('lithuania_lon_max', 26.85);

        if ($latMin > $latMax) {
            [$latMin, $latMax] = [$latMax, $latMin];
        }
        if ($lonMin > $lonMax) {
            [$lonMin, $lonMax] = [$lonMax, $lonMin];
        }

        if ($driver === 'pgsql') {
            $prefix = $columnPrefix ? rtrim($columnPrefix, '.') . '.' : '';
            $latCol = "{$prefix}latitude";
            $lonCol = "{$prefix}longitude";
            $absFunc = 'abs';
        } else {
            $prefix = $columnPrefix ? '`' . trim($columnPrefix, '` .') . '`.' : '';
            $latCol = "{$prefix}`latitude`";
            $lonCol = "{$prefix}`longitude`";
            $absFunc = 'ABS';
        }

        // Atmetame pietryčių Latvijos (Sēlija / Jēkabpils / Daugavpils / Nereta / Aknīste) kampą,
        // kuris patenka į stačiakampį lat <= 56.45, lon <= 26.85 šiauriau Rokiškio ir Zarasų:
        $notLatviaCorner = "NOT ({$latCol} > 56.15 AND {$lonCol} > 25.15) AND NOT ({$latCol} > 55.90 AND {$lonCol} > 26.00)";
        $inBounds = "({$latCol} >= {$latMin} AND {$latCol} <= {$latMax} AND {$lonCol} >= {$lonMin} AND {$lonCol} <= {$lonMax} AND {$notLatviaCorner})";
        $noCoords = "({$latCol} IS NULL OR {$lonCol} IS NULL OR ({$absFunc}({$latCol}) < 0.001 AND {$absFunc}({$lonCol}) < 0.001))";

        if ($includeNoCoords) {
            return "({$noCoords} OR {$inBounds})";
        } else {
            return "(NOT {$noCoords} AND {$inBounds})";
        }
    }

    /**
     * Nustato arba atnaujina mazgo slaptažodį ir 'update' būseną 'nodes' lentelėje.
     */
    public function setNodeMonitoring(string $publicKeyOrNodeId, ?string $password = null, ?bool $update = null): bool
    {
        if ($password === null && $update === null) {
            return false;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $colUpdate = ($driver === 'pgsql') ? '"update"' : '`update`';
        $colPass = ($driver === 'pgsql') ? 'password' : '`password`';

        $sets = [];
        $params = [];

        if ($password !== null) {
            $sets[] = "{$colPass} = :password";
            $params[':password'] = trim($password);
        }

        if ($update !== null) {
            $sets[] = "{$colUpdate} = :update";
            $params[':update'] = ($driver === 'pgsql') ? ($update ? 'true' : 'false') : ($update ? 1 : 0);
        }

        $setString = implode(', ', $sets);
        $cleanId = trim($publicKeyOrNodeId);

        $sql = "UPDATE nodes SET {$setString} WHERE LOWER(public_key) = LOWER(:id1) OR LOWER(node_id) = LOWER(:id2)";
        $params[':id1'] = $cleanId;
        $params[':id2'] = $cleanId;

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Grąžina stebimų kartotuvų sąrašą tiksliai ESP32 /api/repyters formatu.
     */
    public function getMonitoredRepeaters(?string $botId = null): array
    {
        if (empty($botId)) {
            return [];
        }

        $cleanBotId = strtoupper(substr(trim((string)$botId), 0, 16));
        $botPolicy = $this->getBotPollingPolicy($cleanBotId);
        if (!$botPolicy['enabled'] || !$botPolicy['within_active_hours']) {
            return []; // Botas išjungtas arba šiuo metu ne jo darbo valandos
        }

        $repeaters = [];
        $knownKeys = [];
        $driver = $this->pdo ? $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : null;
        $dbSuccess = false;

        if ($this->pdo) {
            try {
                $maxFails = (int)$this->getSetting('max_telemetry_fails', 20);
                $failFilterPgsql = ($maxFails > 0) ? " AND (COALESCE(telemetry_fail_count, 0) < {$maxFails})" : "";
                $failFilterMysql = ($maxFails > 0) ? " AND (COALESCE(`telemetry_fail_count`, 0) < {$maxFails})" : "";

                $sourceRoutingEnabled = (bool)$this->getSetting('enable_source_routing', true);
                $minIntervalMins = $botPolicy['telemetry_interval_mins'];
                $retryIntervalMins = $botPolicy['retry_interval_mins'];
                $successWaitSec = ($minIntervalMins > 0) ? ($minIntervalMins * 60) : 0;
                $retryWaitSec = ($retryIntervalMins > 0) ? max(180, $retryIntervalMins * 60) : 180;

                $intervalFilterPgsql = " AND (
                    requested_date IS NULL
                    OR (
                        telemetry_updated_at IS NOT NULL
                        AND telemetry_updated_at >= (requested_date - INTERVAL '30 seconds')
                        " . ($successWaitSec > 0 ? "AND requested_date <= (CURRENT_TIMESTAMP - INTERVAL '{$successWaitSec} seconds')" : "") . "
                    )
                    OR (
                        (telemetry_updated_at IS NULL OR telemetry_updated_at < (requested_date - INTERVAL '30 seconds'))
                        AND requested_date <= (CURRENT_TIMESTAMP - INTERVAL '{$retryWaitSec} seconds')
                    )
                )";
                if ($driver === 'sqlite') {
                    $intervalFilterMysql = " AND (
                        `requested_date` IS NULL
                        OR (
                            `telemetry_updated_at` IS NOT NULL
                            AND `telemetry_updated_at` >= datetime(`requested_date`, '-30 seconds')
                            " . ($successWaitSec > 0 ? "AND `requested_date` <= datetime('now', '-{$successWaitSec} seconds')" : "") . "
                        )
                        OR (
                            (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < datetime(`requested_date`, '-30 seconds'))
                            AND `requested_date` <= datetime('now', '-{$retryWaitSec} seconds')
                        )
                    )";
                } else {
                    $intervalFilterMysql = " AND (
                        `requested_date` IS NULL
                        OR (
                            `telemetry_updated_at` IS NOT NULL
                            AND `telemetry_updated_at` >= DATE_SUB(`requested_date`, INTERVAL 30 SECOND)
                            " . ($successWaitSec > 0 ? "AND `requested_date` <= DATE_SUB(NOW(), INTERVAL {$successWaitSec} SECOND)" : "") . "
                        )
                        OR (
                            (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < DATE_SUB(`requested_date`, INTERVAL 30 SECOND))
                            AND `requested_date` <= DATE_SUB(NOW(), INTERVAL {$retryWaitSec} SECOND)
                        )
                    )";
                }

                $botFilterPgsql = "";
                $botFilterMysql = "";
                $params = [];
                if (!empty($botId)) {
                    $cleanBotId = strtoupper(substr(trim((string)$botId), 0, 16));
                    $botFilterPgsql = " AND (bot_id = :bot_id)";
                    $botFilterMysql = " AND (`bot_id` = :bot_id)";
                    $params[':bot_id'] = $cleanBotId;
                }

                $geoCondPgsql = $this->getLithuaniaCondition('nodes.', 'pgsql');
                $geoFilterPgsql = $geoCondPgsql ? " AND {$geoCondPgsql}" : "";

                $geoCondMysql = $this->getLithuaniaCondition('nodes.', 'mysql');
                $geoFilterMysql = $geoCondMysql ? " AND {$geoCondMysql}" : "";

                if ($driver === 'pgsql') {
                    $sql = "SELECT public_key, node_id, node_name, is_repeater, password, \"update\", bot_id, last_path, telemetry_fail_count, latitude, longitude 
                            FROM nodes 
                            WHERE is_repeater = true AND \"update\" = true AND (is_disabled = false OR is_disabled IS NULL) {$failFilterPgsql} {$intervalFilterPgsql} {$botFilterPgsql} {$geoFilterPgsql}
                            ORDER BY last_seen_at DESC";
                } else {
                    $sql = "SELECT public_key, node_id, node_name, is_repeater, `password`, `update`, `bot_id`, `last_path`, `telemetry_fail_count`, `latitude`, `longitude` 
                            FROM `nodes` 
                            WHERE (`is_repeater` = 1 OR `is_repeater` = true) AND (`update` = 1 OR `update` = true) AND (`is_disabled` = 0 OR `is_disabled` = false OR `is_disabled` IS NULL) {$failFilterMysql} {$intervalFilterMysql} {$botFilterMysql} {$geoFilterMysql}
                            ORDER BY last_seen_at DESC";
                }

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (empty($row['public_key'])) {
                        continue;
                    }
                    $pubId = strtolower(trim($row['public_key']));
                    if (!isset($knownKeys[$pubId])) {
                        $knownKeys[$pubId] = true;
                        $shortName = !empty($row['node_id']) 
                            ? strtolower(trim($row['node_id'])) 
                            : substr($pubId, 0, 4);

                        $password = $row['password'] !== null
                            ? trim((string)$row['password'])
                            : null;

                        $rawPath = strtoupper(trim((string)($row['last_path'] ?? '')));
                        $isValidHexPath = ($rawPath !== '' && $rawPath !== 'FLOOD' && $rawPath !== 'DIRECT'
                            && strlen($rawPath) >= 4 && (strlen($rawPath) % 4 === 0)
                            && ctype_xdigit($rawPath));

                        if (!$sourceRoutingEnabled || $rawPath === 'FLOOD') {
                            $lastPath = 'FLOOD';
                            $isDirect = false;
                        } elseif ($rawPath === 'DIRECT') {
                            $lastPath = 'DIRECT';
                            $isDirect = true;
                        } elseif ($isValidHexPath) {
                            $lastPath = $rawPath;
                            $isDirect = false;
                        } else {
                            // Kai maršrutas DB yra NULL, grąžiname '', kad boto firmware (v9.4)
                            // nustatytų forceFlood = false ir galėtų išmokti DIRECT kelią jau iš Login atsakymo!
                            $lastPath = '';
                            $isDirect = false;
                        }

                        $rowBot = !empty($botId) ? $cleanBotId : strtoupper(trim((string)($row['bot_id'] ?? 'KLP')));
                        $rowFails = (int)($row['telemetry_fail_count'] ?? 0);
                        $skipLogin = $this->hasActiveBotSession($pubId, $shortName, $rowBot, $rowFails);

                        $repeaters[] = [
                            'name'       => $shortName,
                            'public_id'  => $pubId,
                            'password'   => $password,
                            'path'       => $lastPath,
                            'isDirect'   => $isDirect,
                            'login'      => !$skipLogin,
                            'skip_login' => $skipLogin,
                            'status'     => false,
                            'telemetry'  => true,
                            'bot_id'     => $row['bot_id'] ?? null,
                        ];
                    }
                }
                $dbSuccess = true;
            } catch (\Throwable $e) {
                $dbSuccess = false;
            }
        }

        if ($dbSuccess && !empty($repeaters)) {
            return $repeaters;
        }

        $hasNodesInDb = false;
        if ($dbSuccess && $this->pdo) {
            try {
                $count = (int)$this->pdo->query("SELECT COUNT(*) FROM `nodes`")->fetchColumn();
                if ($count > 0) {
                    $hasNodesInDb = true;
                }
            } catch (\Throwable $e) {}
        }

        if ($hasNodesInDb && $dbSuccess) {
            return [];
        }

        // Fallback: repeaters.json
        $filePaths = [
            __DIR__ . '/../../repeaters.json',
            __DIR__ . '/../../r.json',
            __DIR__ . '/../../../XIAO_S3_Mesh_Bot/repeaters.json'
        ];

        foreach ($filePaths as $path) {
            if (file_exists($path)) {
                $content = @file_get_contents($path);
                if ($content) {
                    $json = @json_decode($content, true);
                    if (is_array($json)) {
                        $list = isset($json['repeaters']) && is_array($json['repeaters']) ? $json['repeaters'] : $json;
                        foreach ($list as $item) {
                            if (!empty($item['public_id']) || !empty($item['public_key'])) {
                                $pubId = strtolower(trim($item['public_id'] ?? $item['public_key']));
                                if (!isset($knownKeys[$pubId])) {
                                    $knownKeys[$pubId] = true;
                                    $repeaters[] = [
                                        'name'      => (string)($item['name'] ?? substr($pubId, 0, 4)),
                                        'public_id' => $pubId,
                                        'password'  => array_key_exists('password', $item) && $item['password'] !== null ? trim((string)$item['password']) : null,
                                        'isDirect'  => isset($item['isDirect']) ? (bool)$item['isDirect'] : false,
                                        'status'    => isset($item['status']) ? (bool)$item['status'] : true,
                                        'telemetry' => isset($item['telemetry']) ? (bool)$item['telemetry'] : true,
                                    ];
                                }
                            }
                        }
                        if (!empty($repeaters)) {
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($repeaters) && (bool)$this->getSetting('filter_lithuania_enabled', false)) {
            $geoSvc = $this->getGeolocationService();
            if ($geoSvc) {
                $repeaters = $geoSvc->filterNodesByGeolocationSettings($repeaters);
            }
        }

        return $repeaters;
    }

    /**
     * Patikrina, ar kartotuvas jau turi aktyvią sesiją (ACL įrašą) su šiuo konkrečiu botu.
     * Sesija laikoma aktyvia, jei:
     * 1) Paskutinė užklausa nebuvo nesėkminga ($failCount === 0), IR
     * 2) Šis botas jau yra sėkmingai gavęs TELEMETRY arba STATUS atsakymą iš šio kartotuvo per pastarąsias 7 dienas
     *    (naudojamas tiesioginis indeksuotas palyginimas be LOWER()/UPPER() apvalkalų greitai paieškai).
     * Jei sesija aktyvi, botas gali praleisti Login (ANON_REQ) žingsnį ir iškart siųsti GET_TELEMETRY_DATA / GET_STATUS.
     */
    private function hasActiveBotSession(
        string $publicKey,
        string $nodeId,
        string $botId,
        int $failCount,
        ?string $lastFailedAt = null
    ): bool {
        if ($failCount > 0 || !$this->pdo || $botId === '') {
            return false;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $pk = strtolower(trim($publicKey));
            $nid = strtolower(trim($nodeId));
            $bId = strtoupper(trim($botId));

            if ($driver === 'pgsql') {
                $sql = "
                    SELECT 1
                    FROM telemetry
                    WHERE (public_key = :pk OR node_id = :nid)
                      AND UPPER(bot_id) = :bot_id
                      AND type IN ('TELEMETRY', 'STATUS')
                      AND recorded_at >= (CURRENT_TIMESTAMP - INTERVAL '7 days')
                ";
            } elseif ($driver === 'sqlite') {
                $sql = "
                    SELECT 1
                    FROM telemetry
                    WHERE (LOWER(public_key) = :pk OR LOWER(node_id) = :nid)
                      AND UPPER(bot_id) = :bot_id
                      AND type IN ('TELEMETRY', 'STATUS')
                      AND recorded_at >= datetime('now', '-7 days')
                ";
            } else {
                // MySQL utf8mb4_unicode_ci yra case-insensitive, todėl tiesioginis = naudoja B-Tree indeksus!
                $sql = "
                    SELECT 1
                    FROM `telemetry`
                    WHERE (`public_key` = :pk OR `node_id` = :nid)
                      AND `bot_id` = :bot_id
                      AND `type` IN ('TELEMETRY', 'STATUS')
                      AND `recorded_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ";
            }

            $params = [
                ':pk'     => $pk,
                ':nid'    => $nid,
                ':bot_id' => $bId,
            ];

            if ($lastFailedAt !== null && trim($lastFailedAt) !== '') {
                $sql .= " AND recorded_at > :last_failed";
                $params[':last_failed'] = trim($lastFailedAt);
            }

            $sql .= " LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Sinchronizuoja ir padidina nesėkmingų bandymų gauti telemetriją skaičių (telemetry_fail_count).
     * Jei išsaugotas maršrutas (last_path) neatsakė per nustatytą laiką, numeta jį į NULL (fallback į FLOOD).
     */
    public function syncFailedTelemetryAttempts(int $timeoutSeconds = 180): int
    {
        if (!$this->pdo) {
            return 0;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $timeout = (int)$timeoutSeconds;

        try {
            if ($driver === 'pgsql') {
                $sql = "UPDATE nodes
                SET 
                    telemetry_fail_count = telemetry_fail_count + 1,
                    last_failed_request_at = requested_date,
                    last_path = CASE 
                        WHEN UPPER(COALESCE(last_path, '')) = 'FLOOD' THEN 'FLOOD' 
                        WHEN COALESCE(telemetry_fail_count, 0) >= 1 THEN NULL 
                        ELSE last_path 
                    END
                WHERE requested_date IS NOT NULL
                  AND requested_date <= (CURRENT_TIMESTAMP - INTERVAL '{$timeout} seconds')
                  AND (telemetry_updated_at IS NULL OR telemetry_updated_at < requested_date)
                  AND (last_failed_request_at IS NULL OR last_failed_request_at < requested_date)";
                return (int)$this->pdo->exec($sql);
            }

            if ($driver === 'sqlite') {
                $sql = "UPDATE `nodes`
                SET 
                    `telemetry_fail_count` = `telemetry_fail_count` + 1,
                    `last_failed_request_at` = `requested_date`,
                    `last_path` = CASE 
                        WHEN UPPER(COALESCE(`last_path`, '')) = 'FLOOD' THEN 'FLOOD' 
                        WHEN COALESCE(`telemetry_fail_count`, 0) >= 1 THEN NULL 
                        ELSE `last_path` 
                    END
                WHERE `requested_date` IS NOT NULL
                  AND `requested_date` <= datetime('now', '-{$timeout} seconds')
                  AND (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < `requested_date`)
                  AND (`last_failed_request_at` IS NULL OR `last_failed_request_at` < `requested_date`)";
                return (int)$this->pdo->exec($sql);
            }

            $sql = "UPDATE `nodes`
            SET 
                `telemetry_fail_count` = `telemetry_fail_count` + 1,
                `last_failed_request_at` = `requested_date`,
                `last_path` = CASE 
                    WHEN UPPER(COALESCE(`last_path`, '')) = 'FLOOD' THEN 'FLOOD' 
                    WHEN COALESCE(`telemetry_fail_count`, 0) >= 1 THEN NULL 
                    ELSE `last_path` 
                END
            WHERE `requested_date` IS NOT NULL
              AND `requested_date` <= DATE_SUB(NOW(), INTERVAL {$timeout} SECOND)
              AND (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < `requested_date`)
              AND (`last_failed_request_at` IS NULL OR `last_failed_request_at` < `requested_date`)";
            return (int)$this->pdo->exec($sql);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Grąžina sekantį 1 stebimą kartotuvą, kurį botas turi užklausti.
     * Pirmiausia grąžina šiam botui priskirtus kartotuvus, o jei kitas botas nepasiekia
     * neužrakinto kartotuvo (>= 2 nesėkmės arba kitas botas išjungtas) – leidžia perimti užklausą (failover).
     */
    public function getNextRepeaterToRequest(?string $botId = null): ?array
    {
        if (empty($botId)) {
            return null;
        }

        $cleanBotId = strtoupper(substr(trim((string)$botId), 0, 16));

        $driver = $this->pdo ? $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : null;
        if (!$this->pdo) {
            return null;
        }

        // Patikriname ar šis botas yra įjungtas ir ar dabartinė valanda patenka į jo aktyvumo langą
        $botPolicy = $this->getBotPollingPolicy($cleanBotId);
        if (!$botPolicy['enabled'] || !$botPolicy['within_active_hours']) {
            return null; // Botas išjungtas arba šiuo metu ne jo nustatytos darbo valandos
        }

        $this->syncFailedTelemetryAttempts(180);

        $maxFails = (int)$this->getSetting('max_telemetry_fails', 20);
        $failFilterPgsql = ($maxFails > 0) ? " AND (COALESCE(telemetry_fail_count, 0) < {$maxFails})" : "";
        $failFilterMysql = ($maxFails > 0) ? " AND (COALESCE(`telemetry_fail_count`, 0) < {$maxFails})" : "";

        $minIntervalMins = $botPolicy['telemetry_interval_mins'];
        $retryIntervalMins = $botPolicy['retry_interval_mins'];
        $successWaitSec = ($minIntervalMins > 0) ? ($minIntervalMins * 60) : 180;
        $retryWaitSec = ($retryIntervalMins > 0) ? max(180, $retryIntervalMins * 60) : 180;

        // Jei paskutinis bandymas buvo SĖKMINGAS (telemetry_updated_at >= requested_date - 30s),
        // laukiame pagrindinio telemetrijos intervalo ($successWaitSec).
        // Jei paskutinis bandymas buvo NESĖKMINGAS (fail/timeout: telemetry_updated_at < requested_date - 30s),
        // laukiame pakartotinio bandymo po fail intervalo ($retryWaitSec).
        $timingFilterPgsql = " AND (
            requested_date IS NULL
            OR (
                telemetry_updated_at IS NOT NULL
                AND telemetry_updated_at >= (requested_date - INTERVAL '30 seconds')
                AND requested_date <= (CURRENT_TIMESTAMP - INTERVAL '{$successWaitSec} seconds')
            )
            OR (
                (telemetry_updated_at IS NULL OR telemetry_updated_at < (requested_date - INTERVAL '30 seconds'))
                AND requested_date <= (CURRENT_TIMESTAMP - INTERVAL '{$retryWaitSec} seconds')
            )
        )";

        if ($driver === 'sqlite') {
            $timingFilterMysql = " AND (
                `requested_date` IS NULL
                OR (
                    `telemetry_updated_at` IS NOT NULL
                    AND `telemetry_updated_at` >= datetime(`requested_date`, '-30 seconds')
                    AND `requested_date` <= datetime('now', '-{$successWaitSec} seconds')
                )
                OR (
                    (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < datetime(`requested_date`, '-30 seconds'))
                    AND `requested_date` <= datetime('now', '-{$retryWaitSec} seconds')
                )
            )";
        } else {
            $timingFilterMysql = " AND (
                `requested_date` IS NULL
                OR (
                    `telemetry_updated_at` IS NOT NULL
                    AND `telemetry_updated_at` >= DATE_SUB(`requested_date`, INTERVAL 30 SECOND)
                    AND `requested_date` <= DATE_SUB(NOW(), INTERVAL {$successWaitSec} SECOND)
                )
                OR (
                    (`telemetry_updated_at` IS NULL OR `telemetry_updated_at` < DATE_SUB(`requested_date`, INTERVAL 30 SECOND))
                    AND `requested_date` <= DATE_SUB(NOW(), INTERVAL {$retryWaitSec} SECOND)
                )
            )";
        }

        $botFilterPgsql = "";
        $botFilterMysql = "";
        $params = [];
        if (!empty($botId)) {
            $cleanBotId = strtoupper(substr(trim((string)$botId), 0, 16));
            $botFilterPgsql = " AND (
                UPPER(nodes.bot_id) = :bot_id
                OR (
                    (nodes.is_bot_locked = false OR nodes.is_bot_locked IS NULL)
                    AND EXISTS (
                        SELECT 1 FROM bots b 
                        WHERE UPPER(b.bot_id) = UPPER(nodes.bot_id) 
                          AND (b.enabled = false)
                    )
                )
            )";
            $botFilterMysql = " AND (
                UPPER(`nodes`.`bot_id`) = :bot_id
                OR (
                    (`nodes`.`is_bot_locked` = 0 OR `nodes`.`is_bot_locked` IS NULL)
                    AND EXISTS (
                        SELECT 1 FROM `bots` b 
                        WHERE UPPER(b.`bot_id`) = UPPER(`nodes`.`bot_id`) 
                          AND (b.`enabled` = 0 OR b.`enabled` = false)
                    )
                )
            )";
            $params[':bot_id'] = $cleanBotId;
            $params[':bot_id_order'] = $cleanBotId;
        }

        $geoCondPgsql = $this->getLithuaniaCondition('nodes.', 'pgsql');
        $geoFilterPgsql = $geoCondPgsql ? " AND {$geoCondPgsql}" : "";

        $geoCondMysql = $this->getLithuaniaCondition('nodes.', 'mysql');
        $geoFilterMysql = $geoCondMysql ? " AND {$geoCondMysql}" : "";

        $sourceRoutingEnabled = (bool)$this->getSetting('enable_source_routing', true);

        if ($driver === 'pgsql') {
            if (!empty($botId)) {
                $params[':bot_id_path'] = $cleanBotId;
            }
            $sql = <<<SQL
            WITH next_rep AS (
                SELECT id, public_key, node_id, node_name, password, requested_date, telemetry_updated_at, last_failed_request_at, bot_id, last_path
                FROM nodes
                WHERE (is_repeater = true) AND ("update" = true) AND (is_disabled = false OR is_disabled IS NULL) {$failFilterPgsql} {$timingFilterPgsql} {$botFilterPgsql} {$geoFilterPgsql}
                ORDER BY 
                    CASE WHEN UPPER(nodes.bot_id) = :bot_id_order THEN 0 ELSE 1 END ASC,
                    CASE WHEN nodes.last_seen_at >= (CURRENT_TIMESTAMP - INTERVAL '72 hours') THEN 0 ELSE 1 END ASC,
                    CASE WHEN requested_date IS NULL THEN 0 ELSE 1 END ASC,
                    requested_date ASC,
                    id ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED
            )
            UPDATE nodes
            SET 
                telemetry_fail_count = CASE 
                    WHEN next_rep.requested_date IS NOT NULL 
                         AND (next_rep.telemetry_updated_at IS NULL OR next_rep.telemetry_updated_at < next_rep.requested_date)
                         AND (next_rep.last_failed_request_at IS NULL OR next_rep.last_failed_request_at < next_rep.requested_date)
                    THEN nodes.telemetry_fail_count + 1 
                    ELSE nodes.telemetry_fail_count 
                END,
                last_failed_request_at = CASE 
                    WHEN next_rep.requested_date IS NOT NULL 
                         AND (next_rep.telemetry_updated_at IS NULL OR next_rep.telemetry_updated_at < next_rep.requested_date)
                         AND (next_rep.last_failed_request_at IS NULL OR next_rep.last_failed_request_at < next_rep.requested_date)
                    THEN next_rep.requested_date 
                    ELSE nodes.last_failed_request_at 
                END,
                last_path = CASE
                    WHEN UPPER(COALESCE(nodes.last_path, '')) = 'FLOOD' THEN 'FLOOD'
                    WHEN UPPER(COALESCE(next_rep.bot_id, '')) != :bot_id_path THEN NULL
                    WHEN next_rep.requested_date IS NOT NULL 
                         AND (next_rep.telemetry_updated_at IS NULL OR next_rep.telemetry_updated_at < next_rep.requested_date)
                         AND (next_rep.last_failed_request_at IS NULL OR next_rep.last_failed_request_at < next_rep.requested_date)
                    THEN NULL
                    ELSE nodes.last_path
                END,
                requested_date = CURRENT_TIMESTAMP
            FROM next_rep
            WHERE nodes.id = next_rep.id
            RETURNING nodes.public_key, nodes.node_id, nodes.node_name, nodes.password, nodes.requested_date, nodes.telemetry_fail_count, nodes.bot_id, nodes.last_path;
SQL;
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $pubId = strtolower(trim($row['public_key']));
                    $shortName = !empty($row['node_id']) 
                        ? strtolower(trim($row['node_id'])) 
                        : substr($pubId, 0, 4);
                    $password = $row['password'] !== null
                        ? trim((string)$row['password'])
                        : null;
                    $rawPath = strtoupper(trim((string)($row['last_path'] ?? '')));
                    $isValidHexPath = ($rawPath !== '' && $rawPath !== 'FLOOD' && $rawPath !== 'DIRECT'
                        && strlen($rawPath) >= 4 && (strlen($rawPath) % 4 === 0)
                        && ctype_xdigit($rawPath));

                    if (!$sourceRoutingEnabled || $rawPath === 'FLOOD') {
                        $lastPath = 'FLOOD';
                        $isDirect = false;
                    } elseif ($rawPath === 'DIRECT') {
                        $lastPath = 'DIRECT';
                        $isDirect = true;
                    } elseif ($isValidHexPath) {
                        $lastPath = $rawPath;
                        $isDirect = false;
                    } else {
                        $lastPath = '';
                        $isDirect = false;
                    }

                    $rowFails = (int)($row['telemetry_fail_count'] ?? 0);
                    $skipLogin = $this->hasActiveBotSession($pubId, $shortName, $cleanBotId, $rowFails);

                    return [
                        'name'                 => $shortName,
                        'public_id'            => $pubId,
                        'password'             => $password,
                        'path'                 => $lastPath,
                        'isDirect'             => $isDirect,
                        'login'                => !$skipLogin,
                        'skip_login'           => $skipLogin,
                        'status'               => false,
                        'telemetry'            => true,
                        'bot_id'               => $row['bot_id'] ?? null,
                        'requested_date'       => $row['requested_date'] ?? date('Y-m-d H:i:s'),
                        'telemetry_fail_count' => $rowFails,
                    ];
                }
                return null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        $aliveFirstOrder = ($driver === 'sqlite')
            ? "CASE WHEN `nodes`.`last_seen_at` >= datetime('now', '-72 hours') THEN 0 ELSE 1 END ASC,"
            : "CASE WHEN `nodes`.`last_seen_at` >= DATE_SUB(NOW(), INTERVAL 72 HOUR) THEN 0 ELSE 1 END ASC,";

        // MySQL / SQLite
        $this->pdo->beginTransaction();
        try {
            $forUpdate = ($driver === 'sqlite') ? '' : 'FOR UPDATE';
            $selectSql = <<<SQL
            SELECT id, public_key, node_id, node_name, `password`, `requested_date`, `telemetry_updated_at`, `last_failed_request_at`, `telemetry_fail_count`, `bot_id`, `last_path`
            FROM `nodes`
            WHERE (`is_repeater` = 1 OR `is_repeater` = true)
              AND (`update` = 1 OR `update` = true)
              AND (`is_disabled` = 0 OR `is_disabled` IS NULL)
              {$failFilterMysql}
              {$timingFilterMysql}
              {$botFilterMysql}
              {$geoFilterMysql}
            ORDER BY 
                CASE WHEN UPPER(`nodes`.`bot_id`) = :bot_id_order THEN 0 ELSE 1 END ASC,
                {$aliveFirstOrder}
                CASE WHEN `requested_date` IS NULL THEN 0 ELSE 1 END ASC,
                `requested_date` ASC,
                `id` ASC
            LIMIT 1
            {$forUpdate}
SQL;
            $stmt = $this->pdo->prepare($selectSql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $failCount = (int)($row['telemetry_fail_count'] ?? 0);
                $lastFailedAt = $row['last_failed_request_at'] ?? null;
                $currentPath = $row['last_path'] ?? null;
                $justFailed = false;

                if (!empty($row['requested_date'])) {
                    $rDate = $row['requested_date'];
                    $tDate = $row['telemetry_updated_at'] ?? null;
                    if (($tDate === null || $tDate < $rDate) && ($lastFailedAt === null || $lastFailedAt < $rDate)) {
                        $failCount++;
                        $lastFailedAt = $rDate;
                        $justFailed = true;
                    }
                }

                // Jei ankstesnės >= 2 užklausos iš eilės nepavyko arba kartotuvą perima kitas botas (failover) –
                // numetame maršrutą į NULL (fallback į FLOOD), nebent priverstinai nustatyta 'FLOOD'
                $rowBotId = strtoupper(trim((string)($row['bot_id'] ?? '')));
                if (strtoupper(trim((string)$currentPath)) !== 'FLOOD') {
                    if (($justFailed && $failCount >= 2) || ($rowBotId !== '' && $rowBotId !== $cleanBotId)) {
                        $currentPath = null;
                    }
                }

                $now = date('Y-m-d H:i:s');
                $updateSql = "UPDATE `nodes` 
                              SET `requested_date` = :now, 
                                  `telemetry_fail_count` = :fail_count, 
                                  `last_failed_request_at` = :last_failed,
                                  `last_path` = :last_path
                              WHERE `id` = :id";
                $uStmt = $this->pdo->prepare($updateSql);
                $uStmt->execute([
                    ':now'        => $now,
                    ':fail_count' => $failCount,
                    ':last_failed'=> $lastFailedAt,
                    ':last_path'  => $currentPath,
                    ':id'         => $row['id']
                ]);
                $this->pdo->commit();

                $pubId = strtolower(trim($row['public_key']));
                $shortName = !empty($row['node_id']) 
                    ? strtolower(trim($row['node_id'])) 
                    : substr($pubId, 0, 4);
                $password = $row['password'] !== null
                    ? trim((string)$row['password'])
                    : null;
                $rawPath = strtoupper(trim((string)($currentPath ?? '')));
                $isValidHexPath = ($rawPath !== '' && $rawPath !== 'FLOOD' && $rawPath !== 'DIRECT'
                    && strlen($rawPath) >= 4 && (strlen($rawPath) % 4 === 0)
                    && ctype_xdigit($rawPath));

                if (!$sourceRoutingEnabled || $rawPath === 'FLOOD') {
                    $lastPath = 'FLOOD';
                    $isDirect = false;
                } elseif ($rawPath === 'DIRECT') {
                    $lastPath = 'DIRECT';
                    $isDirect = true;
                } elseif ($isValidHexPath) {
                    $lastPath = $rawPath;
                    $isDirect = false;
                } else {
                    $lastPath = '';
                    $isDirect = false;
                }

                $skipLogin = $this->hasActiveBotSession($pubId, $shortName, $cleanBotId, $failCount, $lastFailedAt);

                return [
                    'name'                 => $shortName,
                    'public_id'            => $pubId,
                    'password'             => $password,
                    'path'                 => $lastPath,
                    'isDirect'             => $isDirect,
                    'login'                => !$skipLogin,
                    'skip_login'           => $skipLogin,
                    'status'               => false,
                    'telemetry'            => true,
                    'bot_id'               => $row['bot_id'] ?? null,
                    'requested_date'       => $now,
                    'telemetry_fail_count' => $failCount,
                ];
            }

            $this->pdo->commit();
            return null;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return null;
        }
    }

    /**
     * Grąžina kartotuvų telemetrijos užklausų eilę.
     */
    public function getNodePollingQueue(?string $botId = null, bool $monitoredOnly = true, ?string $search = null): array
    {
        if (!$this->pdo) {
            return [];
        }

        $this->syncFailedTelemetryAttempts(180);

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $updateCol = ($driver === 'pgsql') ? '"update"' : '`update`';
        $updateSelectCol = ($driver === 'pgsql') ? 'n."update"' : 'n.`update`';
        $repCol = ($driver === 'pgsql') ? 'is_repeater' : '`is_repeater`';

        $where = [];
        $params = [];

        $where[] = "(n.is_disabled = 0 OR n.is_disabled = false OR n.is_disabled IS NULL)";
        $where[] = "({$repCol} = 1 OR {$repCol} = true)";

        if ($monitoredOnly) {
            $where[] = "({$updateCol} = 1 OR {$updateCol} = true)";
        }

        if (!empty($botId)) {
            $where[] = "n.bot_id = :bot_id";
            $params[':bot_id'] = $botId;
        }

        if (!empty($search)) {
            $s = '%' . strtolower(trim($search)) . '%';
            $where[] = "(LOWER(n.node_name) LIKE :search1 OR LOWER(n.node_id) LIKE :search2 OR LOWER(n.public_key) LIKE :search3)";
            $params[':search1'] = $s;
            $params[':search2'] = $s;
            $params[':search3'] = $s;
        }

        if ($geoCond = $this->getLithuaniaCondition('n.', $driver)) {
            $where[] = $geoCond;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = <<<SQL
        SELECT 
            n.id,
            n.node_id,
            n.node_name,
            n.public_key,
            n.bot_id,
            n.is_repeater,
            {$updateSelectCol} AS `update`,
            n.is_disabled,
            n.latitude,
            n.longitude,
            n.battery_v AS advert_battery_v,
            n.temperature_c AS node_temperature_c,
            n.last_seen_at,
            n.requested_date,
            n.telemetry_updated_at,
            n.telemetry_fail_count,
            n.last_path,
            (
                SELECT t.battery_v 
                FROM telemetry t 
                WHERE (t.node_id = n.node_id OR (n.public_key IS NOT NULL AND t.public_key = n.public_key)) 
                  AND t.type IN ('TELEMETRY', 'STATUS')
                  AND t.battery_v IS NOT NULL 
                  AND t.battery_v >= 0.0 AND t.battery_v <= 15.0
                ORDER BY t.recorded_at DESC, t.id DESC 
                LIMIT 1
            ) AS last_battery_v,
            (
                SELECT t.temperature_c 
                FROM telemetry t 
                WHERE (t.node_id = n.node_id OR (n.public_key IS NOT NULL AND t.public_key = n.public_key)) 
                  AND t.type IN ('TELEMETRY', 'STATUS')
                  AND t.temperature_c IS NOT NULL 
                ORDER BY t.recorded_at DESC, t.id DESC 
                LIMIT 1
            ) AS last_temperature_c,
            (
                SELECT t.recorded_at 
                FROM telemetry t 
                WHERE (t.node_id = n.node_id OR (n.public_key IS NOT NULL AND t.public_key = n.public_key)) 
                  AND t.type IN ('TELEMETRY', 'STATUS')
                ORDER BY t.recorded_at DESC, t.id DESC 
                LIMIT 1
            ) AS last_telemetry_at,
            (
                SELECT 1
                FROM telemetry t
                WHERE (t.node_id = n.node_id OR (n.public_key IS NOT NULL AND t.public_key = n.public_key))
                  AND t.bot_id = COALESCE(NULLIF(n.bot_id, ''), 'KLP')
                  AND t.type IN ('TELEMETRY', 'STATUS')
                  AND (n.last_failed_request_at IS NULL OR t.recorded_at > n.last_failed_request_at)
                LIMIT 1
            ) AS has_bot_session
        FROM nodes n
        {$whereSql}
        ORDER BY 
            CASE WHEN n.requested_date IS NULL THEN 0 ELSE 1 END ASC,
            n.requested_date ASC,
            n.id ASC
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $result = [];
        $queueIndex = 1;
        $now = time();

        $tz = new DateTimeZone('Europe/Vilnius');
        $formatLt = function(?string $dateStr) use ($tz): ?string {
            if (!$dateStr) return null;
            try {
                $dt = new DateTime($dateStr, $tz);
                $dt->setTimezone($tz);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return $dateStr;
            }
        };

        $formatIsoLt = function(?string $dateStr) use ($tz): ?string {
            if (!$dateStr) return null;
            try {
                $dt = new DateTime($dateStr, $tz);
                $dt->setTimezone($tz);
                return $dt->format(DateTimeInterface::ATOM);
            } catch (\Throwable $e) {
                return $dateStr;
            }
        };

        $maxFails = (int)$this->getSetting('max_telemetry_fails', 20);
        $globalMinIntervalMins = (int)$this->getSetting('min_telemetry_interval_mins', 60);
        $globalRetryIntervalMins = (int)$this->getSetting('min_retry_interval_mins', 60);
        $botIntervals = [];
        $botRetryIntervals = [];
        try {
            $bStmt = $this->pdo->query("SELECT bot_id, telemetry_interval_mins, retry_interval_mins FROM bots");
            while ($bRow = $bStmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($bRow['bot_id'])) {
                    $bKey = strtoupper(trim((string)$bRow['bot_id']));
                    if (isset($bRow['telemetry_interval_mins']) && (int)$bRow['telemetry_interval_mins'] > 0) {
                        $botIntervals[$bKey] = (int)$bRow['telemetry_interval_mins'];
                    }
                    if (isset($bRow['retry_interval_mins']) && (int)$bRow['retry_interval_mins'] > 0) {
                        $botRetryIntervals[$bKey] = (int)$bRow['retry_interval_mins'];
                    }
                }
            }
        } catch (\Throwable $e) {}

        foreach ($rows as $row) {
            $nodeId = !empty($row['node_id']) 
                ? (string)$row['node_id'] 
                : (!empty($row['public_key']) ? substr($row['public_key'], 0, 4) : 'node');
            $name = !empty($row['node_name']) ? (string)$row['node_name'] : $nodeId;
            $bot = !empty($row['bot_id']) ? (string)$row['bot_id'] : 'KLP';

            $battV = ($row['last_battery_v'] !== null) ? (float)$row['last_battery_v'] : null;
            $tempC = ($row['last_temperature_c'] !== null) ? (float)$row['last_temperature_c'] : null;

            $rawReqDate = $row['requested_date'];
            $rawTelemDate = $row['last_telemetry_at'] ?? $row['telemetry_updated_at'];
            $hasPriorTelemetry = ($rawTelemDate !== null && trim((string)$rawTelemDate) !== '');

            $failCount = (int)($row['telemetry_fail_count'] ?? 0);
            $isBlocked = ($maxFails > 0 && $failCount >= $maxFails);

            $attemptStatus = 'never';
            $attemptLabel = '🚀 Prioritetas (Laukia 1-os užklausos)';
            $attemptColor = 'green';

            if ($isBlocked) {
                $attemptStatus = 'blocked';
                $attemptLabel = "⛔ Sustabdytas (viršytas limitas: {$failCount}/{$maxFails})";
                $attemptColor = 'red';
            } elseif ($rawReqDate !== null && trim($rawReqDate) !== '') {
                $reqTime = strtotime($rawReqDate);
                $elapsedReqSec = max(0, $now - $reqTime);
                $telemTime = $rawTelemDate ? strtotime($rawTelemDate) : 0;

                if ($telemTime >= ($reqTime - 30)) {
                    $attemptStatus = 'success';
                    $attemptLabel = '✅ Telemetrija gauta';
                    $attemptColor = 'green';
                } else {
                    if ($elapsedReqSec < 180) {
                        $attemptStatus = 'in_progress';
                        $attemptLabel = '⏳ Laukiama atsakymo...';
                        $attemptColor = 'blue';
                    } else {
                        $attemptStatus = 'timeout';
                        $failSuffix = ($failCount > 1) ? " ({$failCount}x)" : '';
                        $attemptLabel = $hasPriorTelemetry 
                            ? "❌ Negauta (Timeout{$failSuffix}) - Yra ankstesnė telemetrija"
                            : "❌ Negauta (Timeout{$failSuffix}) - Nėra telemetrijos";
                        $attemptColor = 'red';
                    }
                }
            }

            $botKey = strtoupper(trim($bot));
            $minIntervalMins = $botIntervals[$botKey] ?? $globalMinIntervalMins;
            $retryIntervalMins = $botRetryIntervals[$botKey] ?? $globalRetryIntervalMins;
            $activeIntervalMins = ($attemptStatus === 'timeout') ? $retryIntervalMins : $minIntervalMins;
            $requiredSec = ($attemptStatus === 'timeout')
                ? (($retryIntervalMins > 0) ? max(180, $retryIntervalMins * 60) : 180)
                : ($minIntervalMins * 60);

            $elapsedReqSec = ($rawReqDate !== null && trim((string)$rawReqDate) !== '') 
                ? max(0, $now - strtotime($rawReqDate)) 
                : null;

            $isRateLimited = false;
            $rateLimitRemainingMins = 0;
            $isRetryCooldown = false;
            $retryRemainingMins = 0;

            if ($activeIntervalMins > 0 && $elapsedReqSec !== null && $elapsedReqSec < $requiredSec) {
                $remainingMins = ceil(($requiredSec - $elapsedReqSec) / 60);
                if ($attemptStatus === 'success') {
                    $isRateLimited = true;
                    $rateLimitRemainingMins = $remainingMins;
                } elseif ($attemptStatus === 'timeout') {
                    $isRetryCooldown = true;
                    $retryRemainingMins = $remainingMins;
                }
            }

            $isInProgress = ($attemptStatus === 'in_progress');
            $hasWaitedInterval = ($elapsedReqSec === null || $elapsedReqSec >= $requiredSec);
            $isEligibleForNext = (!$isBlocked && !$isInProgress && $hasWaitedInterval);
            $queuePos = $isEligibleForNext ? $queueIndex++ : null;

            $skipLogin = ($failCount === 0 && !empty($row['has_bot_session']));

            $result[] = [
                'queue_pos'                => $queuePos,
                'id'                       => (int)$row['id'],
                'node_id'                  => $nodeId,
                'name'                     => $name,
                'public_key'               => $row['public_key'],
                'bot_id'                   => $bot,
                'skip_login'               => $skipLogin,
                'is_repeater'              => (bool)$row['is_repeater'],
                'update'                   => (bool)$row['update'],
                'is_disabled'              => (bool)($row['is_disabled'] ?? false),
                'requested_date'           => $formatLt($rawReqDate),
                'requested_date_iso'       => $formatIsoLt($rawReqDate),
                'telemetry_updated_at'     => $formatLt($rawTelemDate),
                'telemetry_updated_at_iso' => $formatIsoLt($rawTelemDate),
                'telemetry_fail_count'     => $failCount,
                'is_blocked'               => $isBlocked,
                'max_telemetry_fails'      => $maxFails,
                'is_rate_limited'          => $isRateLimited,
                'rate_limit_remaining_mins'=> $rateLimitRemainingMins,
                'min_interval_mins'        => $minIntervalMins,
                'is_retry_cooldown'        => $isRetryCooldown,
                'retry_remaining_mins'     => $retryRemainingMins,
                'min_retry_interval_mins'  => $minIntervalMins,
                'is_in_progress'           => $isInProgress,
                'elapsed_req_sec'          => $elapsedReqSec ?? null,
                'remaining_wait_sec'       => $isInProgress ? max(0, 180 - ($elapsedReqSec ?? 0)) : 0,
                'has_prior_telemetry'      => $hasPriorTelemetry,
                'last_seen_at'             => $formatLt($row['last_seen_at']),
                'last_seen_at_iso'         => $formatIsoLt($row['last_seen_at']),
                'battery_v'                => $battV,
                'temperature_c'            => $tempC,
                'attempt_status'           => $attemptStatus,
                'attempt_label'            => $attemptLabel,
                'attempt_color'            => $attemptColor,
                'last_path'                => $row['last_path'] ?? null,
                'path_display'             => empty($row['last_path']) 
                    ? 'FLOOD (visas tinklas)' 
                    : ($row['last_path'] === 'DIRECT' ? 'DIRECT (0 šuolių)' : 'DIRECT per [' . $row['last_path'] . ']'),
                'tz'                       => 'Europe/Vilnius',
            ];
        }

        return $result;
    }

    /**
     * Grąžina visus duomenų bazėje esančius unikalius bot_id šaltinius.
     */
    public function getAvailableBotSources(): array
    {
        if (!$this->pdo) {
            return [];
        }
        try {
            $stmt = $this->pdo->query("
                SELECT DISTINCT bot_id FROM (
                    SELECT bot_id FROM bots WHERE bot_id IS NOT NULL AND bot_id != ''
                    UNION
                    SELECT bot_id FROM nodes WHERE bot_id IS NOT NULL AND bot_id != ''
                ) combined_bots
                ORDER BY bot_id ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            try {
                $stmt = $this->pdo->query("SELECT DISTINCT bot_id FROM nodes WHERE bot_id IS NOT NULL AND bot_id != '' ORDER BY bot_id ASC");
                return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * Nustato requested_date = NULL konkrečiam mazgui.
     */
    public function resetNodeRequestedDate(string $identifier, bool $resetFailCount = true): bool
    {
        if (!$this->pdo) {
            return false;
        }
        try {
            $cleanId = trim($identifier);
            $failSql = $resetFailCount ? ", telemetry_fail_count = 0, last_failed_request_at = NULL" : "";
            $stmt = $this->pdo->prepare("UPDATE nodes SET requested_date = NULL{$failSql} WHERE LOWER(public_key) = LOWER(:id1) OR LOWER(node_id) = LOWER(:id2)");
            return $stmt->execute([':id1' => $cleanId, ':id2' => $cleanId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Nustato telemetry_fail_count = 0 ir pažymi last_failed_request_at = requested_date konkrečiam mazgui.
     */
    public function resetNodeFailCount(string $identifier): bool
    {
        if (!$this->pdo) {
            return false;
        }
        try {
            $cleanId = trim($identifier);
            $stmt = $this->pdo->prepare("UPDATE nodes SET telemetry_fail_count = 0, last_failed_request_at = requested_date WHERE LOWER(public_key) = LOWER(:id1) OR LOWER(node_id) = LOWER(:id2)");
            return $stmt->execute([':id1' => $cleanId, ':id2' => $cleanId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Nustato telemetry_fail_count = 0 ir pažymi last_failed_request_at = requested_date visiems mazgams.
     */
    public function resetAllFailCounts(?string $botId = null): int
    {
        if (!$this->pdo) {
            return 0;
        }
        try {
            $where = [];
            $params = [];
            if (!empty($botId)) {
                $where[] = "bot_id = :bot_id";
                $params[':bot_id'] = $botId;
            }
            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            $stmt = $this->pdo->prepare("UPDATE nodes SET telemetry_fail_count = 0, last_failed_request_at = requested_date {$whereSql}");
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Nustato requested_date = NULL visiems arba nurodyto boto mazgams.
     */
    public function resetAllRequestedDates(?string $botId = null, bool $monitoredOnly = true, bool $resetFailCount = false): int
    {
        if (!$this->pdo) {
            return 0;
        }
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $updateCol = ($driver === 'pgsql') ? '"update"' : '`update`';
            $repCol = ($driver === 'pgsql') ? 'is_repeater' : '`is_repeater`';

            $where = [];
            $params = [];

            if ($monitoredOnly) {
                $where[] = "({$updateCol} = 1 OR {$updateCol} = true)";
            } else {
                $where[] = "({$repCol} = 1 OR {$repCol} = true)";
            }

            if (!empty($botId)) {
                $where[] = "bot_id = :bot_id";
                $params[':bot_id'] = $botId;
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            $failSql = $resetFailCount ? ", telemetry_fail_count = 0, last_failed_request_at = NULL" : "";
            $stmt = $this->pdo->prepare("UPDATE nodes SET requested_date = NULL{$failSql} {$whereSql}");
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
