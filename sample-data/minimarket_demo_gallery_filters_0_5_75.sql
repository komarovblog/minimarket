-- MiniMarket 0.5.75 additional demo media/facets for existing Joomla 6 catalog.
-- Target database: minimarket; Joomla prefix: nnop3_.
-- Idempotent, no DROP/TRUNCATE/ALTER, never removes user's products.
-- Reuses only existing images/minimarket/sample-01.png ... sample-10.png.
-- Back up the DB before import. Main demo product IDs 1001..1077 and bundles 1090..1092.
USE `minimarket`;
SET NAMES utf8mb4;
START TRANSACTION;


-- Fix references left by earlier demo SQL for images never shipped.
UPDATE nnop3_minimarket_products AS p
SET p.image=CASE p.image
 WHEN 'images/minimarket/sample-11.png' THEN 'images/minimarket/sample-01.png'
 WHEN 'images/minimarket/sample-12.png' THEN 'images/minimarket/sample-02.png'
 WHEN 'images/minimarket/sample-13.png' THEN 'images/minimarket/sample-03.png'
 WHEN 'images/minimarket/sample-14.png' THEN 'images/minimarket/sample-04.png'
 WHEN 'images/minimarket/sample-15.png' THEN 'images/minimarket/sample-05.png'
 WHEN 'images/minimarket/sample-16.png' THEN 'images/minimarket/sample-06.png'
 WHEN 'images/minimarket/sample-17.png' THEN 'images/minimarket/sample-07.png'
 WHEN 'images/minimarket/sample-18.png' THEN 'images/minimarket/sample-08.png'
 WHEN 'images/minimarket/sample-19.png' THEN 'images/minimarket/sample-09.png'
 WHEN 'images/minimarket/sample-20.png' THEN 'images/minimarket/sample-10.png'
 ELSE p.image END,
    p.og_image=CASE p.og_image
 WHEN 'images/minimarket/sample-11.png' THEN 'images/minimarket/sample-01.png'
 WHEN 'images/minimarket/sample-12.png' THEN 'images/minimarket/sample-02.png'
 WHEN 'images/minimarket/sample-13.png' THEN 'images/minimarket/sample-03.png'
 WHEN 'images/minimarket/sample-14.png' THEN 'images/minimarket/sample-04.png'
 WHEN 'images/minimarket/sample-15.png' THEN 'images/minimarket/sample-05.png'
 WHEN 'images/minimarket/sample-16.png' THEN 'images/minimarket/sample-06.png'
 WHEN 'images/minimarket/sample-17.png' THEN 'images/minimarket/sample-07.png'
 WHEN 'images/minimarket/sample-18.png' THEN 'images/minimarket/sample-08.png'
 WHEN 'images/minimarket/sample-19.png' THEN 'images/minimarket/sample-09.png'
 WHEN 'images/minimarket/sample-20.png' THEN 'images/minimarket/sample-10.png'
 ELSE p.og_image END
WHERE (p.id BETWEEN 1001 AND 1077 OR p.id IN (1090,1091,1092))
  AND (p.sku LIKE 'TOY-%' OR p.sku LIKE 'BUNDLE-%')
  AND (p.image IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png') OR p.og_image IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png'));
UPDATE IGNORE nnop3_minimarket_product_images AS pic
JOIN nnop3_minimarket_products AS p ON p.id=pic.product_id
SET pic.path=CASE pic.path
 WHEN 'images/minimarket/sample-11.png' THEN 'images/minimarket/sample-01.png'
 WHEN 'images/minimarket/sample-12.png' THEN 'images/minimarket/sample-02.png'
 WHEN 'images/minimarket/sample-13.png' THEN 'images/minimarket/sample-03.png'
 WHEN 'images/minimarket/sample-14.png' THEN 'images/minimarket/sample-04.png'
 WHEN 'images/minimarket/sample-15.png' THEN 'images/minimarket/sample-05.png'
 WHEN 'images/minimarket/sample-16.png' THEN 'images/minimarket/sample-06.png'
 WHEN 'images/minimarket/sample-17.png' THEN 'images/minimarket/sample-07.png'
 WHEN 'images/minimarket/sample-18.png' THEN 'images/minimarket/sample-08.png'
 WHEN 'images/minimarket/sample-19.png' THEN 'images/minimarket/sample-09.png'
 WHEN 'images/minimarket/sample-20.png' THEN 'images/minimarket/sample-10.png'
 ELSE pic.path END
