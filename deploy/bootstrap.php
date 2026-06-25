<?php
/**
 * PHP 7.4+ compatibility polyfills
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/**
 * Resolve class file path (directory names are lowercase on server)
 */
function yyfss_class_file(string $baseDir, string $relativeClass): string
{
    $parts = explode('/', str_replace('\\', '/', $relativeClass));
    $className = array_pop($parts);
    $dir = strtolower(implode('/', $parts));
    return rtrim($baseDir, '/\\') . '/' . ($dir !== '' ? $dir . '/' : '') . $className . '.php';
}

/**
 * Case-insensitive file resolve fallback
 */
function yyfss_resolve_file(string $baseDir, string $relativeFile): ?string
{
    $parts = explode('/', str_replace('\\', '/', $relativeFile));
    $current = rtrim($baseDir, '/\\');

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $match = null;
        if (!is_dir($current)) {
            return null;
        }
        foreach (scandir($current) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (strcasecmp($entry, $part) === 0) {
                $match = $current . '/' . $entry;
                break;
            }
        }
        if ($match === null) {
            return null;
        }
        $current = $match;
    }

    return $current;
}

spl_autoload_register(function ($class) {
    $prefix = 'YYFSS\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    $candidates = [
        $baseDir . str_replace('\\', '/', $relativeClass) . '.php',
        yyfss_class_file($baseDir, $relativeClass),
    ];

    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    $resolved = yyfss_resolve_file($baseDir, str_replace('\\', '/', $relativeClass) . '.php');
    if ($resolved && file_exists($resolved)) {
        require $resolved;
    }
});

function yyfss_root(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }
    if (is_dir(__DIR__ . '/config')) {
        $root = __DIR__;
    } elseif (is_dir(dirname(__DIR__) . '/config')) {
        $root = dirname(__DIR__);
    } else {
        $root = __DIR__;
    }
    return $root;
}

function yyfss_config(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = yyfss_root() . '/config/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('配置文件不存在: config/' . $name . '.php');
        }
        $cache[$name] = require $file;
    }
    return $cache[$name];
}

function yyfss_load_bootstrap(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
}

yyfss_load_bootstrap();
