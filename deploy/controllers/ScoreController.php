<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\ProjectScoreSettings;
use YYFSS\Core\QRToken;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Core\Security;
use YYFSS\Middleware\AuthMiddleware;
use YYFSS\Middleware\CSRFMiddleware;
use YYFSS\Services\ScoreService;

class ScoreController
{
    public function records(): void
    {
        $user = AuthMiddleware::authenticate();
        $page = max(1, (int)Request::query('page', 1));
        $pageSize = min(50, max(1, (int)Request::query('page_size', 20)));
        $type = Request::query('type', '');
        $projectId = Request::query('project_id', '');
        $userId = Request::query('user_id', '');
        $today = Request::query('today', '');

        $where = ['1=1'];
        $params = [];

        if ($user['role_slug'] === 'player') {
            $where[] = 'sr.user_id = ?';
            $params[] = (int)$user['id'];
        } elseif ($user['role_slug'] === 'admin') {
            $where[] = 'sr.project_id IN (SELECT project_id FROM project_admins WHERE user_id = ?)';
            $params[] = (int)$user['id'];
            if ($today === '1') {
                $where[] = 'DATE(sr.created_at) = CURDATE()';
            }
        } else {
            if ($userId !== '') {
                $where[] = 'sr.user_id = ?';
                $params[] = (int)$userId;
            }
        }

        if ($type !== '') {
            $where[] = 'sr.type = ?';
            $params[] = $type;
        }
        if ($projectId !== '') {
            $where[] = 'sr.project_id = ?';
            $params[] = (int)$projectId;
        }

        $whereStr = implode(' AND ', $where);
        $db = Database::getInstance();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM score_records sr WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $pageSize;
        $stmt = $db->prepare(
            "SELECT sr.*, u.real_name AS user_name, u.nickname, p.name AS project_name,
                    a.real_name AS admin_name
             FROM score_records sr
             JOIN users u ON sr.user_id = u.id
             LEFT JOIN projects p ON sr.project_id = p.id
             JOIN users a ON sr.admin_id = a.id
             WHERE $whereStr
             ORDER BY sr.id DESC LIMIT $offset, $pageSize"
        );
        $stmt->execute($params);

        Response::paginate($stmt->fetchAll(), $total, $page, $pageSize);
    }

    public function gainEligibility(): void
    {
        $admin = AuthMiddleware::requireAdmin();
        $userId = (int)Request::input('user_id', 0);
        $projectId = (int)Request::input('project_id', 0);

        if ($userId <= 0 || $projectId <= 0) {
            Response::error('参数错误');
        }

        if ($admin['role_slug'] === 'admin') {
            $this->checkProjectAccess((int)$admin['id'], $projectId);
        }

        $db = Database::getInstance();
        $cfg = ProjectScoreSettings::get($db, $projectId);
        $alreadyPlayed = ProjectScoreSettings::hasGainRecord($db, $userId, $projectId);

        $stmt = $db->prepare('SELECT name FROM projects WHERE id = ?');
        $stmt->execute([$projectId]);
        $projectName = (string)($stmt->fetchColumn() ?: '该活动');

        $allowed = $cfg['allow_repeat_play'] || !$alreadyPlayed;

        Response::success([
            'allowed' => $allowed,
            'allow_repeat_play' => $cfg['allow_repeat_play'],
            'already_played' => $alreadyPlayed,
            'project_name' => $projectName,
            'message' => $allowed ? '' : "玩家已参与过「{$projectName}」，不可重复游玩",
        ]);
    }

