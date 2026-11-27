<?php

namespace MeshCore\Repositories;

use MeshCore\Contracts\SettingsRepositoryInterface;
use PDO;

/**
 * Class SettingsRepository
 *
 * Atsakinga už sistemos nustatymų saugojimą ir nuskaitymą pagal SRP.
 */
class SettingsRepository implements SettingsRepositoryInterface
{
    private PDO $pdo;

    public const DEFAULT_SYSTEM_SETTINGS = [
        'enable_source_routing'              => true,
        'filter_lithuania_enabled'           => false,
        'filter_lithuania_include_no_coords' => false,
        'lithuania_lat_min'                  => 53.85,
        'lithuania_lat_max'                  => 56.45,
        'lithuania_lon_min'                  => 20.90,
        'lithuania_lon_max'                  => 26.85,
        'max_telemetry_fails'                => 20,
        'auto_add_new_repeaters'             => false,
        'min_telemetry_interval_mins'        => 60,
        'min_retry_interval_mins'            => 60,
        'telemetry_chart_days'               => 7,
        'max_telemetry_age_days'             => 0,
        'api_cache_ttl_mins'                 => 5,
        'api_node_telemetry_limit'           => 50,
        'api_node_telemetry_days'            => 50,
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Grąžina visus sistemos nustatymus iš DB arba atsarginio failo
     */
    public function getAllSettings(): array
    {
        $settings = self::DEFAULT_SYSTEM_SETTINGS;

        // 1. Nuskaitome iš DB 'system_settings' lentelės
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $k = $row['setting_key'];
                $v = $row['setting_value'];
                if ($v === 'true' || $v === '1') $val = true;
                elseif ($v === 'false' || $v === '0') $val = false;
                elseif (is_numeric($v)) $val = (float)$v;
                else $val = $v;
                $settings[$k] = $val;
            }
        } catch (\Throwable $e) {
            // 2. Jei DB klaida, skaitome iš settings.json failo
            $file = __DIR__ . '/../../settings.json';
            if (file_exists($file)) {
                $fileSettings = json_decode(file_get_contents($file), true) ?: [];
                $settings = array_merge($settings, $fileSettings);
            }
        }

        return $settings;
    }

    /**
     * Grąžina vieną nustatymo reikšmę
     */
    public function getSetting(string $key, $default = null)
    {
        $all = $this->getAllSettings();
        return $all[$key] ?? $default;
    }

    /**
     * Išsaugo nustatymus į DB ir settings.json failą
     */
    public function saveSettings(array $newSettings): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $current = $this->getAllSettings();
        $updated = array_merge($current, $newSettings);

        foreach ($newSettings as $k => $v) {
            $valStr = is_bool($v) ? ($v ? '1' : '0') : (string)$v;
            try {
                if ($driver === 'pgsql') {
                    $sql = "INSERT INTO system_settings (setting_key, setting_value, updated_at)
                            VALUES (:key, :val, CURRENT_TIMESTAMP)
                            ON CONFLICT (setting_key) 
                            DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = CURRENT_TIMESTAMP";
                } else {
                    $sql = "INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`)
                            VALUES (:key, :val, CURRENT_TIMESTAMP)
                            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = CURRENT_TIMESTAMP";
                }
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':key' => $k, ':val' => $valStr]);
            } catch (\Throwable $e) {}
        }

        // Taip pat išsaugome į atsarginį failą www/settings.json
        try {
            $file = __DIR__ . '/../../settings.json';
            file_put_contents($file, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {}

        return $updated;
    }
}
