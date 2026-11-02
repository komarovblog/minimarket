-- 0.5.14: text presets, dedicated color swatches and product video library.
-- Safe additive tables/columns only. No product IDs, old option IDs, SKU,
-- prices, stock, images, gallery or URLs are rewritten.
ALTER TABLE `#__minimarket_attributes`
    ADD COLUMN `text_options` TEXT NULL AFTER `number_options`;
ALTER TABLE `#__minimarket_attribute_values`
    ADD COLUMN `color_hex` CHAR(7) NOT NULL DEFAULT '' AFTER `code`;
CREATE TABLE IF NOT EXISTS `#__minimarket_product_videos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `source` VARCHAR(1024) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_mm_video_product_order` (`product_id`,`sort_order`,`id`),
    CONSTRAINT `fk_mm_video_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products`(`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
