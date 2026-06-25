<?php

namespace YYFSS\Core;

use PDO;

class SystemSettings
{
    private static ?array $cache = null;

    private const PUBLIC_KEYS = ['site_name', 'site_name_en'];

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public static function getAll(?PDO $db = null): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $db = $db ?? Database::getInstance();
        $stmt = $db->query('SELECT setting_key, setting_value FROM system_settings');
        self::$cache = [];
        foreach ($stmt->fetchAll() as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return self::$cache;
    }

    public static function get(string $key, $default = '')
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return max(0, (int)self::get($key, $default));
    }

    /** @return array<string, string> */
    public static function getPublic(): array
    {
        $config = yyfss_config('app');
        return [
            'site_name' => (string)self::get('site_name', $config['app_name_cn'] ?? '游园会积分登记与核销系统'),
            'site_name_en' => (string)self::get('site_name_en', $config['app_name'] ?? 'YuYuan Fair Score System'),
        ];
    }
}
