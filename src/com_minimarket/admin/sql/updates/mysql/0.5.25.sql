-- 0.5.25 / SKU-v3 B2.2. Additive only; no historical SKU, URL, stock or media rewrites.
ALTER TABLE `#__minimarket_product_variants`
  ADD COLUMN `description_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN `introtext_override` TEXT NULL,
  ADD COLUMN `description_override` TEXT NULL,
  ADD COLUMN `video_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_variant_videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `variant_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `source` VARCHAR(1024) NOT NULL,
  `poster` VARCHAR(1024) NOT NULL DEFAULT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_sku_video_unique` (`variant_id`, `source`(191)),
  KEY `idx_mm_sku_video_sort` (`variant_id`,`sort_order`,`id`),
  CONSTRAINT `fk_mm_sku_video_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_variant_value_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `value_id` INT UNSIGNED NOT NULL,
  `path` VARCHAR(1024) NOT NULL,
  `alt` VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_value_photo_unique` (`value_id`, `path`(191)),
  KEY `idx_mm_value_photo_sort` (`value_id`,`sort_order`,`id`),
  CONSTRAINT `fk_mm_value_photo_value` FOREIGN KEY (`value_id`)
    REFERENCES `#__minimarket_variant_dimension_values`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
