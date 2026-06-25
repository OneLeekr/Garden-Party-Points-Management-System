<?php

namespace YYFSS\Core;

use PDO;

class Logger
{
    public static function operation(
        ?int $userId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $detail = null
    ): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO operation_logs (user_id, action, target_type, target_id, detail, ip) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $action,
                $targetType,
                $targetId,
                $detail,
                Security::getClientIp(),
            ]);
        } catch (\Exception $e) {
            // silent fail for logging
        }
    }

    public static function login(int $userId, int $status = 1): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO login_logs (user_id, ip, user_agent, status) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                Security::getClientIp(),
                Security::getUserAgent(),
                $status,
            ]);
        } catch (\Exception $e) {
            // silent fail
        }
    }
}
