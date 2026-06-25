<?php

namespace YYFSS\Services;

use YYFSS\Core\Database;
use YYFSS\Core\Logger;
use YYFSS\Core\ProjectScoreSettings;
use PDO;

class ScoreService
{
    public static function changeScore(
        int $userId,
        int $adminId,
        string $type,
        int $score,
        ?int $projectId,
        string $reason
    ): array {
        if ($score <= 0) {
            throw new \InvalidArgumentException('积分必须大于0');
        }
        if (!in_array($type, ['gain', 'consume', 'adjust'], true)) {
            throw new \InvalidArgumentException('无效的积分类型');
        }
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('必须填写原因');
        }

        $db = Database::getInstance();
        Database::beginTransaction();

        $gainLockName = null;
        try {
            $stmt = $db->prepare('SELECT id, score, status, real_name FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || (int)$user['status'] !== 1) {
                throw new \RuntimeException('用户不存在或已被封禁');
            }

            if ($type === 'gain' && $projectId) {
                $cfg = ProjectScoreSettings::get($db, $projectId);
                if (!$cfg['allow_repeat_play']) {
                    $gainLockName = sprintf('yyfss_gain_%d_%d', $userId, $projectId);
                    $lockStmt = $db->prepare('SELECT GET_LOCK(?, 5)');
                    $lockStmt->execute([$gainLockName]);
                    if ((int)$lockStmt->fetchColumn() !== 1) {
                        throw new \RuntimeException('操作过于频繁，请稍后再试');
                    }
                    if (ProjectScoreSettings::hasGainRecord($db, $userId, $projectId)) {
                        $nameStmt = $db->prepare('SELECT name FROM projects WHERE id = ?');
                        $nameStmt->execute([$projectId]);
                        $projectName = $nameStmt->fetchColumn() ?: '该活动';
                        throw new \RuntimeException("玩家已参与过「{$projectName}」，不可重复游玩");
                    }
                }
            }

            $currentScore = (int)$user['score'];
            $delta = $score;

            if ($type === 'consume') {
                $delta = -$score;
                if ($currentScore + $delta < 0) {
                    throw new \RuntimeException('积分不足，当前余额: ' . $currentScore);
                }
            }

            $newScore = $currentScore + ($type === 'consume' ? -$score : $score);

            $stmt = $db->prepare('UPDATE users SET score = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$newScore, $userId]);

            $stmt = $db->prepare(
                'INSERT INTO score_records (user_id, project_id, admin_id, type, score, reason) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $projectId, $adminId, $type, $score, $reason]);

            $recordId = (int)$db->lastInsertId();

            Database::commit();

            Logger::operation($adminId, 'score_' . $type, 'user', $userId, json_encode([
                'score' => $score,
                'type' => $type,
                'reason' => $reason,
                'new_balance' => $newScore,
            ], JSON_UNESCAPED_UNICODE));

            return [
                'record_id' => $recordId,
                'user_id' => $userId,
                'user_name' => $user['real_name'],
                'old_score' => $currentScore,
                'new_score' => $newScore,
                'change' => $type === 'consume' ? -$score : $score,
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        } finally {
            if ($gainLockName) {
                $release = $db->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$gainLockName]);
            }
        }
    }

    public static function adjustScore(
        int $userId,
        int $adminId,
        int $delta,
        string $reason,
        ?int $projectId = null
    ): array {
        if ($delta === 0) {
            throw new \InvalidArgumentException('积分变动不能为0');
        }
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('必须填写原因');
        }

        $db = Database::getInstance();
        Database::beginTransaction();

        try {
            $stmt = $db->prepare('SELECT id, score, status, real_name FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || (int)$user['status'] !== 1) {
                throw new \RuntimeException('用户不存在或已被封禁');
            }

            $currentScore = (int)$user['score'];
            $newScore = $currentScore + $delta;

            if ($newScore < 0) {
                throw new \RuntimeException('积分不足，当前余额: ' . $currentScore);
            }

            $stmt = $db->prepare('UPDATE users SET score = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$newScore, $userId]);

            $type = 'adjust';
            $scoreValue = abs($delta);

            $stmt = $db->prepare(
                'INSERT INTO score_records (user_id, project_id, admin_id, type, score, reason) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $projectId, $adminId, $type, $scoreValue, $reason . ($delta < 0 ? ' [扣除]' : ' [增加]')]);

            $recordId = (int)$db->lastInsertId();
            Database::commit();

            Logger::operation($adminId, 'score_adjust', 'user', $userId, json_encode([
                'delta' => $delta,
                'reason' => $reason,
                'new_balance' => $newScore,
            ], JSON_UNESCAPED_UNICODE));

            return [
                'record_id' => $recordId,
                'user_id' => $userId,
                'user_name' => $user['real_name'],
                'old_score' => $currentScore,
                'new_score' => $newScore,
                'change' => $delta,
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
