-- MiniMarket 0.5.61: independent, non-destructive feed definitions.
-- Feed settings never alter product/category rows and do not alter Joomla core.
CREATE TABLE IF NOT EXISTS `#__minimarket_feeds` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `company` VARCHAR(200) NOT NULL DEFAULT '',
    `provider` VARCHAR(32) NOT NULL DEFAULT 'yandex_direct',
    `state` TINYINT NOT NULL DEFAULT 0,
    `selection_mode` VARCHAR(12) NOT NULL DEFAULT 'auto',
    `include_descendants` TINYINT NOT NULL DEFAULT 1,
    `include_bundles` TINYINT NOT NULL DEFAULT 1,
    `category_ids_json` MEDIUMTEXT NOT NULL,
    `include_product_ids_json` MEDIUMTEXT NOT NULL,
    `exclude_product_ids_json` MEDIUMTEXT NOT NULL,
    `access_key` CHAR(40) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_feed_access_key` (`access_key`),
    KEY `idx_mm_feed_state_provider` (`state`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
