-- Additive universal information-attribute model. No existing product values or SKU rows updated.
ALTER TABLE `#__minimarket_attributes`
    ADD COLUMN `value_source` VARCHAR(12) NOT NULL DEFAULT 'legacy' AFTER `input_mode`,
    ADD COLUMN `color_display` VARCHAR(12) NOT NULL DEFAULT 'both' AFTER `value_source`;

ALTER TABLE `#__minimarket_attribute_values`
    ADD COLUMN `value_payload` MEDIUMTEXT NULL AFTER `sample_image`;

-- 0.5.45: independent ordered manual multi-values; old text preset storage stays intact.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_free_values` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` MEDIUMTEXT NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_mm_free_product_attr` (`product_id`, `attribute_id`, `sort_order`),
    KEY `idx_mm_free_attr` (`attribute_id`),
    CONSTRAINT `fk_mm_free_product` FOREIGN KEY (`product_id`)
      REFERENCES `#__minimarket_products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mm_free_attribute` FOREIGN KEY (`attribute_id`)
      REFERENCES `#__minimarket_attributes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

