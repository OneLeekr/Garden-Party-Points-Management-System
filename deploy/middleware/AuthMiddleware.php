<?php

namespace YYFSS\Middleware;

use YYFSS\Core\Database;
use YYFSS\Core\JWT;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\SessionStore;

class AuthMiddleware
{
    public static ?array $user = null;

    public static function authenticate(): array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $token = Request::bearerToken();
        if (!$token) {
            Response::error('未登录或Token已过期', 401);
        }

        $payload = JWT::decode($token);
        if (!$payload || !isset($payload['uid'])) {
            Response::error('Token无效', 401);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, g.name AS group_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN groups g ON u.group_id = g.id
             WHERE u.id = ?'
        );
        $stmt->execute([$payload['uid']]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['status'] !== 1) {
            Response::error('账号不存在或已被封禁', 403);
        }

        self::validateSession($payload, $user);

        unset($user['password']);
        self::$user = $user;
        return self::$user;
    }

    private static function validateSession(array $payload, array $user): void
    {
        $tokenSid = (string)($payload['sid'] ?? '');

        if (!SessionStore::validate((int)$user['id'], $tokenSid)) {
            $session = SessionStore::get((int)$user['id']);
            if (!$session) {
                Response::error('登录已失效，请重新登录', 401);
            }
            Response::error('账号已在其他设备登录，请重新登录', 401);
        }
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::authenticate();
        if (!in_array($user['role_slug'], $roles, true)) {
            Response::error('权限不足', 403);
        }
        return $user;
    }

    public static function requireSuperAdmin(): array
    {
        return self::requireRole('super_admin');
    }

    public static function requireAdmin(): array
    {
        return self::requireRole('super_admin', 'admin');
    }
}
