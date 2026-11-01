-- 0.5.3: image references only, no files/URLs/SKU/prices/stock migrated.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `path` VARCHAR(1024) NOT NULL,
    `alt` VARCHAR(255) NOT NULL DEFAULT '',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_product_image_unique` (`product_id`, `path`(191)),
    KEY `idx_mm_product_image_order` (`product_id`, `sort_order`, `id`),
    CONSTRAINT `fk_mm_product_image_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products` (`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
