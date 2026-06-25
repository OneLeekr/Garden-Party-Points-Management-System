<?php

namespace YYFSS\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = yyfss_config('database');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                self::applyTimezone(self::$instance);
            } catch (PDOException $e) {
                throw new \RuntimeException('数据库连接失败: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private static function applyTimezone(PDO $pdo): void
    {
        $app = yyfss_config('app');
        $tz = $app['timezone'] ?? 'Asia/Shanghai';
        date_default_timezone_set($tz);
        try {
            $offset = (new \DateTime('now', new \DateTimeZone($tz)))->format('P');
            $pdo->exec("SET time_zone = '{$offset}'");
        } catch (\Throwable $e) {
            $pdo->exec("SET time_zone = '+08:00'");
        }
    }

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    public static function rollBack(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->rollBack();
        }
    }
}
