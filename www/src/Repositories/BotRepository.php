<?php

namespace MeshCore\Repositories;

use MeshCore\Contracts\BotRepositoryInterface;
use PDO;

/**
 * Class BotRepository
 *
 * Atsakinga už ESP32 botų (vartų / stočių) valdymą 'bots' lentelėje pagal SRP.
 */
class BotRepository implements BotRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Grąžina visų botų sąrašą
     */
    public function getAllBots(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM bots ORDER BY bot_id ASC");
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Grąžina visų botų sąrašą su priskirtų mazgų skaičiumi
     */
    public function getBotsWithNodeCounts(): array
    {
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $disabledCond = ($driver === 'pgsql') ? 'n.is_disabled = true' : '(n.is_disabled = 1 OR n.is_disabled = true)';

            $sql = <<<SQL
            SELECT 
                b.*,
                COUNT(n.id) AS total_nodes_count,
                SUM(CASE WHEN n.is_repeater = 1 OR n.is_repeater = true THEN 1 ELSE 0 END) AS repeaters_count,
                SUM(CASE WHEN NOT ({$disabledCond}) THEN 1 ELSE 0 END) AS active_nodes_count
            FROM bots b
            LEFT JOIN nodes n ON LOWER(b.bot_id) = LOWER(n.bot_id)
            GROUP BY b.id, b.bot_id, b.name, b.public_key, b.enabled, b.latitude, b.longitude, b.description, b.last_seen_at, b.created_at, b.updated_at
            ORDER BY b.bot_id ASC
SQL;
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return $this->getAllBots();
        }
    }

    /**
     * Suranda botą pagal bot_id (pvz. 'KLP')
     */
    public function getBotById(string $botId): ?array
    {
        $cleanId = strtoupper(substr(trim($botId), 0, 32));
        if (empty($cleanId)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM bots WHERE UPPER(bot_id) = :bot_id LIMIT 1");
            $stmt->execute([':bot_id' => $cleanId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Suranda botą pagal viešąjį raktą
     */
    public function getBotByPublicKey(string $publicKey): ?array
    {
        $cleanKey = strtolower(trim($publicKey));
        if (empty($cleanKey)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM bots WHERE LOWER(public_key) = :public_key LIMIT 1");
            $stmt->execute([':public_key' => $cleanKey]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Sukuria naują botą
     */
    public function createBot(array $data): bool
    {
        $botId = strtoupper(substr(trim((string)($data['bot_id'] ?? '')), 0, 32));
        if (empty($botId)) {
            return false;
        }

        $name = !empty($data['name']) ? trim((string)$data['name']) : "Botas {$botId}";
        $publicKey = !empty($data['public_key']) ? strtolower(trim((string)$data['public_key'])) : null;
        $enabled = isset($data['enabled']) ? (!empty($data['enabled']) ? 1 : 0) : 1;
        $lat = (isset($data['latitude']) && $data['latitude'] !== '' && $data['latitude'] !== null) ? (float)$data['latitude'] : null;
        $lon = (isset($data['longitude']) && $data['longitude'] !== '' && $data['longitude'] !== null) ? (float)$data['longitude'] : null;
        $description = !empty($data['description']) ? trim((string)$data['description']) : null;

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $enabledVal = ($driver === 'pgsql') ? ($enabled ? 'true' : 'false') : $enabled;

        $sql = <<<SQL
        INSERT INTO bots (bot_id, name, public_key, enabled, latitude, longitude, description, created_at)
        VALUES (:bot_id, :name, :public_key, :enabled, :latitude, :longitude, :description, CURRENT_TIMESTAMP)
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':bot_id', $botId);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':public_key', $publicKey);
            if ($driver === 'pgsql') {
                $stmt->bindValue(':enabled', $enabled ? true : false, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue(':enabled', $enabled, PDO::PARAM_INT);
            }
            $stmt->bindValue(':latitude', $lat);
            $stmt->bindValue(':longitude', $lon);
            $stmt->bindValue(':description', $description);

            return $stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Atnaujina boto informaciją
     */
    public function updateBot(string $botId, array $data): bool
    {
        $cleanId = strtoupper(substr(trim($botId), 0, 32));
        if (empty($cleanId)) {
            return false;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sets = [];
        $params = [':original_bot_id' => $cleanId];

        if (array_key_exists('new_bot_id', $data) && !empty($data['new_bot_id'])) {
            $newId = strtoupper(substr(trim((string)$data['new_bot_id']), 0, 32));
            if ($newId !== $cleanId) {
                $sets[] = "bot_id = :new_bot_id";
                $params[':new_bot_id'] = $newId;
            }
        }

        if (array_key_exists('name', $data)) {
            $sets[] = "name = :name";
            $params[':name'] = ($data['name'] !== null && $data['name'] !== '') ? trim((string)$data['name']) : null;
        }

        if (array_key_exists('public_key', $data)) {
            $sets[] = "public_key = :public_key";
            $params[':public_key'] = ($data['public_key'] !== null && $data['public_key'] !== '') ? strtolower(trim((string)$data['public_key'])) : null;
        }

        if (array_key_exists('enabled', $data)) {
            $en = !empty($data['enabled']) ? 1 : 0;
            if ($driver === 'pgsql') {
                $sets[] = "enabled = :enabled";
                $params[':enabled'] = $en ? 'true' : 'false';
            } else {
                $sets[] = "`enabled` = :enabled";
                $params[':enabled'] = $en;
            }
        }

        if (array_key_exists('latitude', $data)) {
            $sets[] = "latitude = :latitude";
            $params[':latitude'] = ($data['latitude'] !== null && $data['latitude'] !== '') ? (float)$data['latitude'] : null;
        }

        if (array_key_exists('longitude', $data)) {
            $sets[] = "longitude = :longitude";
            $params[':longitude'] = ($data['longitude'] !== null && $data['longitude'] !== '') ? (float)$data['longitude'] : null;
        }

        if (array_key_exists('description', $data)) {
            $sets[] = "description = :description";
            $params[':description'] = ($data['description'] !== null && $data['description'] !== '') ? trim((string)$data['description']) : null;
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = ($driver === 'pgsql') ? "updated_at = CURRENT_TIMESTAMP" : "`updated_at` = CURRENT_TIMESTAMP";

        $setString = implode(', ', $sets);
        $sql = "UPDATE bots SET {$setString} WHERE UPPER(bot_id) = :original_bot_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $res = $stmt->execute($params);

            // Jei pakeistas bot_id, atnaujiname ir mazguose
            $effectiveBotId = !empty($params[':new_bot_id']) ? $params[':new_bot_id'] : $cleanId;
            if ($res && !empty($params[':new_bot_id'])) {
                $nodeSql = "UPDATE nodes SET bot_id = :new_id WHERE UPPER(bot_id) = :old_id";
                $nStmt = $this->pdo->prepare($nodeSql);
                $nStmt->execute([':new_id' => $params[':new_bot_id'], ':old_id' => $cleanId]);
            }

            // Jei botas išjungtas (enabled = 0), perpriskiriame visus jo mazgus arčiausiems įjungtiems botams
            if ($res && array_key_exists('enabled', $data) && empty($data['enabled'])) {
                $this->reassignNodesFromDisabledBot($effectiveBotId);
            }

            return $res;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Pašalina botą
     */
    public function deleteBot(string $botId): bool
    {
        $cleanId = strtoupper(substr(trim($botId), 0, 32));
        if (empty($cleanId)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM bots WHERE UPPER(bot_id) = :bot_id");
            $res = $stmt->execute([':bot_id' => $cleanId]);
            if ($res) {
                $this->reassignNodesFromDisabledBot($cleanId);
            }
            return $res;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Perpriskiria visus išjungto arba pašalinto boto mazgus arčiausiems aktyviems (enabled = 1) botams pagal GPS.
     */
    public function reassignNodesFromDisabledBot(string $disabledBotId): int
    {
        $cleanId = strtoupper(substr(trim($disabledBotId), 0, 32));
        if ($cleanId === '') {
            return 0;
        }

        try {
            $allBots = $this->getAllBots();
            $enabledBots = array_values(array_filter($allBots, static fn(array $b): bool => !empty($b['enabled']) && strtoupper(trim((string)$b['bot_id'])) !== $cleanId));
            if (empty($enabledBots)) {
                return 0;
            }
            $fallbackBotId = strtoupper(substr(trim((string)$enabledBots[0]['bot_id']), 0, 16));

            $stmt = $this->pdo->prepare("SELECT id, latitude, longitude, last_path FROM nodes WHERE UPPER(bot_id) = :b_id");
            $stmt->execute([':b_id' => $cleanId]);
            $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (empty($nodes)) {
                return 0;
            }

            $updStmt = $this->pdo->prepare("UPDATE nodes SET bot_id = :new_bot, last_path = :new_path WHERE id = :id");
            $reassigned = 0;
            foreach ($nodes as $n) {
                $lat = ($n['latitude'] !== null) ? (float)$n['latitude'] : 0.0;
                $lon = ($n['longitude'] !== null) ? (float)$n['longitude'] : 0.0;
                $closest = (abs($lat) > 0.0001 || abs($lon) > 0.0001)
                    ? $this->findClosestBot($lat, $lon, true)
                    : null;
                $newBotId = ($closest && !empty($closest['bot_id']))
                    ? strtoupper(substr(trim((string)$closest['bot_id']), 0, 16))
                    : $fallbackBotId;
                $oldPath = ($n['last_path'] !== null) ? strtoupper(trim((string)$n['last_path'])) : null;
                $newPath = ($oldPath === 'FLOOD') ? 'FLOOD' : null;
                if ($updStmt->execute([':new_bot' => $newBotId, ':new_path' => $newPath, ':id' => $n['id']])) {
                    $reassigned++;
                }
            }
            return $reassigned;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Atnaujina boto paskutinio matymo laiką (last_seen_at)
     */
    public function touchBotSeen(string $botId): bool
    {
        $cleanId = strtoupper(substr(trim($botId), 0, 32));
        if (empty($cleanId)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE bots SET last_seen_at = CURRENT_TIMESTAMP WHERE UPPER(bot_id) = :bot_id");
            return $stmt->execute([':bot_id' => $cleanId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Patikrina ar botas yra aktyvus / įjungtas (enabled)
     */
    public function isBotEnabled(string $botId): bool
    {
        $cleanId = strtoupper(substr(trim($botId), 0, 32));
        if (empty($cleanId)) {
            return true; // jei bot_id nenurodytas, nelaikome blokuotu
        }

        try {
            $stmt = $this->pdo->prepare("SELECT enabled FROM bots WHERE UPPER(bot_id) = :bot_id LIMIT 1");
            $stmt->execute([':bot_id' => $cleanId]);
            $val = $stmt->fetchColumn();
            if ($val === false) {
                return true; // Jei boto nėra lentelėje, pagal nutylėjimą leidžiame
            }
            return (bool)$val;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Suranda botą, kurio GPS koordinatės yra arčiausiai nurodytų (pagal Haversine atstumą).
     * Jei koordinatės (0, 0) arba botų su koordinatėmis nėra, grąžina null.
     */
    public function findClosestBot(float $latitude, float $longitude, bool $onlyEnabled = true): ?array
    {
        if (abs($latitude) < 0.0001 && abs($longitude) < 0.0001) {
            return null;
        }

        $bots = $this->getAllBots();
        $closestBot = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($bots as $bot) {
            if ($onlyEnabled && empty($bot['enabled'])) {
                continue;
            }
            if ($bot['latitude'] === null || $bot['longitude'] === null) {
                continue;
            }
            $bLat = (float)$bot['latitude'];
            $bLon = (float)$bot['longitude'];
            if (abs($bLat) < 0.0001 && abs($bLon) < 0.0001) {
                continue;
            }

            // Haversine formulė (atstumas km)
            $earthRadius = 6371; // km
            $dLat = deg2rad($bLat - $latitude);
            $dLon = deg2rad($bLon - $longitude);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($latitude)) * cos(deg2rad($bLat)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestBot = $bot;
            }
        }

        return $closestBot;
    }
}
