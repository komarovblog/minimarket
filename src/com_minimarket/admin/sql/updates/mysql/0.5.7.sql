-- MiniMarket 0.5.7: typed product attributes and product-selected list values.
-- Nothing migrates or changes old product ID/category/SKU/price/stock/URL/media.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_scalars` (
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` VARCHAR(1024) NULL,
    `value_number` DECIMAL(18,4) NULL,
    `value_boolean` TINYINT UNSIGNED NULL,
    PRIMARY KEY (`product_id`, `attribute_id`),
    KEY `idx_mm_pa_scalar_attr` (`attribute_id`),
    CONSTRAINT `fk_mm_pa_scalar_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products` (`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_mm_pa_scalar_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_options` (
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`product_id`, `attribute_id`, `value_id`),
    KEY `idx_mm_pa_option_value_product` (`value_id`, `product_id`),
    KEY `idx_mm_pa_option_attribute` (`attribute_id`, `value_id`),
    CONSTRAINT `fk_mm_pa_option_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products` (`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_mm_pa_option_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_mm_pa_option_value` FOREIGN KEY (`value_id`)
        REFERENCES `#__minimarket_attribute_values` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
