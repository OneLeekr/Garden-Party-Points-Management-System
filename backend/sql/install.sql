-- YYFSS Database Schema
-- YuYuan Fair Score System

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `slug` VARCHAR(50) NOT NULL COMMENT '角色标识',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '分组名称',
  `description` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_groups_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '登录用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
  `real_name` VARCHAR(100) DEFAULT NULL COMMENT '姓名',
  `student_id` VARCHAR(50) DEFAULT NULL COMMENT '学号',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `class_name` VARCHAR(100) DEFAULT NULL COMMENT '班级',
  `group_id` INT UNSIGNED DEFAULT NULL COMMENT '分组ID',
  `role_id` TINYINT UNSIGNED NOT NULL COMMENT '角色ID',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `nickname` VARCHAR(100) DEFAULT NULL COMMENT '昵称',
  `score` INT NOT NULL DEFAULT 0 COMMENT '当前积分',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0封禁',
  `must_change_password` TINYINT NOT NULL DEFAULT 0 COMMENT '首次登录强制改密',
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_group_id` (`group_id`),
  KEY `idx_users_student_id` (`student_id`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_score` (`score`),
  CONSTRAINT `fk_users_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '项目名称',
  `description` TEXT COMMENT '项目简介',
  `location` VARCHAR(200) DEFAULT NULL COMMENT '项目地点',
  `manager_name` VARCHAR(100) DEFAULT NULL COMMENT '项目负责人姓名',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_admin` (`project_id`, `user_id`),
  KEY `idx_pa_user_id` (`user_id`),
  CONSTRAINT `fk_pa_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `score_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '玩家ID',
  `project_id` INT UNSIGNED DEFAULT NULL COMMENT '项目ID',
  `admin_id` INT UNSIGNED NOT NULL COMMENT '操作管理员ID',
  `type` ENUM('gain','consume','adjust') NOT NULL COMMENT '积分类型',
  `score` INT NOT NULL COMMENT '积分变动值(正数)',
  `reason` VARCHAR(500) DEFAULT NULL COMMENT '原因/备注',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sr_user_id` (`user_id`),
  KEY `idx_sr_project_id` (`project_id`),
  KEY `idx_sr_admin_id` (`admin_id`),
  KEY `idx_sr_type` (`type`),
  KEY `idx_sr_created_at` (`created_at`),
  CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sr_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `operation_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT '操作人',
  `action` VARCHAR(100) NOT NULL COMMENT '操作动作',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT '目标类型',
  `target_id` INT UNSIGNED DEFAULT NULL COMMENT '目标ID',
  `detail` TEXT COMMENT '详情',
  `ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ol_user_id` (`user_id`),
  KEY `idx_ol_action` (`action`),
  KEY `idx_ol_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1成功 0失败',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ll_user_id` (`user_id`),
  KEY `idx_ll_created_at` (`created_at`),
  CONSTRAINT `fk_ll_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `qr_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL COMMENT 'Token SHA256哈希',
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qt_user_id` (`user_id`),
  KEY `idx_qt_token_hash` (`token_hash`),
  KEY `idx_qt_expires_at` (`expires_at`),
  CONSTRAINT `fk_qt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `csrf_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_csrf_user` (`user_id`),
  KEY `idx_csrf_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Initialize roles
INSERT INTO `roles` (`id`, `name`, `slug`) VALUES
(1, '超级管理员', 'super_admin'),
(2, '普通管理员', 'admin'),
(3, '玩家', 'player')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Initialize default groups
INSERT INTO `groups` (`name`, `description`) VALUES
('一年级', '一年级分组'),
('二年级', '二年级分组'),
('教师组', '教师分组'),
('嘉宾组', '嘉宾分组');

-- Default super admin: admin / Admin@123456
INSERT INTO `users` (`username`, `password`, `real_name`, `nickname`, `role_id`, `must_change_password`, `status`)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', '超级管理员', 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- Note: default password hash above is placeholder, install.php will set correct hash

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_name', '游园会积分登记与核销系统'),
('site_name_en', 'YuYuan Fair Score System'),
('qr_expire_minutes', '5'),
('installed', '0')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);
