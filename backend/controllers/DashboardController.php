<?php

namespace YYFSS\Controllers;

use YYFSS\Core\Database;
use YYFSS\Core\Request;
use YYFSS\Core\Response;
use YYFSS\Middleware\AuthMiddleware;

class DashboardController
{
    public function stats(): void
    {
        AuthMiddleware::requireSuperAdmin();
        $db = Database::getInstance();

        $totalPlayers = (int)$db->query('SELECT COUNT(*) FROM users WHERE role_id = 3')->fetchColumn();
        $totalAdmins = (int)$db->query('SELECT COUNT(*) FROM users WHERE role_id IN (1,2)')->fetchColumn();

        $todayCheckin = (int)$db->query(
            'SELECT COUNT(DISTINCT user_id) FROM login_logs WHERE DATE(created_at) = CURDATE() AND status = 1'
        )->fetchColumn();

        $todayGain = (int)$db->query(
            'SELECT COALESCE(SUM(score), 0) FROM score_records WHERE type = "gain" AND DATE(created_at) = CURDATE()'
        )->fetchColumn();

        $todayConsume = (int)$db->query(
            'SELECT COALESCE(SUM(score), 0) FROM score_records WHERE type = "consume" AND DATE(created_at) = CURDATE()'
        )->fetchColumn();

        $projectRank = $db->query(
            'SELECT p.name, COUNT(DISTINCT sr.user_id) AS participants, COALESCE(SUM(sr.score), 0) AS total_score
             FROM projects p
             LEFT JOIN score_records sr ON p.id = sr.project_id AND sr.type = "gain"
             GROUP BY p.id ORDER BY total_score DESC LIMIT 10'
        )->fetchAll();

        $playerRank = $db->query(
            'SELECT u.real_name, u.student_id, u.class_name, u.score, g.name AS group_name
             FROM users u LEFT JOIN groups g ON u.group_id = g.id
             WHERE u.role_id = 3 ORDER BY u.score DESC LIMIT 10'
        )->fetchAll();

        Response::success([
            'total_players' => $totalPlayers,
            'total_admins' => $totalAdmins,
            'today_checkin' => $todayCheckin,
            'today_gain' => $todayGain,
            'today_consume' => $todayConsume,
            'project_rank' => $projectRank,
            'player_rank' => $playerRank,
        ]);
    }

    public function adminHome(): void
    {
        $user = AuthMiddleware::requireAdmin();
        $db = Database::getInstance();

        if ($user['role_slug'] === 'super_admin') {
            $this->stats();
            return;
        }

        $stmt = $db->prepare(
            'SELECT p.id, p.name,
                    (SELECT COUNT(*) FROM score_records sr WHERE sr.project_id = p.id AND sr.type = "gain" AND DATE(sr.created_at) = CURDATE()) AS today_gain_count,
                    (SELECT COUNT(*) FROM score_records sr WHERE sr.project_id = p.id AND sr.type = "consume" AND DATE(sr.created_at) = CURDATE()) AS today_consume_count,
                    (SELECT COUNT(DISTINCT user_id) FROM score_records sr WHERE sr.project_id = p.id AND DATE(sr.created_at) = CURDATE()) AS today_participants
             FROM projects p
             JOIN project_admins pa ON p.id = pa.project_id
             WHERE pa.user_id = ?'
        );
        $stmt->execute([$user['id']]);

        Response::success(['projects' => $stmt->fetchAll()]);
    }
}
