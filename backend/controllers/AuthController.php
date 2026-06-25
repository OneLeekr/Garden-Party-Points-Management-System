<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\JWT;
use YYFSS\Core\Logger;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Core\SessionStore;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;

class AuthController
{
    public function login(): void
    {
        $username = trim(Security::sanitize(Request::input('username', '')));
        $password = (string)Request::input('password', '');
        $remember = (bool)Request::input('remember', false);

        if (empty($username) || empty($password)) {
            Response::error('请输入用户名和密码');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            if ($user) {
                Logger::login((int)$user['id'], 0);
            }
            Response::error('用户名或密码错误', 401);
        }

        if ((int)$user['status'] !== 1) {
            Response::error('账号已被封禁，请联系管理员', 403);
        }

        $config = yyfss_config('app');
        $expire = $remember ? $config['jwt_refresh_expire'] : $config['jwt_expire'];

        $sessionToken = Security::generateToken(32);
        $clientIp = Security::getClientIp();
        $userAgent = mb_substr(Security::getUserAgent(), 0, 500);

        $token = JWT::encode([
            'uid' => (int)$user['id'],
            'role' => $user['role_slug'],
            'sid' => $sessionToken,
        ], $expire);

        $csrfToken = CSRFMiddleware::generate((int)$user['id']);

        $stmt = $db->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?');
        $stmt->execute([$clientIp, $user['id']]);

        SessionStore::set((int)$user['id'], $sessionToken, $clientIp, $userAgent);

        Logger::login((int)$user['id'], 1);
        Logger::operation((int)$user['id'], 'login', 'user', (int)$user['id']);

        unset($user['password']);

        Response::success([
            'token' => $token,
            'csrf_token' => $csrfToken,
            'expires_in' => $expire,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'real_name' => $user['real_name'],
                'nickname' => $user['nickname'],
                'role_slug' => $user['role_slug'],
                'role_name' => $user['role_name'],
                'avatar' => $user['avatar'],
                'score' => (int)$user['score'],
                'must_change_password' => (int)$user['must_change_password'],
            ],
        ], '登录成功');
    }

    public function me(): void
    {
        $user = AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.id, u.username, u.real_name, u.nickname, u.student_id, u.phone, u.class_name,
                    u.avatar, u.score, u.status, u.must_change_password, u.last_login_at, u.last_login_ip,
                    u.group_id, g.name AS group_name, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN groups g ON u.group_id = g.id
             WHERE u.id = ?'
        );
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();

        Response::success($profile);
    }

    public function changePassword(): void
    {
        $user = AuthMiddleware::authenticate();
        $oldPassword = Request::input('old_password', '');
        $newPassword = Request::input('new_password', '');
        $csrfToken = Request::input('csrf_token', '');

        CSRFMiddleware::validate((int)$user['id'], $csrfToken);

        if (empty($oldPassword) || empty($newPassword)) {
            Response::error('请填写完整密码信息');
        }

        if (!Security::validatePasswordStrength($newPassword)) {
            Response::error('新密码至少8位，需包含大小写字母和数字');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!Security::verifyPassword($oldPassword, $row['password'])) {
            Response::error('原密码错误');
        }

        $hash = Security::hashPassword($newPassword);
        $stmt = $db->prepare('UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);

        Logger::operation((int)$user['id'], 'change_password', 'user', (int)$user['id']);

        Response::success(null, '密码修改成功');
    }

    public function resetPassword(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        $userId = (int)Request::input('user_id', 0);
        $newPassword = Request::input('new_password', '');
        if ($newPassword === '') {
            $newPassword = 'Re123456';
        }
        $csrfToken = Request::input('csrf_token', '');

        CSRFMiddleware::validate((int)$admin['id'], $csrfToken);

        if ($userId <= 0) {
            Response::error('无效的用户ID');
        }

        $hash = Security::hashPassword($newPassword);
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, $userId]);

        SessionStore::clear($userId);

        Logger::operation((int)$admin['id'], 'reset_password', 'user', $userId);

        Response::success(['password' => $newPassword], '密码已重置');
    }

    public function csrfToken(): void
    {
        $user = AuthMiddleware::authenticate();
        $token = CSRFMiddleware::generate((int)$user['id']);
        Response::success(['csrf_token' => $token]);
    }

    public function logout(): void
    {
        $user = AuthMiddleware::authenticate();
        SessionStore::clear((int)$user['id']);
        Logger::operation((int)$user['id'], 'logout', 'user', (int)$user['id']);
        Response::success(null, '已退出登录');
    }
}
