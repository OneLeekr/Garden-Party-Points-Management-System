<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\ProjectScoreSettings;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;

class ProjectController
{
    public function index(): void
    {
        $user = AuthMiddleware::authenticate();
        $db = Database::getInstance();

        if ($user['role_slug'] === 'super_admin') {
            $stmt = $db->query(
                'SELECT p.*, GROUP_CONCAT(u.real_name) AS admin_names, GROUP_CONCAT(pa.user_id) AS admin_ids
                 FROM projects p
                 LEFT JOIN project_admins pa ON p.id = pa.project_id
                 LEFT JOIN users u ON pa.user_id = u.id
                 GROUP BY p.id ORDER BY p.id DESC'
            );
            Response::success(ProjectScoreSettings::mergeIntoList($db, $stmt->fetchAll()));
        }

        if ($user['role_slug'] === 'admin') {
            $stmt = $db->prepare(
                'SELECT p.* FROM projects p
                 JOIN project_admins pa ON p.id = pa.project_id
                 WHERE pa.user_id = ? AND p.status = 1'
            );
            $stmt->execute([$user['id']]);
            Response::success(ProjectScoreSettings::mergeIntoList($db, $stmt->fetchAll()));
        }

        $stmt = $db->query('SELECT id, name, description, location, status FROM projects WHERE status = 1');
        Response::success(ProjectScoreSettings::mergeIntoList($db, $stmt->fetchAll()));
    }

    public function store(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $name = Security::sanitize(Request::input('name', ''));
        if (empty($name)) {
            Response::error('项目名称不能为空');
        }

        $db = Database::getInstance();
        Database::beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO projects (name, description, location, manager_name, status) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                Security::sanitize(Request::input('description', '')),
                Security::sanitize(Request::input('location', '')),
                Security::sanitize(Request::input('manager_name', '')),
                (int)Request::input('status', 1),
            ]);
            $projectId = (int)$db->lastInsertId();

            $adminIds = Request::input('admin_ids', []);
            if (is_array($adminIds)) {
                $insertAdmin = $db->prepare('INSERT INTO project_admins (project_id, user_id) VALUES (?, ?)');
                foreach ($adminIds as $adminId) {
                    $insertAdmin->execute([$projectId, (int)$adminId]);
                }
            }

            ProjectScoreSettings::set($db, $projectId, $this->scoreInputFromRequest());

            Database::commit();
            Logger::operation((int)$admin['id'], 'create_project', 'project', $projectId);
            Response::success(['id' => $projectId], '项目创建成功', 201);
        } catch (\Exception $e) {
            Database::rollBack();
            Response::error('创建失败: ' . $e->getMessage());
        }
    }

    public function update(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $id = (int)Request::input('id', 0);
        if ($id <= 0) {
            Response::error('无效的项目ID');
        }

        $db = Database::getInstance();
        Database::beginTransaction();
        try {
            $stmt = $db->prepare(
                'UPDATE projects SET name=?, description=?, location=?, manager_name=?, status=?, updated_at=NOW() WHERE id=?'
            );
            $stmt->execute([
                Security::sanitize(Request::input('name', '')),
                Security::sanitize(Request::input('description', '')),
                Security::sanitize(Request::input('location', '')),
                Security::sanitize(Request::input('manager_name', '')),
                (int)Request::input('status', 1),
                $id,
            ]);

            $adminIds = Request::input('admin_ids', []);
            $db->prepare('DELETE FROM project_admins WHERE project_id = ?')->execute([$id]);
            if (is_array($adminIds)) {
                $insertAdmin = $db->prepare('INSERT INTO project_admins (project_id, user_id) VALUES (?, ?)');
                foreach ($adminIds as $adminId) {
                    $insertAdmin->execute([$id, (int)$adminId]);
                }
            }

            ProjectScoreSettings::set($db, $id, $this->scoreInputFromRequest());

            Database::commit();
            Logger::operation((int)$admin['id'], 'update_project', 'project', $id);
            Response::success(null, '项目更新成功');
        } catch (\Exception $e) {
            Database::rollBack();
            Response::error('更新失败');
        }
    }

    public function delete(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $id = (int)Request::input('id', 0);
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$id]);

        ProjectScoreSettings::remove($db, $id);

        Logger::operation((int)$admin['id'], 'delete_project', 'project', $id);
        Response::success(null, '项目已删除');
    }

    public function admins(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $db = Database::getInstance();
        $stmt = $db->query('SELECT id, username, real_name FROM users WHERE role_id = 2 AND status = 1');
        Response::success($stmt->fetchAll());
    }

    private function scoreInputFromRequest(): array
    {
        return [
            'gain_score' => Request::input('gain_score', 0),
            'auto_fill_gain' => Request::input('auto_fill_gain', 0),
            'lock_auto_fill_gain' => Request::input('lock_auto_fill_gain', 0),
            'consume_score' => Request::input('consume_score', 0),
            'auto_fill_consume' => Request::input('auto_fill_consume', 0),
            'lock_auto_fill_consume' => Request::input('lock_auto_fill_consume', 0),
            'consume_reasons' => Request::input('consume_reasons', []),
            'use_preset_consume_reason' => Request::input('use_preset_consume_reason', 0),
            'allow_custom_consume_reason' => Request::input('allow_custom_consume_reason', 1),
            'auto_fill_consume_reason' => Request::input('auto_fill_consume_reason', 0),
            'default_consume_reason' => Request::input('default_consume_reason', ''),
            'allow_repeat_play' => Request::input('allow_repeat_play', 1),
        ];
    }
}
