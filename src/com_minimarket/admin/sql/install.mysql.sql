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

CREATE TABLE IF NOT EXISTS `#__minimarket_attributes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `input_type` VARCHAR(16) NOT NULL DEFAULT 'list',
    `is_variant` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_filterable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
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
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_attribute_value_code` (`attribute_id`, `code`),
    KEY `idx_mm_attribute_value_state_order` (`attribute_id`, `state`, `sort_order`),
    CONSTRAINT `fk_mm_value_attribute` FOREIGN KEY (`attribute_id`)
        REFERENCES `#__minimarket_attributes` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
