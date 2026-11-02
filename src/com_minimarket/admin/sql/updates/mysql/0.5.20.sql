-- 0.5.20: SKU model v2 foundation. Additive ONLY: no existing product/SKU migrated.
-- The dimension model is product-local; global attribute/category assignments are optional.
CREATE TABLE IF NOT EXISTS `#__minimarket_variant_dimensions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_dim_product_code` (`product_id`, `code`),
  KEY `idx_mm_dim_product_order` (`product_id`, `sort_order`, `id`),
  CONSTRAINT `fk_mm_dim_product` FOREIGN KEY (`product_id`)
    REFERENCES `#__minimarket_products` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_variant_dimension_values` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dimension_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `image` VARCHAR(1024) NOT NULL DEFAULT '',
  `color_hex` VARCHAR(7) NOT NULL DEFAULT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_dimval_dim_code` (`dimension_id`, `code`),
  KEY `idx_mm_dimval_order` (`dimension_id`, `sort_order`, `id`),
  CONSTRAINT `fk_mm_dimval_dimension` FOREIGN KEY (`dimension_id`)
    REFERENCES `#__minimarket_variant_dimensions` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_variant_value_assignments` (
  `variant_id` INT UNSIGNED NOT NULL,
  `dimension_id` INT UNSIGNED NOT NULL,
  `value_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`variant_id`, `dimension_id`),
  KEY `idx_mm_assign_value_variant` (`value_id`, `variant_id`),
  KEY `idx_mm_assign_dimension_value` (`dimension_id`, `value_id`),
  CONSTRAINT `fk_mm_assign_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_assign_dimension` FOREIGN KEY (`dimension_id`)
    REFERENCES `#__minimarket_variant_dimensions` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_mm_assign_value` FOREIGN KEY (`value_id`)
    REFERENCES `#__minimarket_variant_dimension_values` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- External identifiers are NOT SKU strings: source values remain stable on rename.
CREATE TABLE IF NOT EXISTS `#__minimarket_import_product_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `external_product_key` VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`, `external_product_key`),
  KEY `idx_mm_import_product` (`product_id`),
  CONSTRAINT `fk_mm_import_product` FOREIGN KEY (`product_id`)
    REFERENCES `#__minimarket_products` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_import_sku_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `external_sku_key` VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`, `external_sku_key`),
  KEY `idx_mm_import_variant` (`variant_id`),
  CONSTRAINT `fk_mm_import_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
