-- 0.5.28: separate inventory per physical size/color SKU is the default for NEW products.
-- Do not update any existing products: explicitly chosen shared-stock mode (0)
-- and all historical SKU IDs, prices, quantities and URLs are preserved.
ALTER TABLE `#__minimarket_products`
  ALTER COLUMN `option_stock_mode` SET DEFAULT 1;
