<?php

namespace YYFSS\Middleware;

use YYFSS\Core\Database;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;

class CSRFMiddleware
{
    public static function generate(int $userId): string
    {
        $config = yyfss_config('app');
        $token = Security::generateToken(32);
        $expires = time() + ($config['csrf_expire'] ?? 3600);

        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM csrf_tokens WHERE user_id = ? OR expires_at < NOW()');
        $stmt->execute([$userId]);

        $stmt = $db->prepare('INSERT INTO csrf_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))');
        $stmt->execute([$userId, $token, $expires]);

        return $token;
    }

    public static function validate(int $userId, ?string $token): void
    {
        if (empty($token)) {
            Response::error('CSRF Token缺失', 403);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM csrf_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()');
        $stmt->execute([$userId, $token]);
        if (!$stmt->fetch()) {
            Response::error('CSRF Token无效', 403);
        }
    }
}
