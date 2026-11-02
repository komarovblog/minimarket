-- MiniMarket 0.5.5: category-specific attribute assignments.
-- Explicit 1 enables, explicit 0 blocks inherited attributes; no row inherits.
-- This migration never updates products, SKUs, prices, stocks, categories or galleries.
CREATE TABLE IF NOT EXISTS `#__minimarket_category_attributes` (
    `category_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`category_id`, `attribute_id`),
    KEY `idx_mm_category_attribute_reverse` (`attribute_id`, `enabled`),
    CONSTRAINT `fk_mm_catattr_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_mm_catattr_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
