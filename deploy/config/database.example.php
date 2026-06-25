<?php
/**
 * Database Configuration (示例)
 * 部署时复制为 database.php 并填写真实信息，勿将 database.php 提交到公开仓库
 */

return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'your_database_name',
    'username' => 'your_database_user',
    'password' => 'your_database_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
