<?php

namespace YYFSS\Core;

/**
 * 单设备登录会话（文件存储，无需 ALTER TABLE 权限）
 */
class SessionStore
{
    private static function dir(): string
    {
        $dir = yyfss_root() . '/storage/sessions';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
        return $dir;
    }

    private static function path(int $userId): string
    {
        return self::dir() . '/' . $userId . '.json';
    }

    public static function set(int $userId, string $token, string $ip, string $ua): void
    {
        $data = [
            'token' => $token,
            'ip' => $ip,
            'ua' => mb_substr($ua, 0, 500),
            'at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents(self::path($userId), json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function get(int $userId): ?array
    {
        $file = self::path($userId);
        if (!is_file($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function validate(int $userId, string $tokenSid): bool
    {
        $session = self::get($userId);
        if (!$session || empty($session['token'])) {
            return false;
        }
        return $tokenSid !== '' && hash_equals((string)$session['token'], $tokenSid);
    }

    public static function clear(int $userId): void
    {
        $file = self::path($userId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function ensureStorageReady(): void
    {
        self::dir();
        $test = self::dir() . '/.write_test';
        if (@file_put_contents($test, '1') === false) {
            throw new \RuntimeException('storage/sessions 目录不可写，请检查权限');
        }
        @unlink($test);
    }
}
