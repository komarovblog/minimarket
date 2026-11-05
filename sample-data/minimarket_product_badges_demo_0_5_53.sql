-- MiniMarket 0.5.53: OPTIONAL demonstration of New, Popular, and Sale flags.
-- MySQL 8.4, database minimarket, prefix nnop3_. Run ONLY after upgrading
-- MiniMarket to 0.5.53. Does not need a new catalog import.
-- Changes only untouched demo products with exact TOY-/BUNDLE- SKU.
-- Re-running is harmless; existing user-edited badges are left alone.
-- All test promotions begin at SQL import time (UTC), not at 0.5.53 build time.
-- No DROP / DELETE / TRUNCATE / ALTER and no changes to sale prices or inventory.
USE `minimarket`;
START TRANSACTION;

-- New for 7 days + sale discount calculated from old price of 1990.
UPDATE `nnop3_minimarket_products`
SET old_price=1990.00,badge_sale=1,
    badge_new=1,badge_new_days=7,badge_new_since=UTC_TIMESTAMP()
WHERE id=1001 AND sku='TOY-1001' AND price=1490.00
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Popular for 30 days. Leave existing product price 2890 unchanged.
UPDATE `nnop3_minimarket_products`
SET badge_popular=1,badge_popular_days=30,badge_popular_since=UTC_TIMESTAMP()
WHERE id=1002 AND sku='TOY-1002'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Indefinite New (days = NULL).
UPDATE `nnop3_minimarket_products`
SET badge_new=1,badge_new_days=NULL,badge_new_since=UTC_TIMESTAMP()
WHERE id=1003 AND sku='TOY-1003'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Indefinite Popular.
UPDATE `nnop3_minimarket_products`
SET badge_popular=1,badge_popular_days=NULL,badge_popular_since=UTC_TIMESTAMP()
WHERE id=1004 AND sku='TOY-1004'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Fixed-price bundle: own price 4990, old price 6990 => ceil(28.61...) = 29%.
UPDATE `nnop3_minimarket_products`
SET old_price=6990.00,badge_sale=1
WHERE id=1090 AND sku='BUNDLE-1090' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND price=4990.00
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

COMMIT;
SELECT id,title,sku,price,old_price,badge_sale,
       badge_new,badge_new_days,badge_new_since,
       badge_popular,badge_popular_days,badge_popular_since
FROM `nnop3_minimarket_products`
WHERE id IN (1001,1002,1003,1004,1090) ORDER BY id;
