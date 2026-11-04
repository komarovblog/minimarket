-- 0.5.39: one product price, optional SKU label, and gallery visibility/cover by physical variant ID.
-- Additive; no saved variant/image IDs, product prices, stocks or files are rewritten.
ALTER TABLE `#__minimarket_product_variants`
  MODIFY COLUMN `sku` VARCHAR(100) NULL DEFAULT NULL;

ALTER TABLE `#__minimarket_product_images`
  ADD COLUMN `visibility_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `alt`;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_image_variants` (
  `image_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  `is_cover` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`, `variant_id`),
  KEY `idx_mm_product_image_variant` (`variant_id`,`image_id`),
  CONSTRAINT `fk_mm_product_image_target_image` FOREIGN KEY (`image_id`)
    REFERENCES `#__minimarket_product_images`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_product_image_target_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
