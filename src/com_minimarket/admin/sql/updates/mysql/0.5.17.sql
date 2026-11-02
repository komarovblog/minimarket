-- 0.5.17: real variant SKUs under ONE product URL; additive, no product records rewritten.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_variants` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `product_id` INT UNSIGNED NOT NULL,
 `sku` VARCHAR(100) NOT NULL,
 `selection_json` TEXT NOT NULL,
 `signature` CHAR(64) NOT NULL,
 `price` DECIMAL(15,2) NOT NULL DEFAULT 0,
 `stock` INT UNSIGNED NOT NULL DEFAULT 0,
 `image` VARCHAR(1024) NOT NULL DEFAULT '',
 `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
 `state` TINYINT NOT NULL DEFAULT 1,
 `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `idx_mm_variant_combo` (`product_id`,`signature`),
 UNIQUE KEY `idx_mm_variant_sku` (`sku`),
 KEY `idx_mm_variant_product` (`product_id`,`state`,`sort_order`),
 CONSTRAINT `fk_mm_variant_product` FOREIGN KEY (`product_id`) REFERENCES `#__minimarket_products`(`id`)
  ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__minimarket_product_variant_images` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `variant_id` INT UNSIGNED NOT NULL,
 `path` VARCHAR(1024) NOT NULL,
 `alt` VARCHAR(255) NOT NULL DEFAULT '',
 `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `idx_mm_variant_img_unique` (`variant_id`,`path`(191)),
 KEY `idx_mm_variant_img_order` (`variant_id`,`sort_order`,`id`),
 CONSTRAINT `fk_mm_variant_img_variant` FOREIGN KEY (`variant_id`) REFERENCES `#__minimarket_product_variants`(`id`)
 ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
