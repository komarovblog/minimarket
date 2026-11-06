-- MiniMarket 0.5.58: category layout and descendant visibility.
-- Additive only; every previous category and its settings is preserved.
ALTER TABLE `#__minimarket_categories`
 ADD COLUMN `show_descendant_products` TINYINT NOT NULL DEFAULT -1 AFTER `show_bundles`,
 ADD COLUMN `show_descendant_bundles` TINYINT NOT NULL DEFAULT -1 AFTER `show_descendant_products`,
 ADD COLUMN `product_layout` VARCHAR(8) NOT NULL DEFAULT 'inherit' AFTER `show_descendant_bundles`,
 ADD COLUMN `cards_mobile` TINYINT NOT NULL DEFAULT -1 AFTER `product_layout`,
 ADD COLUMN `cards_tablet` TINYINT NOT NULL DEFAULT -1 AFTER `cards_mobile`,
 ADD COLUMN `cards_desktop` TINYINT NOT NULL DEFAULT -1 AFTER `cards_tablet`,
 ADD COLUMN `cards_wide` TINYINT NOT NULL DEFAULT -1 AFTER `cards_desktop`;
