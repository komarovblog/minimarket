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

ALTER TABLE `#__minimarket_products`
    ADD COLUMN `brand_id` INT UNSIGNED NULL DEFAULT NULL AFTER `category_id`,
    ADD KEY `idx_mm_product_brand` (`brand_id`),
    ADD CONSTRAINT `fk_mm_product_brand` FOREIGN KEY (`brand_id`)
      REFERENCES `#__minimarket_brands` (`id`) ON DELETE RESTRICT;

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

