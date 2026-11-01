-- Additive SEO/OG columns for category editor; do not change existing products or URLs.
ALTER TABLE `#__minimarket_categories`
 ADD COLUMN `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `meta_description` VARCHAR(320) NOT NULL DEFAULT '',
 ADD COLUMN `meta_robots` VARCHAR(32) NOT NULL DEFAULT '',
 ADD COLUMN `og_title` VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN `og_description` VARCHAR(320) NOT NULL DEFAULT '',
 ADD COLUMN `og_image` VARCHAR(1024) NOT NULL DEFAULT '',
 ADD COLUMN `og_image_alt` VARCHAR(255) NOT NULL DEFAULT '';
