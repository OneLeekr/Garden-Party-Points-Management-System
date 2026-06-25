<?php

namespace YYFSS\Core;

class Request
{
    private static ?array $body = null;

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }
        return '/' . trim($uri, '/');
    }

    public static function body(): array
    {
        if (self::$body === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            self::$body = is_array($decoded) ? $decoded : [];
            if (empty(self::$body) && !empty($_POST)) {
                self::$body = $_POST;
            }
        }
        return self::$body;
    }

    public static function input(string $key, $default = null)
    {
        $body = self::body();
        return $body[$key] ?? $_GET[$key] ?? $default;
    }

    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($header) && function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp($name, 'Authorization') === 0) {
                        $header = $value;
                        break;
                    }
                }
            }
        }
        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }

        // 部分虚拟主机会丢弃 Authorization 头，允许从参数读取
        $token = self::input('access_token', '') ?: self::query('access_token', '');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return null;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }
}