    public function gain(): void
    {
        $admin = AuthMiddleware::requireAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $qrToken = Request::input('qr_token', '');
        $userId = (int)Request::input('user_id', 0);
        $projectId = (int)Request::input('project_id', 0);
        $score = (int)Request::input('score', 0);
        $reason = Security::sanitize(Request::input('reason', ''));

        if (!empty($qrToken)) {
            $player = QRToken::verify($qrToken);
            if (!$player) {
                Response::error('二维码无效或已过期');
            }
            $userId = (int)$player['id'];
        }

        if ($userId <= 0 || $score <= 0) {
            Response::error('参数错误');
        }

        if ($admin['role_slug'] === 'admin') {
            $this->checkProjectAccess((int)$admin['id'], $projectId);
        }

        if (empty($reason)) {
            $reason = '活动积分登记';
        }

        $db = Database::getInstance();
        if ($projectId > 0) {
            $cfg = ProjectScoreSettings::get($db, $projectId);
            if (!$cfg['allow_repeat_play'] && ProjectScoreSettings::hasGainRecord($db, $userId, $projectId)) {
                $stmt = $db->prepare('SELECT name FROM projects WHERE id = ?');
                $stmt->execute([$projectId]);
                $projectName = $stmt->fetchColumn() ?: '该活动';
                Response::error("玩家已参与过「{$projectName}」，不可重复游玩", 409);
            }
            ProjectScoreSettings::assertScoreEditable($cfg, 'gain', $score);
        }

        try {
            $result = ScoreService::changeScore($userId, (int)$admin['id'], 'gain', $score, $projectId ?: null, $reason);
            Response::success($result, '积分登记成功');
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function consume(): void
    {
        $admin = AuthMiddleware::requireAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $qrToken = Request::input('qr_token', '');
        $userId = (int)Request::input('user_id', 0);
        $projectId = (int)Request::input('project_id', 0);
        $score = (int)Request::input('score', 0);
        $reason = Security::sanitize(Request::input('reason', ''));

        if (!empty($qrToken)) {
            $player = QRToken::verify($qrToken);
            if (!$player) {
                Response::error('二维码无效或已过期');
            }
            $userId = (int)$player['id'];
        }

        if ($userId <= 0 || $score <= 0) {
            Response::error('参数错误');
        }

        if ($admin['role_slug'] === 'admin') {
            $this->checkProjectAccess((int)$admin['id'], $projectId);
        }

        if (empty($reason)) {
            Response::error('请填写核销原因');
        }

        if ($projectId > 0) {
            $db = Database::getInstance();
            $cfg = ProjectScoreSettings::get($db, $projectId);
            ProjectScoreSettings::assertScoreEditable($cfg, 'consume', $score);
        }

        try {
            $result = ScoreService::changeScore($userId, (int)$admin['id'], 'consume', $score, $projectId ?: null, $reason);
            Response::success($result, '积分核销成功');
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function adjust(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $userId = (int)Request::input('user_id', 0);
        $score = (int)Request::input('score', 0);
        $direction = Request::input('direction', 'add');
        $reason = Security::sanitize(Request::input('reason', ''));
        $projectId = Request::input('project_id') ? (int)Request::input('project_id') : null;

        if ($userId <= 0 || $score <= 0 || empty($reason)) {
            Response::error('参数错误，必须填写原因');
        }

        $delta = $direction === 'subtract' ? -$score : $score;

        try {
            $result = ScoreService::adjustScore($userId, (int)$admin['id'], $delta, $reason, $projectId);
            Response::success($result, '积分调整成功');
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function batchAdjust(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $userIds = Request::input('user_ids', []);
        $score = (int)Request::input('score', 0);
        $direction = Request::input('direction', 'add');
        $reason = Security::sanitize(Request::input('reason', ''));

        if (!is_array($userIds) || empty($userIds) || $score <= 0 || empty($reason)) {
            Response::error('参数错误');
        }

        $delta = $direction === 'subtract' ? -$score : $score;
        $results = [];
        $errors = [];

        foreach ($userIds as $uid) {
            try {
                $results[] = ScoreService::adjustScore((int)$uid, (int)$admin['id'], $delta, $reason);
            } catch (\Exception $e) {
                $errors[] = ['user_id' => $uid, 'error' => $e->getMessage()];
            }
        }

        Response::success(['success' => $results, 'errors' => $errors], '批量调整完成');
    }

    public function players(): void
    {
        AuthMiddleware::requireSuperAdmin();

        $keyword = Request::query('keyword', '');
        $page = max(1, (int)Request::query('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $db = Database::getInstance();
        if ($keyword) {
            $kw = "%$keyword%";
            $countStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role_id = 3 AND (real_name LIKE ? OR student_id LIKE ?)');
            $countStmt->execute([$kw, $kw]);
            $stmt = $db->prepare(
                'SELECT u.id, u.real_name, u.student_id, u.class_name, u.score, g.name AS group_name
                 FROM users u LEFT JOIN groups g ON u.group_id = g.id
                 WHERE u.role_id = 3 AND (u.real_name LIKE ? OR u.student_id LIKE ?)
                 ORDER BY u.score DESC LIMIT ?, ?'
            );
            $stmt->bindValue(1, $kw);
            $stmt->bindValue(2, $kw);
            $stmt->bindValue(3, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(4, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $countStmt = $db->query('SELECT COUNT(*) FROM users WHERE role_id = 3');
            $stmt = $db->prepare(
                'SELECT u.id, u.real_name, u.student_id, u.class_name, u.score, g.name AS group_name
                 FROM users u LEFT JOIN groups g ON u.group_id = g.id
                 WHERE u.role_id = 3 ORDER BY u.score DESC LIMIT ?, ?'
            );
            $stmt->bindValue(1, $offset, \PDO::PARAM_INT);
            $stmt->bindValue(2, $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
        }

        Response::paginate($stmt->fetchAll(), (int)$countStmt->fetchColumn(), $page, $pageSize);
    }

    public function trend(): void
    {
        $user = AuthMiddleware::authenticate();
        if ($user['role_slug'] !== 'player') {
            Response::error('权限不足', 403);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS date,
                    SUM(CASE
                        WHEN type = 'gain' THEN score
                        WHEN type = 'adjust' AND reason LIKE '%[扣除]%' THEN 0
                        WHEN type = 'adjust' THEN score
                        ELSE 0
                    END) AS gained,
                    SUM(CASE
                        WHEN type = 'consume' THEN score
                        WHEN type = 'adjust' AND reason LIKE '%[扣除]%' THEN score
                        ELSE 0
                    END) AS consumed
             FROM score_records WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at) ORDER BY date"
        );
        $stmt->execute([$user['id']]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['gained'] = (int)$row['gained'];
            $row['consumed'] = (int)$row['consumed'];
        }
        unset($row);

        Response::success($rows);
    }

    public function projectStats(): void
    {
        $user = AuthMiddleware::requireAdmin();
        $projectId = (int)Request::query('project_id', 0);

        $db = Database::getInstance();

        if ($user['role_slug'] === 'admin') {
            $check = $db->prepare('SELECT id FROM project_admins WHERE user_id = ? AND project_id = ?');
            $check->execute([$user['id'], $projectId]);
            if (!$check->fetch()) {
                Response::error('无权查看该项目', 403);
            }
        }

        $stmt = $db->prepare(
            'SELECT COUNT(DISTINCT user_id) AS participant_count,
                    SUM(CASE WHEN type = "gain" THEN score ELSE 0 END) AS total_gain,
                    SUM(CASE WHEN type = "consume" THEN score ELSE 0 END) AS total_consume
             FROM score_records WHERE project_id = ? AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute([$projectId]);
        Response::success($stmt->fetch());
    }

    public function flash(): void
    {
        $user = AuthMiddleware::authenticate();
        if ($user['role_slug'] !== 'player') {
            Response::error('权限不足', 403);
        }

        $sinceId = max(0, (int)Request::query('since_id', 0));
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, type, score, reason, created_at
             FROM score_records
             WHERE user_id = ? AND id > ?
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([(int)$user['id'], $sinceId]);
        $record = $stmt->fetch();

        if (!$record) {
            Response::success(null);
        }

        $createdAt = strtotime($record['created_at']);
        if ($createdAt === false || (time() - $createdAt) > 120) {
            Response::success(null);
        }

        $type = 'gain';
        if ($record['type'] === 'consume') {
            $type = 'consume';
        } elseif ($record['type'] === 'adjust' && str_contains($record['reason'], '[扣除]')) {
            $type = 'consume';
        }

        Response::success([
            'id' => (int)$record['id'],
            'score' => (int)$record['score'],
            'type' => $type,
        ]);
    }

    public function clearRecords(): void
    {
        $admin = AuthMiddleware::requireSuperAdmin();
        CSRFMiddleware::validate((int)$admin['id'], Request::input('csrf_token'));

        $all = (bool)Request::input('all', false);
        $ids = Request::input('ids', []);

        $db = Database::getInstance();

        if ($all) {
            $countStmt = $db->query('SELECT COUNT(*) FROM score_records');
            $total = (int)$countStmt->fetchColumn();
            $db->exec('DELETE FROM score_records');
            Logger::operation((int)$admin['id'], 'clear_all_score_records', 'score_record', null, "deleted: $total");
            Response::success(['deleted' => $total], '已清除全部积分流水');
            return;
        }

        if (!is_array($ids) || empty($ids)) {
            Response::error('请选择要清除的流水记录');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        if (empty($ids)) {
            Response::error('无效的记录ID');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $countStmt = $db->prepare("SELECT COUNT(*) FROM score_records WHERE id IN ($placeholders)");
        $countStmt->execute($ids);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("DELETE FROM score_records WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        Logger::operation((int)$admin['id'], 'clear_score_records', 'score_record', null, json_encode(['ids' => $ids], JSON_UNESCAPED_UNICODE));

        Response::success(['deleted' => $total], "已清除 {$total} 条流水");
    }

    private function checkProjectAccess(int $adminId, int $projectId): void
    {
        if ($projectId <= 0) {
            Response::error('请选择活动项目');
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM project_admins WHERE user_id = ? AND project_id = ?');
        $stmt->execute([$adminId, $projectId]);
        if (!$stmt->fetch()) {
            Response::error('您不是该项目的负责人', 403);
        }
    }
}
