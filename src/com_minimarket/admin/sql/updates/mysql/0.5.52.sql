-- MiniMarket 0.5.52: explicit kit pricing modes. No existing price or stock is overwritten.
ALTER TABLE `#__minimarket_products`
  ADD COLUMN `bundle_price_mode` VARCHAR(12) NOT NULL DEFAULT 'fixed' AFTER `product_type`,
  ADD COLUMN `bundle_discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `bundle_price_mode`;
