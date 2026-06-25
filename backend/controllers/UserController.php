<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Core\SessionStore;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;
use YYFSS\Utils\SimpleXLSX;

class UserController
{
    public function index(): void
    {
        AuthMiddleware::requireSuperAdmin();

        $page = max(1, (int)Request::query('page', 1));
        $pageSize = min(100, max(1, (int)Request::query('page_size', 20)));
        $keyword = Request::query('keyword', '');
        $groupId = Request::query('group_id', '');
        $roleId = Request::query('role_id', '');
        $status = Request::query('status', '');

        $where = ['1=1'];
        $params = [];

        if ($keyword) {
            $where[] = '(u.username LIKE ? OR u.real_name LIKE ? OR u.phone LIKE ?)';
            $kw = "%$keyword%";
            $params = array_merge($params, [$kw, $kw, $kw]);
        }
        if ($groupId !== '') {
            $where[] = 'u.group_id = ?';
            $params[] = (int)$groupId;
        }
        if ($roleId !== '') {
            $where[] = 'u.role_id = ?';
            $params[] = (int)$roleId;
        }
        if ($status !== '') {
            $where[] = 'u.status = ?';
            $params[] = (int)$status;
        }

        $whereStr = implode(' AND ', $where);
        $db = Database::getInstance();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $pageSize;
        $stmt = $db->prepare(
            "SELECT u.id, u.username, u.real_name, u.student_id, u.phone, u.class_name, u.nickname,
                    u.score, u.status, u.group_id, u.role_id, u.last_login_at, u.last_login_ip, u.created_at,
                    (SELECT ll.user_agent FROM login_logs ll WHERE ll.user_id = u.id AND ll.status = 1 ORDER BY ll.id DESC LIMIT 1) AS last_login_ua,
                    g.name AS group_name, r.name AS role_name, r.slug AS role_slug
             FROM users u
             LEFT JOIN groups g ON u.group_id = g.id
             JOIN roles r ON u.role_id = r.id
             WHERE $whereStr
             ORDER BY u.id DESC LIMIT $offset, $pageSize"
        );
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        Response::paginate($list, $total, $page, $pageSize);
    }

    public function store(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $data = $this->validateUserInput(true);
        $password = Request::input('password', '');
        if ($password === '') {
            Response::error('请设置登录密码');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            Response::error('用户名已存在');
        }

        $hash = Security::hashPassword($password);
        $stmt = $db->prepare(
            'INSERT INTO users (username, password, real_name, phone, group_id, role_id, nickname, score, must_change_password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([
            $data['username'], $hash, $data['real_name'],
            $data['phone'], $data['group_id'], $data['role_id'],
            $data['nickname'] ?: $data['real_name'], $data['score'],
        ]);

        $userId = (int)$db->lastInsertId();
        Logger::operation((int)$admin['id'], 'create_user', 'user', $userId, json_encode($data, JSON_UNESCAPED_UNICODE));

