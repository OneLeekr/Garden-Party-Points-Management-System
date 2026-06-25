<?php

namespace YYFSS\Core;

use PDO;

class Installer
{
    public static function ensureRoles(PDO $db): void
    {
        $roles = [
            [1, '超级管理员', 'super_admin'],
            [2, '普通管理员', 'admin'],
            [3, '玩家', 'player'],
        ];

        $stmt = $db->prepare(
            'INSERT INTO roles (id, name, slug) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug)'
        );
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }

    public static function ensureAdmin(PDO $db, string $username = 'admin', string $password = 'Admin@123456'): void
    {
        self::ensureRoles($db);

        $hash = Security::hashPassword($password);
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare(
                'UPDATE users SET password = ?, role_id = 1, status = 1, must_change_password = 0, updated_at = NOW()
                 WHERE username = ?'
            );
            $stmt->execute([$hash, $username]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO users (username, password, real_name, nickname, role_id, must_change_password, status)
                 VALUES (?, ?, ?, ?, 1, 0, 1)'
            );
            $stmt->execute([$username, $hash, '系统管理员', '超级管理员']);
        }

        $stmt = $db->prepare('SELECT password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row || !Security::verifyPassword($password, $row['password'])) {
            throw new \RuntimeException('管理员密码验证失败');
        }
    }
}
