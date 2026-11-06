-- MiniMarket 0.5.54 — OPTIONAL, additive examples for the already populated
-- demo catalog on MySQL 8.4, database minimarket, prefix nnop3_.
-- Install com_minimarket >= 0.5.54 first. This is NOT a schema migration.
-- Requires previous demo bundles and (for the savings examples) 0.5.52
-- percentage pricing mode or manually configured discounts.
-- Existing user-selected fields are never changed if already customized.
USE `minimarket`;
START TRANSACTION;
-- Demonstrate a category explicitly showing bundles (default inherit=-1).
UPDATE `nnop3_minimarket_categories`
SET show_bundles=1
WHERE id=101 AND alias='myagkie-igrushki' AND show_bundles=-1;
-- Show money saved on demo bundles only when percentage pricing was chosen.
UPDATE `nnop3_minimarket_products`
SET badge_benefit=1
WHERE id IN (1091,1092) AND sku=CONCAT('BUNDLE-',id)
  AND product_type='bundle' AND bundle_price_mode='discount'
  AND bundle_discount_pct>0 AND badge_benefit=0;
COMMIT;
SELECT id,title,sku,bundle_price_mode,bundle_discount_pct,badge_benefit
FROM `nnop3_minimarket_products` WHERE id IN (1090,1091,1092)
ORDER BY id;
SELECT id,title,show_products,show_bundles
FROM `nnop3_minimarket_categories` WHERE id=101;
