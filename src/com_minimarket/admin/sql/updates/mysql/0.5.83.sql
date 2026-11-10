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
