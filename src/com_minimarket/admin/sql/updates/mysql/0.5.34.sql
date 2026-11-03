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
