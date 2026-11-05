-- MiniMarket 0.5.52, optional pricing examples on existing demo bundles.
-- Target: minimarket database, nnop3_ table prefix, MySQL 8.4.
-- Requires com_minimarket 0.5.52 and the 0.5.51 bundle demo already loaded.
-- One fixed price, one 10% discount, one 15% nested-kit discount.
-- Never changes unknown products or overrides an already configured discount.
USE `minimarket`;
START TRANSACTION;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='fixed',bundle_discount_pct=0
WHERE id=1090 AND sku='BUNDLE-1090' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='discount',bundle_discount_pct=10.00
WHERE id=1091 AND sku='BUNDLE-1091' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='discount',bundle_discount_pct=15.00
WHERE id=1092 AND sku='BUNDLE-1092' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
COMMIT;
SELECT id,title,sku,bundle_price_mode,bundle_discount_pct,price
FROM `nnop3_minimarket_products`
WHERE id IN (1090,1091,1092) AND product_type='bundle' ORDER BY id;
