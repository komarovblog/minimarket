-- 0.5.37: stable global option/value IDs on product-local SKU selection keys.
-- Additive only. No backfill of historical dimensions and no SKU or stock rewrite.
ALTER TABLE `#__minimarket_variant_dimensions`
  ADD COLUMN `source_option_id` INT UNSIGNED NULL DEFAULT NULL AFTER `product_id`,
  ADD UNIQUE KEY `idx_mm_dim_product_source` (`product_id`, `source_option_id`),
  ADD CONSTRAINT `fk_mm_dim_source_option`
    FOREIGN KEY (`source_option_id`)
    REFERENCES `#__minimarket_order_options`(`id`)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE `#__minimarket_variant_dimension_values`
  ADD COLUMN `source_value_id` INT UNSIGNED NULL DEFAULT NULL AFTER `dimension_id`,
  ADD UNIQUE KEY `idx_mm_dimval_source` (`dimension_id`, `source_value_id`),
  ADD CONSTRAINT `fk_mm_dimval_source_value`
    FOREIGN KEY (`source_value_id`)
    REFERENCES `#__minimarket_order_option_values`(`id`)
    ON UPDATE RESTRICT ON DELETE RESTRICT;
