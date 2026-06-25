<?php
/**
 * YYFSS bootstrap loader (case-insensitive for Linux hosting)
 */
function yyfss_require_bootstrap(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    foreach (['bootstrap.php', 'Bootstrap.php'] as $name) {
        $path = __DIR__ . '/' . $name;
        if (is_file($path)) {
            require_once $path;
            $done = true;
            return;
        }
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>YYFSS 启动失败</h1><p>找不到 bootstrap.php，请将 htdocs 中的 Bootstrap.php 重命名为 bootstrap.php（全小写）。</p>';
    exit;
}

yyfss_require_bootstrap();

// 修复 Apache/虚拟主机未传递 Authorization 头的问题
if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $_SERVER['HTTP_AUTHORIZATION'] = $value;
                    break;
                }
            }
        }
    }
}
