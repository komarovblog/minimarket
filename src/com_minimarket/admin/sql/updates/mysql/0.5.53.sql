-- MiniMarket 0.5.53: additive catalogue flags for products AND bundles.
-- All pre-existing prices, SKU, images, collections, stock and bundles are retained.
-- NULL old_price means no comparison price. NULL days means badge never expires.
-- All activation timestamps remain NULL until a user enables the flag in Joomla.
ALTER TABLE `#__minimarket_products`
    ADD COLUMN `old_price` DECIMAL(15,2) NULL DEFAULT NULL AFTER `price`,
    ADD COLUMN `badge_sale` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `old_price`,
    ADD COLUMN `badge_new` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `badge_sale`,
    ADD COLUMN `badge_new_days` INT UNSIGNED NULL DEFAULT NULL AFTER `badge_new`,
    ADD COLUMN `badge_new_since` DATETIME NULL DEFAULT NULL AFTER `badge_new_days`,
    ADD COLUMN `badge_popular` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `badge_new_since`,
    ADD COLUMN `badge_popular_days` INT UNSIGNED NULL DEFAULT NULL AFTER `badge_popular`,
    ADD COLUMN `badge_popular_since` DATETIME NULL DEFAULT NULL AFTER `badge_popular_days`;