WHERE (p.id BETWEEN 1001 AND 1077 OR p.id IN (1090,1091,1092))
 AND (p.sku LIKE 'TOY-%' OR p.sku LIKE 'BUNDLE-%')
 AND pic.path IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png');
-- Remove only the remaining broken demo-only gallery rows that could not be
-- renamed because an equivalent valid gallery image already existed.
DELETE pic FROM nnop3_minimarket_product_images AS pic
JOIN nnop3_minimarket_products AS p ON p.id=pic.product_id
WHERE (p.id BETWEEN 1001 AND 1077 OR p.id IN (1090,1091,1092))
 AND (p.sku LIKE 'TOY-%' OR p.sku LIKE 'BUNDLE-%')
 AND pic.path IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png');
UPDATE nnop3_minimarket_categories
SET image=CASE image
 WHEN 'images/minimarket/sample-11.png' THEN 'images/minimarket/sample-01.png'
 WHEN 'images/minimarket/sample-12.png' THEN 'images/minimarket/sample-02.png'
 WHEN 'images/minimarket/sample-13.png' THEN 'images/minimarket/sample-03.png'
 WHEN 'images/minimarket/sample-14.png' THEN 'images/minimarket/sample-04.png'
 WHEN 'images/minimarket/sample-15.png' THEN 'images/minimarket/sample-05.png'
 WHEN 'images/minimarket/sample-16.png' THEN 'images/minimarket/sample-06.png'
 WHEN 'images/minimarket/sample-17.png' THEN 'images/minimarket/sample-07.png'
 WHEN 'images/minimarket/sample-18.png' THEN 'images/minimarket/sample-08.png'
 WHEN 'images/minimarket/sample-19.png' THEN 'images/minimarket/sample-09.png'
 WHEN 'images/minimarket/sample-20.png' THEN 'images/minimarket/sample-10.png'
 ELSE image END
WHERE id BETWEEN 101 AND 127 AND image IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png');
UPDATE nnop3_minimarket_product_variants AS v
JOIN nnop3_minimarket_products AS p ON p.id=v.product_id
SET v.image=CASE v.image
 WHEN 'images/minimarket/sample-11.png' THEN 'images/minimarket/sample-01.png'
 WHEN 'images/minimarket/sample-12.png' THEN 'images/minimarket/sample-02.png'
 WHEN 'images/minimarket/sample-13.png' THEN 'images/minimarket/sample-03.png'
 WHEN 'images/minimarket/sample-14.png' THEN 'images/minimarket/sample-04.png'
 WHEN 'images/minimarket/sample-15.png' THEN 'images/minimarket/sample-05.png'
 WHEN 'images/minimarket/sample-16.png' THEN 'images/minimarket/sample-06.png'
 WHEN 'images/minimarket/sample-17.png' THEN 'images/minimarket/sample-07.png'
 WHEN 'images/minimarket/sample-18.png' THEN 'images/minimarket/sample-08.png'
 WHEN 'images/minimarket/sample-19.png' THEN 'images/minimarket/sample-09.png'
 WHEN 'images/minimarket/sample-20.png' THEN 'images/minimarket/sample-10.png'
 ELSE v.image END
WHERE p.id BETWEEN 1001 AND 1077 AND p.sku LIKE 'TOY-%'
 AND v.image IN ('images/minimarket/sample-11.png','images/minimarket/sample-12.png','images/minimarket/sample-13.png','images/minimarket/sample-14.png','images/minimarket/sample-15.png','images/minimarket/sample-16.png','images/minimarket/sample-17.png','images/minimarket/sample-18.png','images/minimarket/sample-19.png','images/minimarket/sample-20.png');


