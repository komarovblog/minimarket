-- 0.5.83: additive reviews and SEO landings. No historical content is changed.
CREATE TABLE IF NOT EXISTS `#__minimarket_reviews` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_key` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `author_name` VARCHAR(120) NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `title` VARCHAR(140) NOT NULL DEFAULT '',
    `body` TEXT NOT NULL,
    `photo_path` VARCHAR(1024) NOT NULL DEFAULT '',
    `video_url` VARCHAR(1024) NOT NULL DEFAULT '',
    `source` VARCHAR(16) NOT NULL DEFAULT 'site',
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_review_key` (`review_key`),
    KEY `idx_mm_review_moderation` (`state`,`created_at`),
    KEY `idx_mm_review_product` (`product_id`,`state`,`created_at`),
    CONSTRAINT `fk_mm_review_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Published filter landing pages use a separate canonical SEO URL.
CREATE TABLE IF NOT EXISTS `#__minimarket_seo_filters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `alias` VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `heading` VARCHAR(255) NOT NULL,
    `introtext` TEXT NULL,
    `description` MEDIUMTEXT NULL,
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT 'index,follow',
    `filters_json` TEXT NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_seofilter_alias` (`alias`),
    KEY `idx_mm_seofilter_category_state` (`category_id`,`state`),
    CONSTRAINT `fk_mm_seofilter_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
