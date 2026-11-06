-- 0.5.54: category bundle visibility and independent kit-benefit badge.
ALTER TABLE `#__minimarket_categories`
    ADD COLUMN `show_bundles` TINYINT NOT NULL DEFAULT -1 AFTER `show_products`;
ALTER TABLE `#__minimarket_products`
    ADD COLUMN `badge_benefit` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `badge_sale`;