        Response::success(['id' => $userId], '用户创建成功', 201);
    }

    public function update(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $userId = (int)Request::input('id', 0);
        if ($userId <= 0) {
            Response::error('无效的用户ID');
        }

        $data = $this->validateUserInput(false);
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'UPDATE users SET real_name=?, phone=?, group_id=?, role_id=?, nickname=?, updated_at=NOW() WHERE id=?'
        );
        $stmt->execute([
            $data['real_name'], $data['phone'], $data['group_id'], $data['role_id'], $data['nickname'], $userId,
        ]);

        Logger::operation((int)$admin['id'], 'update_user', 'user', $userId);
        Response::success(null, '用户更新成功');
    }

    public function delete(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $userId = (int)Request::input('id', 0);
        if ($userId <= 0 || $userId === (int)$admin['id']) {
            Response::error('无法删除该用户');
        }

        $db = Database::getInstance();

        $roleStmt = $db->prepare('SELECT role_id FROM users WHERE id = ?');
        $roleStmt->execute([$userId]);
        $roleId = (int)$roleStmt->fetchColumn();
        if ($roleId <= 0) {
            Response::error('用户不存在');
        }
        if ($roleId === 1) {
            Response::error('无法删除超级管理员');
        }

        $refStmt = $db->prepare('SELECT COUNT(*) FROM score_records WHERE user_id = ? OR admin_id = ?');
        $refStmt->execute([$userId, $userId]);
        if ((int)$refStmt->fetchColumn() > 0) {
            Response::error('该用户存在积分流水记录，无法删除，可先封禁');
        }

        Database::beginTransaction();
        try {
            SessionStore::clear($userId);
            $db->prepare('DELETE FROM csrf_tokens WHERE user_id = ?')->execute([$userId]);
            $db->prepare('DELETE FROM project_admins WHERE user_id = ?')->execute([$userId]);
            $stmt = $db->prepare('DELETE FROM users WHERE id = ? AND role_id != 1');
            $stmt->execute([$userId]);
            if ($stmt->rowCount() === 0) {
                Database::rollBack();
                Response::error('删除失败');
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            Response::error('删除失败，请稍后重试');
        }

        Logger::operation((int)$admin['id'], 'delete_user', 'user', $userId);
        Response::success(null, '用户已删除');
    }

    public function batchDelete(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $ids = Request::input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            Response::error('请选择要删除的用户');
        }

        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0 && $id !== (int)$admin['id']);
        if (empty($ids)) {
            Response::error('无有效用户');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role_id != 1");
        $stmt->execute(array_values($ids));

        Logger::operation((int)$admin['id'], 'batch_delete_users', 'user', null, json_encode($ids));
        Response::success(['deleted' => $stmt->rowCount()], '批量删除成功');
    }

    public function ban(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $userId = (int)Request::input('user_id', 0);
        $status = (int)Request::input('status', 0);

        if ($userId <= 0 || $userId === (int)$admin['id']) {
            Response::error('无法操作该用户');
        }

        $db = Database::getInstance();
        if ($status === 0) {
            SessionStore::clear($userId);
        }
        $stmt = $db->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $userId]);

        Logger::operation((int)$admin['id'], $status ? 'unban_user' : 'ban_user', 'user', $userId);
        Response::success(null, $status ? '账号已解封' : '账号已封禁');
    }

    public function import(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();

        $file = Request::file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error('请上传文件');
        }

        $xlsx = SimpleXLSX::parse($file['tmp_name'], $file['name'] ?? null);
        if (!$xlsx) {
            Response::error('文件解析失败，请使用 CSV 或 XLSX 格式');
        }

        $rows = $xlsx->rows();
        if (count($rows) < 2) {
            Response::error('文件内容为空');
        }

        $db = Database::getInstance();
        $groupMap = [];
        $stmt = $db->query('SELECT id, name FROM groups');
        foreach ($stmt->fetchAll() as $g) {
            $groupMap[$g['name']] = (int)$g['id'];
        }

        $success = 0;
        $failed = [];
        $defaultPassword = Security::hashPassword('Player@123456');

        Database::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) {
                    continue;
                }

                $realName = trim($row[0] ?? '');
                $phone = trim($row[1] ?? '');
                $groupName = trim($row[2] ?? '');
                $initScore = (int)($row[3] ?? 0);

                if (empty($realName)) {
                    $failed[] = ['row' => $i + 1, 'reason' => '姓名不能为空'];
                    continue;
                }

                $username = $phone ?: ('player_' . time() . '_' . $i);
                $groupId = $groupMap[$groupName] ?? null;

                $check = $db->prepare('SELECT id FROM users WHERE username = ?');
                $check->execute([$username]);
                if ($check->fetch()) {
                    $username = $username . '_' . $i;
                }

                $insert = $db->prepare(
                    'INSERT INTO users (username, password, real_name, phone, group_id, role_id, nickname, score, must_change_password)
                     VALUES (?, ?, ?, ?, ?, 3, ?, ?, 0)'
                );
                $insert->execute([$username, $defaultPassword, $realName, $phone, $groupId, $realName, $initScore]);
                $success++;
            }
            Database::commit();
        } catch (\Exception $e) {
            Database::rollBack();
            Response::error('导入失败: ' . $e->getMessage());
        }

        Logger::operation((int)$admin['id'], 'import_users', 'user', null, "success: $success");
        Response::success(['success' => $success, 'failed' => $failed], '导入完成');
    }

    public function export(): void
    {
        AuthMiddleware::requireSuperAdmin();

        $db = Database::getInstance();
        $stmt = $db->query(
            'SELECT u.real_name, u.phone, g.name AS group_name, u.score, u.username, r.name AS role_name
             FROM users u LEFT JOIN groups g ON u.group_id = g.id JOIN roles r ON u.role_id = r.id ORDER BY u.id'
        );
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_export_' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        echo "姓名,手机号,分组,积分,用户名,角色\n";
        foreach ($rows as $r) {
            echo implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '""', $v ?? '') . '"';
            }, [$r['real_name'], $r['phone'], $r['group_name'], $r['score'], $r['username'], $r['role_name']]));
            echo "\n";
        }
        exit;
    }

    public function updateProfile(): void
    {
        $user = AuthMiddleware::authenticate();
        CSRFMiddleware::validate((int)$user['id'], Request::input('csrf_token'));

        $nickname = Security::sanitize(Request::input('nickname', ''));
        if (empty($nickname)) {
            Response::error('昵称不能为空');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET nickname = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$nickname, $user['id']]);

        $stmt = $db->prepare('SELECT nickname, real_name FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $updated = $stmt->fetch() ?: ['nickname' => $nickname, 'real_name' => ''];

        Response::success($updated, '资料更新成功');
    }

    public function loginLogs(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $userId = (int)Request::query('user_id', 0);
        $page = max(1, (int)Request::query('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $db = Database::getInstance();
        if ($userId > 0) {
            $countStmt = $db->prepare('SELECT COUNT(*) FROM login_logs WHERE user_id = ?');
            $countStmt->execute([$userId]);
            $stmt = $db->prepare('SELECT * FROM login_logs WHERE user_id = ? ORDER BY id DESC LIMIT ?, ?');
            $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(3, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $countStmt = $db->query('SELECT COUNT(*) FROM login_logs');
            $stmt = $db->prepare('SELECT ll.*, u.username, u.real_name FROM login_logs ll JOIN users u ON ll.user_id = u.id ORDER BY ll.id DESC LIMIT ?, ?');
            $stmt->bindValue(1, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(2, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        }

        Response::paginate($stmt->fetchAll(), (int)$countStmt->fetchColumn(), $page, $pageSize);
    }

    public function operationLogs(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $userId = (int)Request::query('user_id', 0);
        $page = max(1, (int)Request::query('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $db = Database::getInstance();
        if ($userId > 0) {
            $countStmt = $db->prepare('SELECT COUNT(*) FROM operation_logs WHERE user_id = ?');
            $countStmt->execute([$userId]);
            $stmt = $db->prepare(
                'SELECT ol.*, u.username FROM operation_logs ol LEFT JOIN users u ON ol.user_id = u.id WHERE ol.user_id = ? ORDER BY ol.id DESC LIMIT ?, ?'
            );
            $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(3, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $countStmt = $db->query('SELECT COUNT(*) FROM operation_logs');
            $stmt = $db->prepare(
                'SELECT ol.*, u.username FROM operation_logs ol LEFT JOIN users u ON ol.user_id = u.id ORDER BY ol.id DESC LIMIT ?, ?'
            );
            $stmt->bindValue(1, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(2, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        }

        Response::paginate($stmt->fetchAll(), (int)$countStmt->fetchColumn(), $page, $pageSize);
    }

    private function validateUserInput(bool $isCreate): array
    {
        $username = Security::sanitize(Request::input('username', ''));
        if ($isCreate && empty($username)) {
            Response::error('用户名不能为空');
        }

        return [
            'username' => $username,
            'real_name' => Security::sanitize(Request::input('real_name', '')),
            'student_id' => Security::sanitize(Request::input('student_id', '')),
            'phone' => Security::sanitize(Request::input('phone', '')),
            'class_name' => Security::sanitize(Request::input('class_name', '')),
            'group_id' => Request::input('group_id') ? (int)Request::input('group_id') : null,
            'role_id' => (int)Request::input('role_id', 3),
            'nickname' => Security::sanitize(Request::input('nickname', '')),
            'score' => (int)Request::input('score', 0),
        ];
    }
}
