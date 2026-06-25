<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\ProjectScoreSettings;
use YYFSS\Core\QRToken;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Middleware\AuthMiddleware;

class QRController
{
    public function generate(): void
    {
        $user = AuthMiddleware::authenticate();
        if ($user['role_slug'] !== 'player') {
            Response::error('仅玩家可生成身份二维码', 403);
        }

        $data = QRToken::generate((int)$user['id']);
        Response::success($data);
    }

    public function verify(): void
    {
        $admin = AuthMiddleware::requireAdmin();
        $token = Request::input('token', '');
        if (empty($token)) {
            Response::error('二维码Token不能为空');
        }

        $player = QRToken::verify($token);
        if (!$player) {
            Response::error('二维码无效或已过期', 400);
        }

        $userId = (int)$player['id'];
        $result = [
            'id' => $userId,
            'real_name' => $player['real_name'],
            'nickname' => $player['nickname'],
            'student_id' => $player['student_id'],
            'class_name' => $player['class_name'],
            'group_name' => $player['group_name'],
            'score' => (int)$player['score'],
        ];

        $projectId = (int)Request::input('project_id', 0);
        if ($projectId > 0) {
            $this->assertAdminProjectAccess($admin, $projectId);
            $result = array_merge($result, $this->gainEligibilityPayload($userId, $projectId));
        }

        Response::success($result);
    }

    /** @return array<string, mixed> */
    private function gainEligibilityPayload(int $userId, int $projectId): array
    {
        $db = Database::getInstance();
        $cfg = ProjectScoreSettings::get($db, $projectId);
        $alreadyPlayed = ProjectScoreSettings::hasGainRecord($db, $userId, $projectId);

        $stmt = $db->prepare('SELECT name FROM projects WHERE id = ?');
        $stmt->execute([$projectId]);
        $projectName = (string)($stmt->fetchColumn() ?: '该活动');

        $allowed = $cfg['allow_repeat_play'] || !$alreadyPlayed;
        $payload = [
            'allow_repeat_play' => $cfg['allow_repeat_play'],
            'already_played' => $alreadyPlayed,
            'project_name' => $projectName,
            'gain_allowed' => $allowed,
        ];
        if (!$allowed) {
            $payload['gain_block_message'] = "玩家已参与过「{$projectName}」，不可重复游玩";
        }
        return $payload;
    }

    /** @param array<string, mixed> $admin */
    private function assertAdminProjectAccess(array $admin, int $projectId): void
    {
        if ($admin['role_slug'] !== 'admin') {
            return;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM project_admins WHERE user_id = ? AND project_id = ?');
        $stmt->execute([(int)$admin['id'], $projectId]);
        if (!$stmt->fetch()) {
            Response::error('您不是该项目的负责人', 403);
        }
    }
}