-- Every demo product and bundle gets two additional working gallery images,
-- even if the product originally only had a main cover image.
INSERT IGNORE INTO nnop3_minimarket_product_images
(product_id,path,alt,sort_order)
SELECT p.id,
 CONCAT('images/minimarket/sample-',LPAD(MOD(p.id+v.shift_index,10)+1,2,'0'),'.png'),
 CONCAT(p.sku,' / demo angle ',v.shift_index+1),v.shift_index+1
FROM nnop3_minimarket_products AS p
CROSS JOIN (SELECT 0 AS shift_index UNION ALL SELECT 1) AS v
WHERE ((p.id BETWEEN 1001 AND 1077 AND p.sku=CONCAT('TOY-',p.id))
    OR (p.id IN (1090,1091,1092) AND p.sku=CONCAT('BUNDLE-',p.id)))
 AND p.product_type IN ('simple','bundle');


INSERT INTO nnop3_minimarket_attributes
(title,code,input_type,value_source,is_multiple,is_filterable,filter_mode,filter_multiple,sort_order,state)
SELECT 'Материал / демо','mm-demo-fabric','text','preset',1,1,'dropdown',1,401,1
WHERE NOT EXISTS (SELECT 1 FROM nnop3_minimarket_attributes WHERE code='mm-demo-fabric');

INSERT INTO nnop3_minimarket_attributes
(title,code,input_type,value_source,is_multiple,is_filterable,filter_mode,filter_multiple,sort_order,state)
SELECT 'Сезон / демо','mm-demo-season','text','preset',0,1,'dropdown',0,402,1
WHERE NOT EXISTS (SELECT 1 FROM nnop3_minimarket_attributes WHERE code='mm-demo-season');

INSERT INTO nnop3_minimarket_attributes
(title,code,input_type,value_source,is_multiple,is_filterable,filter_mode,filter_multiple,sort_order,state)
SELECT 'Вес / демо','mm-demo-weight','number','manual',0,1,'slider',0,403,1
WHERE NOT EXISTS (SELECT 1 FROM nnop3_minimarket_attributes WHERE code='mm-demo-weight');

INSERT INTO nnop3_minimarket_attributes
(title,code,input_type,value_source,is_multiple,is_filterable,filter_mode,filter_multiple,sort_order,state)
SELECT 'Подарочная упаковка / демо','mm-demo-gift','boolean','manual',0,1,'radio',0,404,1
WHERE NOT EXISTS (SELECT 1 FROM nnop3_minimarket_attributes WHERE code='mm-demo-gift');

INSERT INTO nnop3_minimarket_attributes
(title,code,input_type,value_source,is_multiple,is_filterable,filter_mode,filter_multiple,sort_order,state)
SELECT 'Стиль / демо','mm-demo-style','text','preset',1,1,'checkboxes',1,405,1
WHERE NOT EXISTS (SELECT 1 FROM nnop3_minimarket_attributes WHERE code='mm-demo-style');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Плюш','plush',0,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-fabric' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='plush');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Хлопок','cotton',1,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-fabric' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='cotton');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Пластик','plastic',2,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-fabric' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='plastic');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Керамика','ceramic',3,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-fabric' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='ceramic');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Всесезонный','all',0,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-season' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='all');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Летний','summer',1,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-season' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='summer');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Зимний','winter',2,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-season' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='winter');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Классика','classic',0,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-style' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='classic');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Яркий','bright',1,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-style' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='bright');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Природный','natural',2,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-style' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='natural');

INSERT INTO nnop3_minimarket_attribute_values
(attribute_id,title,code,sort_order,state)
SELECT a.id,'Творческий','creative',3,1
FROM nnop3_minimarket_attributes a
WHERE a.code='mm-demo-style' AND NOT EXISTS
 (SELECT 1 FROM nnop3_minimarket_attribute_values av WHERE av.attribute_id=a.id AND av.code='creative');

