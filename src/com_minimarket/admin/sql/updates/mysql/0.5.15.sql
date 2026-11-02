-- 0.5.15 additive: no existing attribute or product data is rewritten.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_multi_values` (
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`product_id`,`attribute_id`,`value_text`),
    KEY `idx_mm_multi_facet` (`attribute_id`,`value_text`,`product_id`),
    CONSTRAINT `fk_mm_multi_product` FOREIGN KEY (`product_id`) REFERENCES `#__minimarket_products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mm_multi_attr` FOREIGN KEY (`attribute_id`) REFERENCES `#__minimarket_attributes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__minimarket_attribute_values`
    ADD COLUMN `sample_image` VARCHAR(1024) NOT NULL DEFAULT '' AFTER `color_hex`;
ALTER TABLE `#__minimarket_product_videos`
    ADD COLUMN `poster` VARCHAR(1024) NOT NULL DEFAULT '' AFTER `source`;
