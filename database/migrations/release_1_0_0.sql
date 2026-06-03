-- Release 1.0.0 schema changes.
-- Safe to run more than once on MySQL 8.x.

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

DROP TABLE IF EXISTS `core_component`;

DROP TABLE IF EXISTS `core_menu`;

DELETE rp
FROM `core_role_permission` rp
INNER JOIN `core_permission` p ON p.`id` = rp.`permission_id`
LEFT JOIN `core_permission_group` pg ON pg.`id` = p.`group_id`
WHERE p.`code` IN (
    'SETTING_ACCESS',
    'SETTING_VIEW_ALL',
    'SETTING_UPDATE'
)
OR pg.`code` IN ('SETTING', 'COMPONENT', 'MENU');

DELETE FROM `core_permission`
WHERE `code` IN (
    'SETTING_ACCESS',
    'SETTING_VIEW_ALL',
    'SETTING_UPDATE'
);

DELETE p
FROM `core_permission` p
INNER JOIN `core_permission_group` pg ON pg.`id` = p.`group_id`
WHERE pg.`code` IN ('SETTING', 'COMPONENT', 'MENU');

DELETE FROM `core_permission_group`
WHERE `code` IN ('SETTING', 'COMPONENT', 'MENU');

DROP TABLE IF EXISTS `core_setting`;

DELETE rp
FROM `core_role_permission` rp
INNER JOIN `core_permission` p ON p.`id` = rp.`permission_id`
WHERE p.`code` IN (
    'TASK_VIEW',
    'PERMISSION_GROUP_VIEW',
    'PERMISSION_VIEW',
    'USER_VIEW',
    'ROLE_VIEW'
);

DELETE FROM `core_permission`
WHERE `code` IN (
    'TASK_VIEW',
    'PERMISSION_GROUP_VIEW',
    'PERMISSION_VIEW',
    'USER_VIEW',
    'ROLE_VIEW'
);