-- Bind attributes to demo storefront categories and their descendants.
INSERT IGNORE INTO nnop3_minimarket_category_attributes
(category_id,attribute_id,enabled,required)
SELECT c.id,a.id,1,0
FROM nnop3_minimarket_categories c
CROSS JOIN nnop3_minimarket_attributes a
WHERE c.id IN (101,102,103,104,105,106,107,108,109,128,129)
 AND a.code IN ('mm-demo-fabric','mm-demo-season','mm-demo-weight','mm-demo-gift','mm-demo-style');

-- Assign a varied preset material, season and style to actual demo items.
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
(product_id,attribute_id,value_id)
SELECT p.id,a.id,v.id
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-fabric'
JOIN nnop3_minimarket_attribute_values v ON v.attribute_id=a.id
 AND v.code=CASE
   WHEN p.id=1201 THEN 'cotton' WHEN p.id=1202 THEN 'ceramic'
   WHEN p.id BETWEEN 1001 AND 1010 THEN 'plush' ELSE 'plastic' END
WHERE (p.id BETWEEN 1001 AND 1018 OR p.id IN (1020,1021,1022,1023,1201,1202))
 AND p.state=1;
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
(product_id,attribute_id,value_id)
SELECT p.id,a.id,v.id
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-season'
JOIN nnop3_minimarket_attribute_values v ON v.attribute_id=a.id
 AND v.code=CASE MOD(p.id,3) WHEN 0 THEN 'winter' WHEN 1 THEN 'summer' ELSE 'all' END
WHERE (p.id BETWEEN 1001 AND 1018 OR p.id IN (1020,1021,1022,1023,1201,1202))
 AND p.state=1;
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
(product_id,attribute_id,value_id)
SELECT p.id,a.id,v.id
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-style'
JOIN nnop3_minimarket_attribute_values v ON v.attribute_id=a.id
 AND v.code=CASE MOD(p.id,4) WHEN 0 THEN 'classic' WHEN 1 THEN 'bright'
 WHEN 2 THEN 'natural' ELSE 'creative' END
WHERE (p.id BETWEEN 1001 AND 1018 OR p.id IN (1020,1021,1022,1023,1201,1202))
 AND p.state=1;
-- Deliberately assign two styles to some items to test OR-within-one-facet.
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
(product_id,attribute_id,value_id)
SELECT p.id,a.id,v.id
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-style'
JOIN nnop3_minimarket_attribute_values v ON v.attribute_id=a.id AND v.code='classic'
WHERE p.id IN (1001,1002,1005,1012,1014,1201) AND p.state=1;


-- Numeric sliders and boolean radio choices use canonical scalar storage.
INSERT IGNORE INTO nnop3_minimarket_product_attribute_scalars
(product_id,attribute_id,value_number)
SELECT p.id,a.id,CAST(ROUND(150+MOD(p.id,9)*185,2) AS DECIMAL(18,4))
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-weight'
WHERE (p.id BETWEEN 1001 AND 1018 OR p.id IN (1020,1021,1022,1023,1201,1202)) AND p.state=1;
INSERT IGNORE INTO nnop3_minimarket_product_attribute_scalars
(product_id,attribute_id,value_boolean)
SELECT p.id,a.id,IF(MOD(p.id,3)=0,0,1)
FROM nnop3_minimarket_products p
JOIN nnop3_minimarket_attributes a ON a.code='mm-demo-gift'
WHERE (p.id BETWEEN 1001 AND 1018 OR p.id IN (1020,1021,1022,1023,1201,1202)) AND p.state=1;
COMMIT;

SELECT 'demo_product_galleries' AS metric,COUNT(*) AS total
FROM nnop3_minimarket_product_images WHERE product_id BETWEEN 1001 AND 1092
UNION ALL SELECT 'demo_filter_attributes',COUNT(*)
FROM nnop3_minimarket_attributes WHERE code LIKE 'mm-demo-%'
UNION ALL SELECT 'demo_filter_values',COUNT(*)
FROM nnop3_minimarket_attribute_values v
JOIN nnop3_minimarket_attributes a ON a.id=v.attribute_id
WHERE a.code LIKE 'mm-demo-%';