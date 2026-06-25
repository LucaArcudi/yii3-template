-- Release 1.0.1 production schema patch.
-- Safe to run more than once on MySQL 8.x.

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
