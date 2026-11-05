-- Safe additive migration. Existing products stay simple, stock and SKU unchanged.
ALTER TABLE `#__minimarket_products`
  ADD COLUMN `product_type` VARCHAR(12) NOT NULL DEFAULT 'simple' AFTER `brand_id`;

-- 0.5.51: bundles use ordinary product cards and own prices/SEO.
-- No changes to existing SKU, stock, gallery or descriptions.
CREATE TABLE IF NOT EXISTS `#__minimarket_bundle_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bundle_product_id` INT UNSIGNED NOT NULL,
    `component_product_id` INT UNSIGNED NOT NULL,
    `component_variant_id` INT UNSIGNED NULL DEFAULT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_mm_bundle_rows` (`bundle_product_id`,`sort_order`,`id`),
    KEY `idx_mm_bundle_component` (`component_product_id`),
    KEY `idx_mm_bundle_variant` (`component_variant_id`),
    CONSTRAINT `fk_mm_bundle_parent` FOREIGN KEY (`bundle_product_id`)
      REFERENCES `#__minimarket_products` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_mm_bundle_component` FOREIGN KEY (`component_product_id`)
      REFERENCES `#__minimarket_products` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_mm_bundle_variant` FOREIGN KEY (`component_variant_id`)
      REFERENCES `#__minimarket_product_variants` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `ck_mm_bundle_quantity` CHECK (`quantity` BETWEEN 1 AND 1000),
    CONSTRAINT `ck_mm_bundle_no_self` CHECK (`bundle_product_id` <> `component_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

