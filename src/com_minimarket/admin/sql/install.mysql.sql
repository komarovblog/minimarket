CREATE TABLE IF NOT EXISTS `#__minimarket_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
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
    `state` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mm_product_alias` (`alias`),
    KEY `idx_mm_product_category_state` (`category_id`, `state`),
    CONSTRAINT `fk_mm_product_category` FOREIGN KEY (`category_id`)
        REFERENCES `#__minimarket_categories` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The same dictionaries are installed on a fresh Joomla installation.
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
