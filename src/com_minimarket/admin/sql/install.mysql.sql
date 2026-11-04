CREATE TABLE IF NOT EXISTS `#__minimarket_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `introtext` TEXT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `image` VARCHAR(1024) NOT NULL DEFAULT '',
    `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `show_children` TINYINT NOT NULL DEFAULT -1,
    `show_products` TINYINT NOT NULL DEFAULT -1,
    `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
    `og_title` VARCHAR(255) NOT NULL DEFAULT '',
    `og_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_category_alias` (`alias`),
    KEY `idx_mm_category_parent_state` (`parent_id`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NOT NULL DEFAULT '',
    `price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `option_stock_mode` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `introtext` TEXT NULL,
    `description` TEXT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `image` VARCHAR(1024) NOT NULL DEFAULT '',
    `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
    `og_title` VARCHAR(255) NOT NULL DEFAULT '',
    `og_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_product_alias` (`alias`),
    KEY `idx_mm_product_category_state` (`category_id`, `state`),
    CONSTRAINT `fk_mm_product_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `#__minimarket_product_videos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `source` VARCHAR(1024) NOT NULL,
    `poster` VARCHAR(1024) NOT NULL DEFAULT '',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_mm_video_product_order` (`product_id`,`sort_order`,`id`),
    CONSTRAINT `fk_mm_video_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products`(`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_attributes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `input_type` VARCHAR(16) NOT NULL DEFAULT 'list',
    `input_mode` VARCHAR(24) NOT NULL DEFAULT 'auto',
    `number_options` VARCHAR(1024) NOT NULL DEFAULT '',
    `text_options` TEXT NULL,
    `number_unit` VARCHAR(32) NOT NULL DEFAULT '',
    `number_min` DECIMAL(18,4) NULL,
    `number_max` DECIMAL(18,4) NULL,
    `number_step` DECIMAL(18,4) NULL,
    `is_multiple` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_filterable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `filter_mode` VARCHAR(24) NOT NULL DEFAULT 'none',
    `filter_multiple` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_attribute_code` (`code`),
    KEY `idx_mm_attribute_state_order` (`state`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_attribute_values` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attribute_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `color_hex` CHAR(7) NOT NULL DEFAULT '',
    `sample_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_attribute_value_code` (`attribute_id`, `code`),
    KEY `idx_mm_attribute_value_state_order` (`attribute_id`, `state`, `sort_order`),
    CONSTRAINT `fk_mm_value_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_category_attributes` (
    `category_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `required` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`category_id`, `attribute_id`),
    KEY `idx_mm_category_attribute_reverse` (`attribute_id`, `enabled`),
    CONSTRAINT `fk_mm_catattr_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_mm_catattr_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_scalars` (
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` TEXT NULL,
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

-- Multi-select text preset values (0.5.15)
CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_multi_values` (
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`product_id`,`attribute_id`,`value_text`),
    KEY `idx_mm_multi_facet` (`attribute_id`,`value_text`,`product_id`),
    CONSTRAINT `fk_mm_multi_product` FOREIGN KEY (`product_id`) REFERENCES `#__minimarket_products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mm_multi_attr` FOREIGN KEY (`attribute_id`) REFERENCES `#__minimarket_attributes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
 `description_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0,
 `introtext_override` TEXT NULL,
 `description_override` TEXT NULL,
 `video_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS `#__minimarket_product_variant_videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `variant_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `source` VARCHAR(1024) NOT NULL,
  `poster` VARCHAR(1024) NOT NULL DEFAULT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_sku_video_unique` (`variant_id`,`source`(191)),
  KEY `idx_mm_sku_video_sort` (`variant_id`,`sort_order`,`id`),
  CONSTRAINT `fk_mm_sku_video_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__minimarket_variant_value_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `value_id` INT UNSIGNED NOT NULL,
  `path` VARCHAR(1024) NOT NULL,
  `alt` VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_value_photo_unique` (`value_id`,`path`(191)),
  KEY `idx_mm_value_photo_sort` (`value_id`,`sort_order`,`id`),
  CONSTRAINT `fk_mm_value_photo_value` FOREIGN KEY (`value_id`)
    REFERENCES `#__minimarket_variant_dimension_values`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'similar',
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_product_group_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_group_members` (
  `product_id` INT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`,`product_id`),
  KEY `idx_mm_group_member_product` (`product_id`),
  KEY `idx_mm_group_member_order` (`group_id`,`sort_order`,`product_id`),
  CONSTRAINT `fk_mm_group_member_product` FOREIGN KEY (`product_id`)
    REFERENCES `#__minimarket_products`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_group_member_group` FOREIGN KEY (`group_id`)
    REFERENCES `#__minimarket_product_groups`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stable external group identity for a future importer (no importer in this release).
CREATE TABLE IF NOT EXISTS `#__minimarket_import_group_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `external_group_key` VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`,`external_group_key`),
  KEY `idx_mm_import_group` (`group_id`),
  CONSTRAINT `fk_mm_import_group` FOREIGN KEY (`group_id`)
    REFERENCES `#__minimarket_product_groups`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0.5.34: global reusable order options and category inheritance.
-- Additive only: no existing products, SKU rows, stock, galleries or local axes changed.
CREATE TABLE IF NOT EXISTS `#__minimarket_order_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_order_option_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_order_option_values` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `color_hex` VARCHAR(7) NOT NULL DEFAULT '',
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_order_option_value_code` (`option_id`, `code`),
  KEY `idx_mm_order_option_value_sort` (`option_id`, `sort_order`, `id`),
  CONSTRAINT `fk_mm_order_option_value_option` FOREIGN KEY (`option_id`)
    REFERENCES `#__minimarket_order_options`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_category_order_options` (
  `category_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `mode` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`category_id`, `option_id`),
  KEY `idx_mm_cat_order_options_option` (`option_id`),
  CONSTRAINT `fk_mm_cat_order_options_category` FOREIGN KEY (`category_id`)
    REFERENCES `#__minimarket_categories`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_cat_order_options_option` FOREIGN KEY (`option_id`)
    REFERENCES `#__minimarket_order_options`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0.5.35: stable external keys across imported catalog entities.
-- No change to products, SKU tables, old import keys or media data.
CREATE TABLE IF NOT EXISTS `#__minimarket_import_entity_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `entity_kind` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL,
  `external_key` VARCHAR(128) COLLATE utf8mb4_bin NOT NULL,
  `local_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`,`entity_kind`,`external_key`),
  KEY `idx_mm_import_entity_local` (`entity_kind`,`local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
