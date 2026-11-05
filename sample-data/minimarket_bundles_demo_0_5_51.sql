-- MiniMarket 0.5.51: DEMONSTRATION BUNDLES for the user's MySQL 8.4 site.
-- Database: minimarket, prefix: nnop3_. Requires 0.5.51 schema and
-- minimarket_mysql84_nnop3_full_demo.sql already imported.
-- Additive only: NO DROP/DELETE/ALTER/TRUNCATE. Existing data never overwritten.
-- Images already exist: images/minimarket/sample-01.png..sample-10.png.
-- Can be rerun safely; all bundle/sku links validated against demo SKU values.
-- Products and prices are fictitious.
USE `minimarket`;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1090,101,'Подарочный комплект «Медвежата»','demo-bundle-bears','BUNDLE-1090','bundle',4990.00,0,0,
 '<p>Подарочный комплект из двух видов плюшевых медведей.</p>','<p>Подарочный комплект из двух видов плюшевых медведей. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',1,
 'images/minimarket/sample-02.png','Подарочный комплект «Медвежата»','Подарочный комплект «Медвежата» — MiniMarket','Подарочный комплект из двух видов плюшевых медведей.',
 'index,follow','Подарочный комплект «Медвежата»','Подарочный комплект из двух видов плюшевых медведей.','images/minimarket/sample-02.png','Подарочный комплект «Медвежата»',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=101)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1090 OR alias='demo-bundle-bears' OR sku='BUNDLE-1090');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-forest-toys'
SET p.brand_id=b.id
WHERE p.id=1090 AND p.sku='BUNDLE-1090' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-soft-friends'
WHERE p.id=1090 AND p.sku='BUNDLE-1090'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1002,NULL,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1002 AND c.sku='TOY-1002'
WHERE b.id=1090 AND b.sku='BUNDLE-1090' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1002
 AND i.component_variant_id IS NULL
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1003,NULL,2,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1003 AND c.sku='TOY-1003'
WHERE b.id=1090 AND b.sku='BUNDLE-1090' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1003
 AND i.component_variant_id IS NULL
);

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1091,104,'Набор юного инженера','demo-bundle-engineer','BUNDLE-1091','bundle',6290.00,0,0,
 '<p>Комплект двух конструкторов в конкретных SKU.</p>','<p>Комплект двух конструкторов в конкретных SKU. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',2,
 'images/minimarket/sample-07.png','Набор юного инженера','Набор юного инженера — MiniMarket','Комплект двух конструкторов в конкретных SKU.',
 'index,follow','Набор юного инженера','Комплект двух конструкторов в конкретных SKU.','images/minimarket/sample-07.png','Набор юного инженера',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=104)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1091 OR alias='demo-bundle-engineer' OR sku='BUNDLE-1091');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-geo-lab'
SET p.brand_id=b.id
WHERE p.id=1091 AND p.sku='BUNDLE-1091' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-stem'
WHERE p.id=1091 AND p.sku='BUNDLE-1091'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1012,7004,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_product_variants` v ON v.id=7004 AND v.product_id=1012 AND v.sku='SKU-1012-SET60-BLUE'
WHERE b.id=1091 AND b.sku='BUNDLE-1091' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1012
 AND i.component_variant_id=7004
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1016,7007,1,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_product_variants` v ON v.id=7007 AND v.product_id=1016 AND v.sku='SKU-1016-SET32'
WHERE b.id=1091 AND b.sku='BUNDLE-1091' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1016
 AND i.component_variant_id=7007
);

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1092,101,'Большой комплект мягких друзей','demo-bundle-friends','BUNDLE-1092','bundle',7390.00,0,0,
 '<p>Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.</p>','<p>Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',3,
 'images/minimarket/sample-03.png','Большой комплект мягких друзей','Большой комплект мягких друзей — MiniMarket','Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.',
 'index,follow','Большой комплект мягких друзей','Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.','images/minimarket/sample-03.png','Большой комплект мягких друзей',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=101)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1092 OR alias='demo-bundle-friends' OR sku='BUNDLE-1092');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-forest-toys'
SET p.brand_id=b.id
WHERE p.id=1092 AND p.sku='BUNDLE-1092' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-gifts'
WHERE p.id=1092 AND p.sku='BUNDLE-1092'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1090,NULL,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1090 AND c.product_type='bundle'
WHERE b.id=1092 AND b.sku='BUNDLE-1092' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1090
 AND i.component_variant_id IS NULL
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1003,NULL,1,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1003 AND c.sku='TOY-1003'
WHERE b.id=1092 AND b.sku='BUNDLE-1092' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1003
 AND i.component_variant_id IS NULL
);

COMMIT;
SELECT p.id,p.title,p.sku,COUNT(i.id) AS component_rows
FROM `nnop3_minimarket_products` p
LEFT JOIN `nnop3_minimarket_bundle_items` i ON i.bundle_product_id=p.id
WHERE p.product_type='bundle'
GROUP BY p.id,p.title,p.sku ORDER BY p.id;
