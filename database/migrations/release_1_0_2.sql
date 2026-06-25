-- Release 1.0.2 production bootstrap schema patch.
-- Safe to run more than once on MySQL 8.x.

CREATE TABLE IF NOT EXISTS `core_permission_group` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `code` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `updated_by` INT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permission_group_name_unique` (`name`),
    UNIQUE KEY `permission_group_code_unique` (`code`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_permission` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` INT NULL DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `weight` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `updated_by` INT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permission_code` (`code`),
    UNIQUE KEY `uq_permission_name` (`name`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_role` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `updated_by` INT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_role_code` (`code`),
    UNIQUE KEY `uq_role_name` (`name`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `remember_token_hash` VARCHAR(255) NULL DEFAULT NULL,
    `password_changed_at` DATETIME NULL DEFAULT NULL,
    `password_expires_at` DATETIME NULL DEFAULT NULL,
    `password_reset_selector` VARCHAR(64) NULL DEFAULT NULL,
    `password_reset_token_hash` VARCHAR(255) NULL DEFAULT NULL,
    `password_reset_token_expires_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `updated_by` INT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_email` (`email`),
    KEY `idx_user_password_reset_selector` (`password_reset_selector`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_role_permission` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `fk_role_permission_permission` (`permission_id`),
    CONSTRAINT `fk_role_permission_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `core_permission` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_role_permission_role`
        FOREIGN KEY (`role_id`) REFERENCES `core_role` (`id`)
        ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_user_role` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_user_role_role` (`role_id`),
    CONSTRAINT `fk_user_role_role`
        FOREIGN KEY (`role_id`) REFERENCES `core_role` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_user_role_user`
        FOREIGN KEY (`user_id`) REFERENCES `core_user` (`id`)
        ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mes_task` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `status` TINYINT NOT NULL DEFAULT 0,
    `start_date` DATE NULL DEFAULT NULL,
    `end_date` DATE NULL DEFAULT NULL,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `updated_by` INT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_task_start_date` (`start_date`),
    INDEX `idx_task_end_date` (`end_date`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_auth_rate_limit` (
    `rate_key` VARCHAR(191) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `scope` VARCHAR(64) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `window_started_at` DATETIME NOT NULL,
    `blocked_until` DATETIME NULL DEFAULT NULL,
    `last_attempt_at` DATETIME NOT NULL,
    PRIMARY KEY (`rate_key`),
    INDEX `idx_auth_rate_limit_scope` (`scope`),
    INDEX `idx_auth_rate_limit_cleanup` (`last_attempt_at`, `blocked_until`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `entity_id` VARCHAR(64) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `action` VARCHAR(30) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `source` VARCHAR(20) COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT 'web',
    `actor_id` INT UNSIGNED NULL DEFAULT NULL,
    `entity_created_at` DATETIME NULL DEFAULT NULL,
    `entity_updated_at` DATETIME NULL DEFAULT NULL,
    `sql_query` LONGTEXT COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `sql_params` JSON NULL DEFAULT NULL,
    `url` VARCHAR(2048) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `method` VARCHAR(16) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `request_query` JSON NULL DEFAULT NULL,
    `request_body` JSON NULL DEFAULT NULL,
    `console_command` VARCHAR(512) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `user_agent` VARCHAR(512) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_log_entity` (`entity_type`, `entity_id`, `id`),
    INDEX `idx_log_actor` (`actor_id`),
    INDEX `idx_log_action` (`action`),
    INDEX `idx_log_created_at` (`created_at`),
    INDEX `idx_log_source` (`source`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `description` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_notification_created_at` (`created_at`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `core_notification_user` (
    `notification_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`notification_id`, `user_id`),
    INDEX `idx_notification_user_user_read` (`user_id`, `is_read`, `created_at`),
    CONSTRAINT `fk_notification_user_notification`
        FOREIGN KEY (`notification_id`) REFERENCES `core_notification` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_notification_user_user`
        FOREIGN KEY (`user_id`) REFERENCES `core_user` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO `core_permission_group` (`name`, `code`, `created_at`, `updated_at`)
VALUES
    ('task', 'TASK', NOW(), NOW()),
    ('permission', 'PERMISSION', NOW(), NOW()),
    ('role', 'ROLE', NOW(), NOW()),
    ('user', 'USER', NOW(), NOW()),
    ('log', 'LOG', NOW(), NOW()),
    ('permission.group', 'PERMISSION_GROUP', NOW(), NOW()),
    ('notification', 'NOTIFICATION', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `updated_at` = VALUES(`updated_at`);

INSERT INTO `core_role` (`name`, `code`, `created_at`, `updated_at`)
VALUES
    ('admin', 'ADMIN', NOW(), NOW()),
    ('utente.esterno', 'UTENTE_ESTERNO', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `updated_at` = VALUES(`updated_at`);

DROP TEMPORARY TABLE IF EXISTS `bootstrap_permission`;

CREATE TEMPORARY TABLE `bootstrap_permission` (
    `group_code` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `name` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `code` VARCHAR(100) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
    `weight` INT NOT NULL DEFAULT 1
);

INSERT INTO `bootstrap_permission` (`group_code`, `name`, `code`, `weight`)
VALUES
    ('TASK', 'task.access', 'TASK_ACCESS', 1),
    ('TASK', 'task.view.all', 'TASK_VIEW_ALL', 90),
    ('TASK', 'task.view.own', 'TASK_VIEW_OWN', 80),
    ('TASK', 'task.create', 'TASK_CREATE', 1),
    ('TASK', 'task.update', 'TASK_UPDATE', 1),
    ('TASK', 'task.delete', 'TASK_DELETE', 1),
    ('PERMISSION', 'permission.access', 'PERMISSION_ACCESS', 1),
    ('PERMISSION', 'permission.view.all', 'PERMISSION_VIEW_ALL', 90),
    ('PERMISSION', 'permission.view.own', 'PERMISSION_VIEW_OWN', 80),
    ('PERMISSION', 'permission.create', 'PERMISSION_CREATE', 1),
    ('PERMISSION', 'permission.update', 'PERMISSION_UPDATE', 1),
    ('PERMISSION', 'permission.delete', 'PERMISSION_DELETE', 1),
    ('ROLE', 'role.access', 'ROLE_ACCESS', 1),
    ('ROLE', 'role.view.all', 'ROLE_VIEW_ALL', 90),
    ('ROLE', 'role.view.own', 'ROLE_VIEW_OWN', 80),
    ('ROLE', 'role.create', 'ROLE_CREATE', 1),
    ('ROLE', 'role.update', 'ROLE_UPDATE', 1),
    ('ROLE', 'role.delete', 'ROLE_DELETE', 1),
    ('USER', 'user.access', 'USER_ACCESS', 1),
    ('USER', 'user.view.all', 'USER_VIEW_ALL', 90),
    ('USER', 'user.view.own', 'USER_VIEW_OWN', 80),
    ('USER', 'user.create', 'USER_CREATE', 1),
    ('USER', 'user.update', 'USER_UPDATE', 1),
    ('USER', 'user.delete', 'USER_DELETE', 1),
    ('LOG', 'log.access', 'LOG_ACCESS', 1),
    ('PERMISSION_GROUP', 'permission.group.access', 'PERMISSION_GROUP_ACCESS', 1),
    ('PERMISSION_GROUP', 'permission.group.view.all', 'PERMISSION_GROUP_VIEW_ALL', 90),
    ('PERMISSION_GROUP', 'permission.group.view.own', 'PERMISSION_GROUP_VIEW_OWN', 80),
    ('PERMISSION_GROUP', 'permission.group.create', 'PERMISSION_GROUP_CREATE', 1),
    ('PERMISSION_GROUP', 'permission.group.update', 'PERMISSION_GROUP_UPDATE', 1),
    ('PERMISSION_GROUP', 'permission.group.delete', 'PERMISSION_GROUP_DELETE', 1),
    ('NOTIFICATION', 'notification.access', 'NOTIFICATION_ACCESS', 1);

INSERT INTO `core_permission` (`group_id`, `name`, `code`, `weight`, `created_at`, `updated_at`)
SELECT pg.`id`, bp.`name`, bp.`code`, bp.`weight`, NOW(), NOW()
FROM `bootstrap_permission` bp
INNER JOIN `core_permission_group` pg ON pg.`code` = bp.`group_code`
ON DUPLICATE KEY UPDATE
    `group_id` = VALUES(`group_id`),
    `name` = VALUES(`name`),
    `weight` = VALUES(`weight`),
    `updated_at` = VALUES(`updated_at`);

DROP TEMPORARY TABLE `bootstrap_permission`;

INSERT IGNORE INTO `core_role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `core_role` r
CROSS JOIN `core_permission` p
WHERE r.`code` = 'ADMIN';
