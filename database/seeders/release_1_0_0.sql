-- Release 1.0.0 seed data.
-- Safe to run more than once on MySQL 8.x.

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

INSERT INTO `core_permission_group` (`name`, `code`, `created_at`, `updated_at`)
SELECT 'notification', 'NOTIFICATION', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `core_permission_group` WHERE `code` = 'NOTIFICATION'
);

INSERT INTO `core_permission` (`group_id`, `name`, `code`, `weight`, `created_at`, `updated_at`)
SELECT pg.`id`, 'notification.access', 'NOTIFICATION_ACCESS', 1, NOW(), NOW()
FROM `core_permission_group` pg
WHERE pg.`code` = 'NOTIFICATION'
ON DUPLICATE KEY UPDATE
    `group_id` = VALUES(`group_id`),
    `name` = VALUES(`name`),
    `weight` = VALUES(`weight`),
    `updated_at` = VALUES(`updated_at`);

INSERT INTO `core_role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `core_role` r
JOIN `core_permission` p ON p.`code` IN ('NOTIFICATION_ACCESS')
WHERE r.`code` IN ('ADMIN', 'SVILUPPATORE')
ON DUPLICATE KEY UPDATE `permission_id` = VALUES(`permission_id`);
