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
    `show_bundles` TINYINT NOT NULL DEFAULT -1,
    `show_descendant_products` TINYINT NOT NULL DEFAULT -1,
    `show_descendant_bundles` TINYINT NOT NULL DEFAULT -1,
    `product_layout` VARCHAR(8) NOT NULL DEFAULT 'inherit',
    `cards_xs` TINYINT NOT NULL DEFAULT -1,
    `cards_sm` TINYINT NOT NULL DEFAULT -1,
    `cards_md` TINYINT NOT NULL DEFAULT -1,
    `cards_lg` TINYINT NOT NULL DEFAULT -1,
    `cards_xl` TINYINT NOT NULL DEFAULT -1,
    `cards_xxl` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_xs` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_sm` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_md` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_lg` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_xl` TINYINT NOT NULL DEFAULT -1,
    `subcategory_cards_xxl` TINYINT NOT NULL DEFAULT -1,
    `products_per_page` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `filter_price_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
    `filter_sku_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
    `filter_stock_mode` TINYINT NOT NULL DEFAULT -1,
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

-- Global catalog dictionaries. A product has one optional brand; products can
-- belong to multiple independent collections. Neither influences SKU or stock.
CREATE TABLE IF NOT EXISTS `#__minimarket_brands` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `introtext` TEXT NULL,
    `description` MEDIUMTEXT NULL,
    `image` VARCHAR(1024) NOT NULL DEFAULT '',
    `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `website_url` VARCHAR(1024) NOT NULL DEFAULT '',
    `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
    `og_title` VARCHAR(255) NOT NULL DEFAULT '',
    `og_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_brand_alias` (`alias`),
    KEY `idx_mm_brand_state_title` (`state`, `title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_collections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `introtext` TEXT NULL,
    `description` MEDIUMTEXT NULL,
    `image` VARCHAR(1024) NOT NULL DEFAULT '',
    `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
    `og_title` VARCHAR(255) NOT NULL DEFAULT '',
    `og_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_collection_alias` (`alias`),
    KEY `idx_mm_collection_state_title` (`state`, `title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `brand_id` INT UNSIGNED NULL DEFAULT NULL,
    `product_type` VARCHAR(12) NOT NULL DEFAULT 'simple',
    `delivery_type` VARCHAR(12) NOT NULL DEFAULT 'physical',
    `digital_asset_key` VARCHAR(80) NOT NULL DEFAULT '',
    `digital_filename` VARCHAR(255) NOT NULL DEFAULT '',
    `digital_delivery_mode` VARCHAR(16) NOT NULL DEFAULT 'file',
    `digital_delivery_note` VARCHAR(2000) NOT NULL DEFAULT '',
    `bundle_price_mode` VARCHAR(12) NOT NULL DEFAULT 'fixed',
    `bundle_discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NOT NULL DEFAULT '',
    `price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `cost_price` DECIMAL(15, 2) NULL DEFAULT NULL,
    `old_price` DECIMAL(15,2) NULL DEFAULT NULL,
    `badge_sale` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `badge_benefit` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `badge_sale_display` VARCHAR(8) NOT NULL DEFAULT 'percent',
    `badge_benefit_display` VARCHAR(8) NOT NULL DEFAULT 'money',
    `badge_new` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `badge_new_days` INT UNSIGNED NULL DEFAULT NULL,
    `badge_new_since` DATETIME NULL DEFAULT NULL,
    `badge_popular` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `badge_popular_days` INT UNSIGNED NULL DEFAULT NULL,
    `badge_popular_since` DATETIME NULL DEFAULT NULL,
    `stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `option_stock_mode` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `shipping_weight_grams` INT UNSIGNED NOT NULL DEFAULT 0,
    `shipping_volume_cm3` INT UNSIGNED NOT NULL DEFAULT 0,
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
    KEY `idx_mm_product_brand` (`brand_id`),
    CONSTRAINT `fk_mm_product_brand` FOREIGN KEY (`brand_id`)
        REFERENCES `#__minimarket_brands` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mm_product_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_collections` (
    `product_id` INT UNSIGNED NOT NULL,
    `collection_id` INT UNSIGNED NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`product_id`, `collection_id`),
    KEY `idx_mm_collection_product` (`collection_id`, `product_id`),
    CONSTRAINT `fk_mm_product_collection_product` FOREIGN KEY (`product_id`)
      REFERENCES `#__minimarket_products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mm_product_collection_collection` FOREIGN KEY (`collection_id`)
      REFERENCES `#__minimarket_collections` (`id`) ON DELETE RESTRICT
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
    `is_promo` TINYINT UNSIGNED NOT NULL DEFAULT 0,
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
    `input_type` VARCHAR(16) NOT NULL DEFAULT 'text',
    `value_source` VARCHAR(12) NOT NULL DEFAULT 'manual',
    `color_display` VARCHAR(12) NOT NULL DEFAULT 'both',
    `display_location` VARCHAR(12) NOT NULL DEFAULT 'inherit',
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
    `value_payload` MEDIUMTEXT NULL,
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
    `show_in_list` TINYINT UNSIGNED NOT NULL DEFAULT 1,
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
    UNIQUE KEY `idx_mm_pa_one_value` (`product_id`, `attribute_id`),
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

-- Canonical manual multi-values storage.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_attribute_free_values` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `attribute_id` INT UNSIGNED NOT NULL,
    `value_text` MEDIUMTEXT NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_mm_free_product_attr` (`product_id`, `attribute_id`, `sort_order`),
    UNIQUE KEY `idx_mm_free_one_value` (`product_id`, `attribute_id`),
    KEY `idx_mm_free_attr` (`attribute_id`),
    CONSTRAINT `fk_mm_free_product` FOREIGN KEY (`product_id`)
      REFERENCES `#__minimarket_products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mm_free_attribute` FOREIGN KEY (`attribute_id`)
      REFERENCES `#__minimarket_attributes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0.5.77: archive of legacy multi-value selections for migrated stores.
-- New stores will never populate this table.
CREATE TABLE IF NOT EXISTS #__minimarket_attribute_multivalue_archive (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    attribute_id INT UNSIGNED NOT NULL,
    source_kind VARCHAR(16) NOT NULL,
    value_id INT UNSIGNED NULL,
    value_text MEDIUMTEXT NULL,
    previous_sort_order INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_mm_attr_archive_product (product_id, attribute_id),
    KEY idx_mm_attr_archive_kind (source_kind, attribute_id)
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
  `display_location` VARCHAR(12) NOT NULL DEFAULT 'inherit',
  `filter_mode` VARCHAR(24) NOT NULL DEFAULT 'checkboxes',
  `filter_multiple` TINYINT NOT NULL DEFAULT 1,
  `filter_search` TINYINT NOT NULL DEFAULT 0,
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
  `sample_image` VARCHAR(1024) NOT NULL DEFAULT '',
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
  `show_in_list` TINYINT UNSIGNED NOT NULL DEFAULT 1,
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

-- 0.5.39: one product price, optional SKU label, and gallery visibility/cover by physical variant ID.
-- Additive; no saved variant/image IDs, product prices, stocks or files are rewritten.
ALTER TABLE `#__minimarket_product_variants`
  MODIFY `sku` VARCHAR(100) NULL DEFAULT NULL;

ALTER TABLE `#__minimarket_product_images`
  ADD COLUMN `visibility_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `alt`;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_image_variants` (
  `image_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  `is_cover` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`, `variant_id`),
  KEY `idx_mm_product_image_variant` (`variant_id`,`image_id`),
  CONSTRAINT `fk_mm_product_image_target_image` FOREIGN KEY (`image_id`)
    REFERENCES `#__minimarket_product_images`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_product_image_target_variant` FOREIGN KEY (`variant_id`)
    REFERENCES `#__minimarket_product_variants`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MiniMarket 0.5.61: independent, non-destructive feed definitions.
-- Feed settings never alter product/category rows and do not alter Joomla core.
CREATE TABLE IF NOT EXISTS `#__minimarket_feeds` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `company` VARCHAR(200) NOT NULL DEFAULT '',
    `provider` VARCHAR(32) NOT NULL DEFAULT 'yandex_direct',
    `state` TINYINT NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `selection_mode` VARCHAR(12) NOT NULL DEFAULT 'auto',
    `include_descendants` TINYINT NOT NULL DEFAULT 1,
    `include_bundles` TINYINT NOT NULL DEFAULT 1,
    `category_ids_json` MEDIUMTEXT NOT NULL,
    `include_product_ids_json` MEDIUMTEXT NOT NULL,
    `exclude_product_ids_json` MEDIUMTEXT NOT NULL,
    `access_key` CHAR(40) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_feed_access_key` (`access_key`),
    KEY `idx_mm_feed_state_provider` (`state`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MiniMarket 0.5.78: checkout request ledger. No payment, shipping,
-- invoice, inventory hold/deduction or email delivery at this stage.

CREATE TABLE IF NOT EXISTS #__minimarket_pickup_points (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(150) NOT NULL,
 address VARCHAR(512) NOT NULL,
 schedule VARCHAR(1000) NOT NULL DEFAULT '',
 map_url VARCHAR(1024) NOT NULL DEFAULT '',
 fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
 state TINYINT NOT NULL DEFAULT 1,
 ordering INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY idx_mm_pickup_state (state,ordering,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automatic customer-facing offers; separate from one-time coupon redemptions.
-- Managed recommendations: independent marketing relations, not SKU/variant groups.
CREATE TABLE IF NOT EXISTS #__minimarket_recommendation_settings (
    product_id INT UNSIGNED NOT NULL,
    similar_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
    together_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(product_id),
    CONSTRAINT fk_mm_recommendation_source FOREIGN KEY(product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_recommendation_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_product_id INT UNSIGNED NOT NULL,
    target_product_id INT UNSIGNED NOT NULL,
    kind VARCHAR(12) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_recommendation_link (source_product_id,target_product_id,kind),
    KEY idx_mm_recommendation_target(target_product_id),
    CONSTRAINT fk_mm_recommendation_link_source FOREIGN KEY(source_product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mm_recommendation_link_target FOREIGN KEY(target_product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_auto_discounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    state TINYINT UNSIGNED NOT NULL DEFAULT 0,
    discount_type VARCHAR(10) NOT NULL DEFAULT 'percent',
    amount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    basis_points INT UNSIGNED NOT NULL DEFAULT 0,
    applies_to VARCHAR(12) NOT NULL DEFAULT 'all',
    target_ids_json TEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_mm_auto_discount_period (state,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    checkout_key CHAR(32) NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(254) NOT NULL DEFAULT '',
    customer_comment TEXT NULL,
    delivery_method VARCHAR(16) NOT NULL DEFAULT 'pickup',
    pickup_point_id INT UNSIGNED NULL DEFAULT NULL,
    delivery_city VARCHAR(120) NOT NULL DEFAULT '',
    delivery_country CHAR(2) NOT NULL DEFAULT 'RU',
    delivery_region VARCHAR(120) NOT NULL DEFAULT '',
    delivery_postcode VARCHAR(16) NOT NULL DEFAULT '',
    delivery_address VARCHAR(512) NOT NULL DEFAULT '',
    delivery_rate_source VARCHAR(16) NOT NULL DEFAULT 'none',
    delivery_zone_id INT UNSIGNED NULL,
    delivery_fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    payment_method VARCHAR(24) NOT NULL DEFAULT 'on_receipt',
    digital_recipient_email VARCHAR(254) NOT NULL DEFAULT '',
    payment_status VARCHAR(16) NOT NULL DEFAULT 'unpaid',
    payment_reference VARCHAR(128) NOT NULL DEFAULT '',
    payment_url VARCHAR(2048) NOT NULL DEFAULT '',
    paid_at DATETIME NULL,
    fulfillment_status VARCHAR(24) NOT NULL DEFAULT 'awaiting_assembly',
    fulfilled_at DATETIME NULL,
    order_source VARCHAR(16) NOT NULL DEFAULT 'site',
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    currency CHAR(3) NOT NULL DEFAULT 'RUB',
    items_total_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    promo_code_id INT UNSIGNED NULL,
    auto_discount_id INT UNSIGNED NULL DEFAULT NULL,
    auto_discount_title VARCHAR(160) NOT NULL DEFAULT '',
    promo_code VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    discount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_cents BIGINT UNSIGNED NOT NULL,
    item_count INT UNSIGNED NOT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    reserved_until DATETIME NULL,
    reservation_state VARCHAR(16) NOT NULL DEFAULT 'legacy',
    ready_at DATETIME NULL,
    accepted_at DATETIME NULL,
    accepted_by INT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_reason VARCHAR(512) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_mm_order_checkout_key (checkout_key),
    KEY idx_mm_order_status_created (status, created_at),
    KEY idx_mm_order_user_created (user_id, created_at),
    KEY idx_mm_order_reservation (reservation_state,reserved_until),
    KEY idx_mm_order_payment_reservation (payment_status,reserved_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- New orders only: legacy orders remain untouched and have no fictitious holds.
-- Available stock decreases atomically on order creation. The ledger makes release
-- idempotent, even with bundles and shared variant stock.
CREATE TABLE IF NOT EXISTS #__minimarket_stock_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    source_kind VARCHAR(8) NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'held',
    created_at DATETIME NOT NULL,
    released_at DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_allocation_key (order_id,source_kind,source_id),
    KEY idx_mm_allocation_order (order_id,status),
    CONSTRAINT fk_mm_stock_allocation_order FOREIGN KEY(order_id)
      REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Notification preferences are owned by the real Joomla user ID, not a copy
-- of the CMS account. Only installed/allowed channels are exposed in the UI.
CREATE TABLE IF NOT EXISTS #__minimarket_notification_preferences (
  user_id INT UNSIGNED NOT NULL,
  event_code VARCHAR(40) NOT NULL,
  channel_code VARCHAR(80) NOT NULL,
  enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY(user_id,event_code,channel_code),
  KEY idx_mm_notify_user_enabled (user_id,enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One execution record per event reference and channel. No external API keys
-- and no full personal message body are stored in the log.
CREATE TABLE IF NOT EXISTS #__minimarket_notification_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  event_code VARCHAR(40) NOT NULL,
  channel_code VARCHAR(80) NOT NULL,
  event_reference VARCHAR(80) NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  error_code VARCHAR(40) NOT NULL DEFAULT '',
  PRIMARY KEY(id),
  UNIQUE KEY uq_mm_notify_event (order_id,event_code,channel_code,event_reference),
  KEY idx_mm_notify_status_created (status,created_at),
  CONSTRAINT fk_mm_notify_order FOREIGN KEY(order_id)
    REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verified manual payment receipts only. Pending payment obligations remain
-- on the order; never synthesize bank transactions for historical paid orders.
CREATE TABLE IF NOT EXISTS #__minimarket_payment_records (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 order_id INT UNSIGNED NOT NULL,
 amount_cents BIGINT UNSIGNED NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'RUB',
 method VARCHAR(24) NOT NULL,
 source VARCHAR(24) NOT NULL DEFAULT 'manual',
 external_reference VARCHAR(128) NOT NULL,
 recorded_by INT UNSIGNED NOT NULL DEFAULT 0,
 confirmed_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY uq_mm_payment_origin_reference (order_id,source,external_reference),
 KEY idx_mm_payment_order (order_id,confirmed_at),
 CONSTRAINT fk_mm_payment_order FOREIGN KEY(order_id)
     REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Native courier delivery zones; plugins remain independent of these tariffs.
CREATE TABLE IF NOT EXISTS #__minimarket_delivery_zones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(160) NOT NULL,
  country_code CHAR(2) NOT NULL DEFAULT 'RU',
  region VARCHAR(120) NOT NULL DEFAULT '',
  cities TEXT NOT NULL,
  postcode_prefixes TEXT NOT NULL,
  rate_mode VARCHAR(16) NOT NULL DEFAULT 'fixed',
  base_fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  rate_per_unit_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  free_from_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  min_cart_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  max_weight_grams BIGINT UNSIGNED NOT NULL DEFAULT 0,
  max_volume_cm3 BIGINT UNSIGNED NOT NULL DEFAULT 0,
  estimated_days_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  estimated_days_max SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  priority SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  state TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_mm_zone_active_priority (state,priority,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipment fulfillment is independent of order payment and catalog records.
CREATE TABLE IF NOT EXISTS #__minimarket_shipments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    carrier VARCHAR(16) NOT NULL DEFAULT 'own',
    tracking_number VARCHAR(120) NOT NULL DEFAULT '',
    actual_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL,
    cost_reference VARCHAR(128) NOT NULL DEFAULT '',
    cost_confirmed_at DATETIME NULL DEFAULT NULL,
    cost_confirmed_by INT UNSIGNED NULL DEFAULT NULL,
    reason VARCHAR(24) NOT NULL DEFAULT 'initial',
    status VARCHAR(24) NOT NULL DEFAULT 'awaiting_assembly',
    problem_status VARCHAR(16) NOT NULL DEFAULT 'none',
    problem_code VARCHAR(24) NOT NULL DEFAULT '',
    note TEXT NULL,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(id),
    KEY idx_mm_shipment_order(order_id),
    KEY idx_mm_shipment_status(status,created_at),
    CONSTRAINT fk_mm_shipment_order FOREIGN KEY(order_id)
        REFERENCES #__minimarket_orders(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Revisions only: reporting sums the current confirmed shipment cost ONCE.
CREATE TABLE IF NOT EXISTS #__minimarket_shipment_cost_revisions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipment_id INT UNSIGNED NOT NULL,
    previous_cost_cents BIGINT UNSIGNED NULL,
    confirmed_cost_cents BIGINT UNSIGNED NOT NULL,
    evidence_reference VARCHAR(128) NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_mm_shipment_cost_revision (shipment_id,id),
    CONSTRAINT fk_mm_shipment_cost_revision FOREIGN KEY(shipment_id)
        REFERENCES #__minimarket_shipments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable delivery events, never inventory movements.
CREATE TABLE IF NOT EXISTS #__minimarket_shipment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipment_id INT UNSIGNED NOT NULL,
    event_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'manual',
    source_reference VARCHAR(128) NOT NULL,
    event_code VARCHAR(32) NOT NULL,
    problem_code VARCHAR(24) NOT NULL DEFAULT '',
    note VARCHAR(2000) NOT NULL DEFAULT '',
    occurred_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_shipment_event_key (shipment_id,event_key),
    UNIQUE KEY uq_mm_shipment_source_ref (shipment_id,source,source_reference),
    KEY idx_mm_shipment_events_date (shipment_id,occurred_at,id),
    CONSTRAINT fk_mm_shipment_event_parent FOREIGN KEY(shipment_id)
      REFERENCES #__minimarket_shipments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- CRM notes are tied to verified Joomla user IDs; guests are not merged.
CREATE TABLE IF NOT EXISTS #__minimarket_customer_notes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 author_user_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 KEY idx_mm_customer_note_user (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal tickets and messages. Never invent a customer identity.
CREATE TABLE IF NOT EXISTS #__minimarket_support_tickets (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NULL,
 user_id INT UNSIGNED NOT NULL DEFAULT 0,
 subject VARCHAR(180) NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'new',
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY idx_mm_ticket_status (status,created_at),
 KEY idx_mm_ticket_customer (user_id,created_at),
 KEY idx_mm_ticket_order (order_id),
 CONSTRAINT fk_mm_ticket_order FOREIGN KEY(order_id)
  REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_support_messages (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 ticket_id INT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 internal TINYINT NOT NULL DEFAULT 1,
 author_user_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 KEY idx_mm_support_timeline (ticket_id,id),
 CONSTRAINT fk_mm_support_message_ticket FOREIGN KEY(ticket_id)
  REFERENCES #__minimarket_support_tickets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NOT NULL DEFAULT 0,
    sku VARCHAR(100) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL,
    unit_cents BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_cents BIGINT UNSIGNED NOT NULL,
    unit_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL,
    line_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL,
    delivery_type VARCHAR(12) NOT NULL DEFAULT 'physical',
    digital_asset_key VARCHAR(80) NOT NULL DEFAULT '',
    digital_filename VARCHAR(255) NOT NULL DEFAULT '',
    digital_delivery_mode VARCHAR(16) NOT NULL DEFAULT 'file',
    digital_delivery_note VARCHAR(2000) NOT NULL DEFAULT '',
    digital_fulfilled_at DATETIME NULL,
    digital_manual_message TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_mm_order_line_order (order_id),
    CONSTRAINT fk_mm_order_line_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A return is a separate case, not a rewrite of immutable order snapshots.
-- Optional shipment_id refers to the delivery that triggered the return.
CREATE TABLE IF NOT EXISTS #__minimarket_returns (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    shipment_id INT UNSIGNED NULL,
    reason VARCHAR(24) NOT NULL DEFAULT 'defective',
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    note TEXT NULL,
    resolution VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_mm_return_order (order_id,created_at),
    KEY idx_mm_return_status (status,created_at),
    KEY idx_mm_return_shipment (shipment_id),
    CONSTRAINT fk_mm_return_order FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mm_return_shipment FOREIGN KEY (shipment_id)
        REFERENCES #__minimarket_shipments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_return_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    return_id INT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mm_return_item (return_id,order_item_id),
    KEY idx_mm_return_item_order_line (order_item_id),
    CONSTRAINT fk_mm_return_item_return FOREIGN KEY(return_id)
        REFERENCES #__minimarket_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_mm_return_item_order_item FOREIGN KEY(order_item_id)
        REFERENCES #__minimarket_order_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_order_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    old_value VARCHAR(32) NOT NULL,
    new_value VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_mm_order_event (order_id, created_at),
    CONSTRAINT fk_mm_order_event_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_digital_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    encrypted_code TEXT NOT NULL,
    order_item_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    assigned_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mm_digital_key_fingerprint (product_id, fingerprint),
    KEY idx_mm_digital_key_available (product_id, order_item_id, id),
    CONSTRAINT fk_mm_digital_key_product FOREIGN KEY (product_id)
        REFERENCES #__minimarket_products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_digital_grants (
    order_item_id BIGINT UNSIGNED NOT NULL,
    token_nonce CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_downloads INT UNSIGNED NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL,
    last_sent_at DATETIME NULL,
    PRIMARY KEY (order_item_id),
    CONSTRAINT fk_mm_digital_grant_item FOREIGN KEY (order_item_id)
        REFERENCES #__minimarket_order_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_order_custom_values (
    order_id INT UNSIGNED NOT NULL,
    field_code VARCHAR(40) NOT NULL,
    field_title VARCHAR(120) NOT NULL,
    field_value TEXT NOT NULL,
    PRIMARY KEY (order_id, field_code),
    CONSTRAINT fk_mm_order_custom_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MiniMarket 0.5.79 — account/phone verification foundation.
-- Existing orders with user_id=0 are retained; application no longer inserts
-- anonymous orders. All writes to these new tables will require an SMS provider.
CREATE TABLE IF NOT EXISTS #__minimarket_user_phones (
    user_id INT UNSIGNED NOT NULL,
    phone_e164 VARCHAR(16) NOT NULL,
    verified_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY idx_mm_phone_unique (phone_e164)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_phone_challenges (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    challenge_key CHAR(32) NOT NULL,
    phone_e164 VARCHAR(16) NOT NULL,
    intent VARCHAR(16) NOT NULL,
    code_digest CHAR(64) NOT NULL,
    nonce CHAR(32) NOT NULL,
    session_digest CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_mm_phone_challenge_key (challenge_key),
    KEY idx_mm_phone_challenge_phone_time (phone_e164, created_at),
    KEY idx_mm_phone_challenge_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly confirmed-goods plans. Missing plan is NULL in reports, not zero.
CREATE TABLE IF NOT EXISTS #__minimarket_sales_plans (
    period_year SMALLINT UNSIGNED NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL,
    target_goods_cents BIGINT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(period_year,period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- An append-only audit of target changes. These are NOT stock movements.
CREATE TABLE IF NOT EXISTS #__minimarket_sales_plan_revisions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_year SMALLINT UNSIGNED NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL,
    previous_target_cents BIGINT UNSIGNED NULL,
    target_goods_cents BIGINT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    KEY idx_mm_plan_revision_period(period_year,period_month,created_at),
    CONSTRAINT fk_mm_sales_plan_revision FOREIGN KEY(period_year,period_month)
        REFERENCES #__minimarket_sales_plans(period_year,period_month)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MiniMarket reviews: independent data, no Joomla core table changes.
CREATE TABLE IF NOT EXISTS `#__minimarket_reviews` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_key` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `author_name` VARCHAR(120) NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `title` VARCHAR(140) NOT NULL DEFAULT '',
    `body` TEXT NOT NULL,
    `photo_path` VARCHAR(1024) NOT NULL DEFAULT '',
    `video_url` VARCHAR(1024) NOT NULL DEFAULT '',
    `source` VARCHAR(16) NOT NULL DEFAULT 'site',
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_review_key` (`review_key`),
    KEY `idx_mm_review_moderation` (`state`,`created_at`),
    KEY `idx_mm_review_product` (`product_id`,`state`,`created_at`),
    CONSTRAINT `fk_mm_review_product` FOREIGN KEY (`product_id`)
        REFERENCES `#__minimarket_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Published filter landing pages use a separate canonical SEO URL.
CREATE TABLE IF NOT EXISTS `#__minimarket_seo_filters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `alias` VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `heading` VARCHAR(255) NOT NULL,
    `introtext` TEXT NULL,
    `description` MEDIUMTEXT NULL,
    `image` VARCHAR(1024) NOT NULL DEFAULT '',
    `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_title` VARCHAR(255) NOT NULL DEFAULT '',
    `og_description` VARCHAR(320) NOT NULL DEFAULT '',
    `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
    `meta_robots` VARCHAR(32) NOT NULL DEFAULT 'index,follow',
    `filters_json` TEXT NOT NULL,
    `conditions_json` TEXT NULL DEFAULT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_seofilter_alias` (`alias`),
    KEY `idx_mm_seofilter_category_state` (`category_id`,`state`),
    CONSTRAINT `fk_mm_seofilter_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount codes are an admin catalog only until the checkout promotion
-- rules are agreed. Never invent a discount or mark any order as redeemed.
CREATE TABLE IF NOT EXISTS #__minimarket_promo_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    discount_type VARCHAR(12) NOT NULL DEFAULT 'fixed',
    percent_value DECIMAL(5,2) NULL DEFAULT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    min_cart_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    usage_limit INT UNSIGNED NULL,
    per_customer_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    applies_to VARCHAR(12) NOT NULL DEFAULT 'all',
    target_ids_json TEXT NULL,
    combinable TINYINT UNSIGNED NOT NULL DEFAULT 0,
    state TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY idx_mm_promo_code(code),
    KEY idx_mm_promo_state(state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable, one-per-order promo redemption: all updates share the order transaction.
CREATE TABLE IF NOT EXISTS #__minimarket_promo_redemptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  promo_code_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  customer_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  discount_cents BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_mm_redemption_order (order_id),
  KEY idx_mm_redemption_customer (promo_code_id,customer_key),
  CONSTRAINT fk_mm_promo_redemption_order FOREIGN KEY (order_id)
    REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mm_promo_redemption_code FOREIGN KEY (promo_code_id)
    REFERENCES #__minimarket_promo_codes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
