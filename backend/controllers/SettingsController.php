<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Core\SystemSettings;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;

class SettingsController
{
    public function index(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $db = Database::getInstance();
        $stmt = $db->query('SELECT setting_key, setting_value FROM system_settings');
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        Response::success($settings);
    }

    public function update(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $settings = Request::input('settings', []);
        if (!is_array($settings)) {
            Response::error('参数错误');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        $allowed = ['site_name', 'site_name_en', 'qr_expire_minutes'];
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowed, true)) {
                if ($key === 'qr_expire_minutes') {
                    $value = (string)max(1, min(60, (int)$value));
                }
                $stmt->execute([$key, Security::sanitize((string)$value)]);
            }
        }

        Logger::operation((int)$admin['id'], 'update_settings', 'system', null);
        SystemSettings::clearCache();
        Response::success(SystemSettings::getPublic(), '设置已保存');
    }

    public function publicSettings(): void
    {
        Response::success(SystemSettings::getPublic());
    }
}
