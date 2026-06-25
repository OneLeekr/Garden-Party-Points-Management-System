<?php
/**
 * YYFSS System Installer
 * Visit once, then delete this file.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/load.php';

use YYFSS\Core\Database;
use YYFSS\Core\Installer;
use YYFSS\Core\Security;

header('Content-Type: text/html; charset=utf-8');

$messages = [];
$success = false;
$phpVersion = PHP_VERSION;

try {
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        throw new Exception('需要 PHP 7.4 或更高版本，当前: ' . PHP_VERSION);
    }

    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
        throw new Exception('缺少 PDO 或 PDO_MySQL 扩展，请在主机面板启用');
    }

    $sqlFile = __DIR__ . '/sql/install.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL 文件不存在: sql/install.sql');
    }

    $sql = file_get_contents($sqlFile);
    $db = Database::getInstance();

    // Remove block comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    $statements = preg_split('/;\s*\n/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        // Skip single-line comments at start
        $lines = array_filter(array_map('trim', explode("\n", $statement)), function ($line) {
            return $line !== '' && !str_starts_with($line, '--');
        });
        $statement = implode("\n", $lines);
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }

    // 确保角色和管理员数据完整
    Installer::ensureRoles($db);
    Installer::ensureAdmin($db, 'admin', 'Admin@123456');

    $stmt = $db->prepare(
        'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute(['installed', '1']);
    $stmt->execute(['install_time', date('Y-m-d H:i:s')]);

    $success = true;
    $messages[] = '数据库初始化成功';
    $messages[] = 'PHP 版本: ' . PHP_VERSION;
    $messages[] = '默认管理员: admin / Admin@123456';
    $messages[] = '首次登录需修改密码';
    $messages[] = '请删除 install.php 文件';
} catch (Throwable $e) {
    $messages[] = '安装失败: ' . $e->getMessage();
    $messages[] = 'PHP 版本: ' . $phpVersion;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YYFSS 系统安装</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; background: #f5f7fa; }
        .card { background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { color: #1a56db; margin-top: 0; }
        .msg { padding: 12px; margin: 8px 0; border-radius: 8px; background: <?= $success ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $success ? '#065f46' : '#991b1b' ?>; }
        .warn { margin-top: 20px; padding: 12px; background: #fffbeb; color: #92400e; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>游园会积分系统安装</h1>
        <?php foreach ($messages as $msg): ?>
            <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
        <?php if ($success): ?>
            <div class="warn">安全提示: 安装完成后请立即删除 install.php，并修改 config/app.php 中的密钥。</div>
        <?php else: ?>
            <div class="warn">若仍失败：1) 将 Bootstrap.php 重命名为 bootstrap.php；2) 在控制面板将 PHP 版本设为 8.0+；3) 访问 check.php 查看环境检测。</div>
        <?php endif; ?>
    </div>
</body>
</html>
