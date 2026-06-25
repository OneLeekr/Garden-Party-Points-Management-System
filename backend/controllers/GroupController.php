<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;

class GroupController
{
    public function index(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $db = Database::getInstance();
        $stmt = $db->query('SELECT g.*, (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id) AS user_count FROM groups g ORDER BY g.id');
        Response::success($stmt->fetchAll());
    }

    public function store(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $name = Security::sanitize(Request::input('name', ''));
        $description = Security::sanitize(Request::input('description', ''));

        if (empty($name)) {
            Response::error('分组名称不能为空');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO groups (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);

        $id = (int)$db->lastInsertId();
        Logger::operation((int)$admin['id'], 'create_group', 'group', $id);
        Response::success(['id' => $id], '分组创建成功', 201);
    }

    public function update(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $id = (int)Request::input('id', 0);
        $name = Security::sanitize(Request::input('name', ''));
        $description = Security::sanitize(Request::input('description', ''));
        $status = (int)Request::input('status', 1);

        if ($id <= 0 || empty($name)) {
            Response::error('参数错误');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE groups SET name=?, description=?, status=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $description, $status, $id]);

        Logger::operation((int)$admin['id'], 'update_group', 'group', $id);
        Response::success(null, '分组更新成功');
    }

    public function delete(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $id = (int)Request::input('id', 0);
        if ($id <= 0) {
            Response::error('无效的分组ID');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET group_id = NULL WHERE group_id = ?');
        $stmt->execute([$id]);
        $stmt = $db->prepare('DELETE FROM groups WHERE id = ?');
        $stmt->execute([$id]);

        Logger::operation((int)$admin['id'], 'delete_group', 'group', $id);
        Response::success(null, '分组已删除');
    }
}
