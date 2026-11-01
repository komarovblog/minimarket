-- Additive 0.4.1 upgrade; does not rewrite or delete existing categories.
ALTER TABLE `#__minimarket_categories`
 ADD COLUMN `introtext` TEXT NULL,
 ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `image` VARCHAR(1024) NOT NULL DEFAULT '',
 ADD COLUMN `image_alt` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `show_children` TINYINT NOT NULL DEFAULT -1,
 ADD COLUMN `show_products` TINYINT NOT NULL DEFAULT -1;
