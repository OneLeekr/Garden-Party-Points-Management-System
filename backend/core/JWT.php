<?php

namespace YYFSS\Core;

class JWT
{
    public static function encode(array $payload, ?int $expire = null): string
    {
        $config = yyfss_config('app');
        $expire = $expire ?? $config['jwt_expire'];
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = time();
        $payload['exp'] = time() + $expire;
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", $config['jwt_secret'], true)
        );
        return "$header.$payloadEncoded.$signature";
    }

    public static function decode(string $token): ?array
    {
        $config = yyfss_config('app');
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $config['jwt_secret'], true)
        );
        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }
        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
            return null;
        }
        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
