<?php

namespace YYFSS\Core;

use PDO;

class QRToken
{
    public static function generate(int $userId): array
    {
        $config = yyfss_config('app');
        $expireMinutes = SystemSettings::getInt('qr_expire_minutes', (int)($config['qr_expire_minutes'] ?? 5));
        if ($expireMinutes <= 0) {
            $expireMinutes = 5;
        }
        $nonce = Security::generateToken(16);
        $expiresAt = time() + ($expireMinutes * 60);

        $payload = [
            'uid' => $userId,
            'nonce' => $nonce,
            'exp' => $expiresAt,
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $config['qr_secret']);
        $token = base64_encode($payloadJson) . '.' . $signature;
        $tokenHash = hash('sha256', $token);

        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM qr_tokens WHERE user_id = ? OR expires_at < NOW()');
        $stmt->execute([$userId]);

        $stmt = $db->prepare('INSERT INTO qr_tokens (user_id, token_hash, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))');
        $stmt->execute([$userId, $tokenHash, $expiresAt]);

        return [
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'expire_minutes' => $expireMinutes,
        ];
    }

    public static function verify(string $token): ?array
    {
        $config = yyfss_config('app');
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $signature] = $parts;
        $payloadJson = base64_decode($payloadB64, true);
        if ($payloadJson === false) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $payloadJson, $config['qr_secret']);
        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!$payload || !isset($payload['uid'], $payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM qr_tokens WHERE token_hash = ? AND user_id = ? AND expires_at > NOW()');
        $stmt->execute([$tokenHash, $payload['uid']]);
        if (!$stmt->fetch()) {
            return null;
        }

        $stmt = $db->prepare(
            'SELECT u.id, u.username, u.real_name, u.nickname, u.student_id, u.class_name, u.score, u.status, u.group_id, g.name AS group_name
             FROM users u LEFT JOIN groups g ON u.group_id = g.id WHERE u.id = ? AND u.role_id = 3'
        );
        $stmt->execute([$payload['uid']]);
        $user = $stmt->fetch();
        if (!$user || (int)$user['status'] !== 1) {
            return null;
        }

        return $user;
    }
}
