<?php

namespace YYFSS\Core;

use PDO;

/**
 * 各活动扩展配置（积分、核销原因等，存 system_settings）
 */
class ProjectScoreSettings
{
    private const KEY = 'project_score_map';

    public static function defaults(): array
    {
        return [
            'gain_score' => 0,
            'auto_fill_gain' => 0,
            'lock_auto_fill_gain' => 0,
            'consume_score' => 0,
            'auto_fill_consume' => 0,
            'lock_auto_fill_consume' => 0,
            'consume_reasons' => [],
            'use_preset_consume_reason' => 0,
            'allow_custom_consume_reason' => 1,
            'auto_fill_consume_reason' => 0,
            'default_consume_reason' => '',
            'allow_repeat_play' => 1,
        ];
    }

    public static function normalize(array $input): array
    {
        $input = array_merge(self::defaults(), $input);
        $reasons = self::parseReasons($input['consume_reasons'] ?? []);
        $defaultReason = trim((string)($input['default_consume_reason'] ?? ''));
        if ($defaultReason === '' && !empty($reasons)) {
            $defaultReason = $reasons[0];
        }
        if ($defaultReason !== '' && !in_array($defaultReason, $reasons, true)) {
            $reasons[] = $defaultReason;
        }

        return [
            'gain_score' => max(0, (int)($input['gain_score'] ?? 0)),
            'auto_fill_gain' => !empty($input['auto_fill_gain']) ? 1 : 0,
            'lock_auto_fill_gain' => !empty($input['lock_auto_fill_gain']) ? 1 : 0,
            'consume_score' => max(0, (int)($input['consume_score'] ?? 0)),
            'auto_fill_consume' => !empty($input['auto_fill_consume']) ? 1 : 0,
            'lock_auto_fill_consume' => !empty($input['lock_auto_fill_consume']) ? 1 : 0,
            'consume_reasons' => array_values($reasons),
            'use_preset_consume_reason' => !empty($input['use_preset_consume_reason']) ? 1 : 0,
            'allow_custom_consume_reason' => !empty($input['allow_custom_consume_reason']) ? 1 : 0,
            'auto_fill_consume_reason' => !empty($input['auto_fill_consume_reason']) ? 1 : 0,
            'default_consume_reason' => $defaultReason,
            'allow_repeat_play' => !empty($input['allow_repeat_play']) ? 1 : 0,
        ];
    }

    /** @param mixed $input */
    private static function parseReasons($input): array
    {
        if (is_array($input)) {
            $list = $input;
        } elseif (is_string($input)) {
            $list = preg_split('/[\r\n,，;；]+/', $input) ?: [];
        } else {
            $list = [];
        }

        $reasons = [];
        foreach ($list as $item) {
            $text = trim((string)$item);
            if ($text !== '' && !in_array($text, $reasons, true)) {
                $reasons[] = $text;
            }
        }
        return $reasons;
    }

    public static function getAll(PDO $db): array
    {
        $stmt = $db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
        $stmt->execute([self::KEY]);
        $raw = $stmt->fetchColumn();
        if (!$raw) {
            return [];
        }
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    public static function get(PDO $db, int $projectId): array
    {
        $all = self::getAll($db);
        $cfg = $all[(string)$projectId] ?? self::defaults();
        return self::normalize($cfg);
    }

    public static function set(PDO $db, int $projectId, array $input): void
    {
        $all = self::getAll($db);
        $all[(string)$projectId] = self::normalize($input);

        $stmt = $db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([self::KEY, json_encode($all, JSON_UNESCAPED_UNICODE)]);
    }

    public static function remove(PDO $db, int $projectId): void
    {
        $all = self::getAll($db);
        unset($all[(string)$projectId]);
        $stmt = $db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([self::KEY, json_encode($all, JSON_UNESCAPED_UNICODE)]);
    }

    /** @param array<int, array<string, mixed>> $projects */
    public static function mergeIntoList(PDO $db, array $projects): array
    {
        $all = self::getAll($db);
        foreach ($projects as &$project) {
            $cfg = $all[(string)$project['id']] ?? self::defaults();
            $project = array_merge($project, self::normalize($cfg));
        }
        unset($project);
        return $projects;
    }

    public static function assertScoreEditable(array $cfg, string $mode, int $submittedScore): void
    {
        if ($mode === 'gain') {
            if (!empty($cfg['auto_fill_gain']) && !empty($cfg['lock_auto_fill_gain'])
                && $submittedScore !== (int)$cfg['gain_score']) {
                Response::error('该项目登记积分不可修改');
            }
            return;
        }
        if (!empty($cfg['auto_fill_consume']) && !empty($cfg['lock_auto_fill_consume'])
            && $submittedScore !== (int)$cfg['consume_score']) {
            Response::error('该项目核销积分不可修改');
        }
    }

    public static function hasGainRecord(PDO $db, int $userId, int $projectId): bool
    {
        if ($userId <= 0 || $projectId <= 0) {
            return false;
        }
        $stmt = $db->prepare(
            "SELECT id FROM score_records WHERE user_id = ? AND project_id = ? AND type = 'gain' LIMIT 1"
        );
        $stmt->execute([$userId, $projectId]);
        return (bool)$stmt->fetch();
    }
}
