<?php

require_once __DIR__ . '/load.php';
require_once __DIR__ . '/yyfss-migrate.php';

use YYFSS\Controllers\AuthController;
use YYFSS\Controllers\DashboardController;
use YYFSS\Controllers\GroupController;
use YYFSS\Controllers\ProjectController;
use YYFSS\Controllers\QRController;
use YYFSS\Controllers\ScoreController;
use YYFSS\Controllers\SettingsController;
use YYFSS\Controllers\UserController;
use YYFSS\Core\Request;
use YYFSS\Core\Response;

$config = yyfss_config('app');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

if (Request::method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set($config['timezone']);

try {
    yyfss_ensure_storage();
} catch (Throwable $e) {
    Response::error('存储目录初始化失败: ' . $e->getMessage(), 500);
}

$routes = [
    'POST /api/auth/login' => [AuthController::class, 'login'],
    'POST /api/auth/logout' => [AuthController::class, 'logout'],
    'GET /api/auth/me' => [AuthController::class, 'me'],
    'POST /api/auth/change-password' => [AuthController::class, 'changePassword'],
    'POST /api/auth/reset-password' => [AuthController::class, 'resetPassword'],
    'GET /api/auth/csrf-token' => [AuthController::class, 'csrfToken'],

    'GET /api/dashboard/stats' => [DashboardController::class, 'stats'],
    'GET /api/dashboard/admin-home' => [DashboardController::class, 'adminHome'],

    'GET /api/users' => [UserController::class, 'index'],
    'POST /api/users' => [UserController::class, 'store'],
    'PUT /api/users' => [UserController::class, 'update'],
    'DELETE /api/users' => [UserController::class, 'delete'],
    'POST /api/users/delete' => [UserController::class, 'delete'],
    'POST /api/users/batch-delete' => [UserController::class, 'batchDelete'],
    'POST /api/users/ban' => [UserController::class, 'ban'],
    'POST /api/users/import' => [UserController::class, 'import'],
    'GET /api/users/export' => [UserController::class, 'export'],
    'PUT /api/users/profile' => [UserController::class, 'updateProfile'],
    'POST /api/users/profile' => [UserController::class, 'updateProfile'],
    'GET /api/users/login-logs' => [UserController::class, 'loginLogs'],
    'GET /api/users/operation-logs' => [UserController::class, 'operationLogs'],

    'GET /api/groups' => [GroupController::class, 'index'],
    'POST /api/groups' => [GroupController::class, 'store'],
    'PUT /api/groups' => [GroupController::class, 'update'],
    'DELETE /api/groups' => [GroupController::class, 'delete'],

    'GET /api/projects' => [ProjectController::class, 'index'],
    'POST /api/projects' => [ProjectController::class, 'store'],
    'PUT /api/projects' => [ProjectController::class, 'update'],
    'DELETE /api/projects' => [ProjectController::class, 'delete'],
    'GET /api/projects/admins' => [ProjectController::class, 'admins'],

    'GET /api/scores/flash' => [ScoreController::class, 'flash'],
    'GET /api/scores/records' => [ScoreController::class, 'records'],
    'POST /api/scores/records/clear' => [ScoreController::class, 'clearRecords'],
    'POST /api/scores/gain' => [ScoreController::class, 'gain'],
    'GET /api/scores/gain-eligibility' => [ScoreController::class, 'gainEligibility'],
    'POST /api/scores/gain-eligibility' => [ScoreController::class, 'gainEligibility'],
    'POST /api/scores/consume' => [ScoreController::class, 'consume'],
    'POST /api/scores/adjust' => [ScoreController::class, 'adjust'],
    'POST /api/scores/batch-adjust' => [ScoreController::class, 'batchAdjust'],
    'GET /api/scores/players' => [ScoreController::class, 'players'],
    'GET /api/scores/trend' => [ScoreController::class, 'trend'],
    'GET /api/scores/project-stats' => [ScoreController::class, 'projectStats'],

    'GET /api/qr/generate' => [QRController::class, 'generate'],
    'POST /api/qr/verify' => [QRController::class, 'verify'],

    'GET /api/settings' => [SettingsController::class, 'index'],
    'GET /api/settings/public' => [SettingsController::class, 'publicSettings'],
    'PUT /api/settings' => [SettingsController::class, 'update'],
    'POST /api/settings' => [SettingsController::class, 'update'],
    'POST /api/settings/save' => [SettingsController::class, 'update'],

    'GET /api/health' => function () {
        Response::success(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    },
];

try {
    $method = Request::method();
    $uri = Request::uri();
    $key = "$method $uri";

    if (isset($routes[$key])) {
        $handler = $routes[$key];
        if (is_callable($handler)) {
            $handler();
        } else {
            [$class, $action] = $handler;
            (new $class())->$action();
        }
    } else {
        Response::error('接口不存在: ' . $uri, 404);
    }
} catch (Throwable $e) {
    Response::error('服务器错误: ' . $e->getMessage(), 500);
}
