-- Additive 0.5.0 product editorial fields; no SKU, price, stock, ID or URL rewrites.
ALTER TABLE `#__minimarket_products`
 ADD COLUMN `introtext` TEXT NULL,
 ADD COLUMN `description` TEXT NULL,
 ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `image` VARCHAR(1024) NOT NULL DEFAULT '',
 ADD COLUMN `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
 ADD COLUMN `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
 ADD COLUMN `og_title` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `og_description` VARCHAR(320) NOT NULL DEFAULT '',
 ADD COLUMN `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
 ADD COLUMN `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '';
