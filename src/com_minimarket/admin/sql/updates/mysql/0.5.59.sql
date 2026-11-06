-- MiniMarket 0.5.59: independent price badge display and optional floating promo video.
-- Additive only: preserve prices, SKU IDs, media and video sources.
ALTER TABLE `#__minimarket_products`
 ADD COLUMN `badge_sale_display` VARCHAR(8) NOT NULL DEFAULT 'percent' AFTER `badge_benefit`,
 ADD COLUMN `badge_benefit_display` VARCHAR(8) NOT NULL DEFAULT 'money' AFTER `badge_sale_display`;
ALTER TABLE `#__minimarket_product_videos`
 ADD COLUMN `is_promo` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `poster`;
