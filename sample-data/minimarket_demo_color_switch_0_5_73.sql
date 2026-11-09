-- MiniMarket 0.5.73: optional, additive DEMO (no catalog reset).
-- Import AFTER clean 0.5.73 SQL or a fully updated MiniMarket component.
-- Adds test products 1201/1202, two categories, 12 SKU combinations,
-- and SKU-specific front/detail photo links. Re-running does not duplicate IDs.
-- Images are generated separately by scripts/create-demo-images.ps1.
-- All data is invented for testing. Existing categories/products remain intact.
USE `minimarket`;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `nnop3_minimarket_categories`
(id,parent_id,title,alias,description,state,sort_order,image,image_alt)
VALUES
(128,0,'Одежда','demo-odezhda','<p>Одежда с демонстрационными вариантами цвета и размера.</p>',1,28,'images/minimarket/demo-tee-blue-front.png','Одежда / демо'),
(129,0,'Посуда','demo-posuda','<p>Посуда с демонстрационными вариантами цвета и объёма.</p>',1,29,'images/minimarket/demo-mug-red-front.png','Посуда / демо')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,introtext,description,sort_order,image,image_alt,state)
VALUES
(1201,128,'Футболка базовая','demo-tshirt-basic','DEMO-TSHIRT','simple',1490.00,60,1,'<p>Демонстрационный товар: при выборе цвета меняются фото и SKU.</p>','<p>Тестовый товар MiniMarket. Цвет определяет фотографии; размер определяет отдельный артикул SKU и остаток. Цена общая для всех вариантов.</p>',1,'images/minimarket/demo-tee-blue-front.png','Футболка синяя',1),
(1202,129,'Кружка керамическая','demo-mug-ceramic','DEMO-MUG','simple',890.00,35,1,'<p>Демонстрационный товар: при выборе цвета меняются фото и SKU.</p>','<p>Тестовый товар MiniMarket. Цвет определяет фотографии; объём определяет отдельный артикул SKU и остаток. Цена общая для всех вариантов.</p>',2,'images/minimarket/demo-mug-white-front.png','Кружка белая',1)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_variant_dimensions` (id,product_id,code,title,sort_order) VALUES
(5401,1201,'color','Цвет',0),
(5402,1201,'size','Размер',1),
(5403,1202,'color','Цвет',0),
(5404,1202,'volume','Объём',1)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_variant_dimension_values`
(id,dimension_id,code,title,image,color_hex,sort_order) VALUES
(6401,5401,'blue','Синий','images/minimarket/demo-tee-blue-front.png','#1959BF',0),
(6402,5401,'white','Белый','images/minimarket/demo-tee-white-front.png','#F2EFE6',1),
(6403,5401,'black','Чёрный','images/minimarket/demo-tee-black-front.png','#222A37',2),
(6411,5402,'s','S','images/minimarket/demo-tee-blue-front.png','',0),
(6412,5402,'m','M','images/minimarket/demo-tee-blue-front.png','',1),
(6413,5402,'l','L','images/minimarket/demo-tee-blue-front.png','',2),
(6421,5403,'white','Белая','images/minimarket/demo-mug-white-front.png','#EEE7DD',0),
(6422,5403,'red','Красная','images/minimarket/demo-mug-red-front.png','#C93F40',1),
(6431,5404,'300','300 мл','images/minimarket/demo-mug-white-front.png','',0),
(6432,5404,'450','450 мл','images/minimarket/demo-mug-white-front.png','',1)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_product_variants`
(id,product_id,sku,selection_json,signature,price,stock,image,image_alt,state,sort_order)
VALUES
(7401,1201,'DEMO-TEE-BLUE-S','{"color":"v:blue","size":"v:s"}',SHA2('{"color":"v:blue","size":"v:s"}',256),1490.00,12,'images/minimarket/demo-tee-blue-front.png','DEMO-TEE-BLUE-S',1,0),
(7402,1201,'DEMO-TEE-BLUE-M','{"color":"v:blue","size":"v:m"}',SHA2('{"color":"v:blue","size":"v:m"}',256),1490.00,18,'images/minimarket/demo-tee-blue-front.png','DEMO-TEE-BLUE-M',1,1),
(7403,1201,'DEMO-TEE-WHITE-S','{"color":"v:white","size":"v:s"}',SHA2('{"color":"v:white","size":"v:s"}',256),1490.00,8,'images/minimarket/demo-tee-white-front.png','DEMO-TEE-WHITE-S',1,2),
(7404,1201,'DEMO-TEE-WHITE-M','{"color":"v:white","size":"v:m"}',SHA2('{"color":"v:white","size":"v:m"}',256),1490.00,10,'images/minimarket/demo-tee-white-front.png','DEMO-TEE-WHITE-M',1,3),
(7405,1201,'DEMO-TEE-WHITE-L','{"color":"v:white","size":"v:l"}',SHA2('{"color":"v:white","size":"v:l"}',256),1490.00,9,'images/minimarket/demo-tee-white-front.png','DEMO-TEE-WHITE-L',1,4),
(7406,1201,'DEMO-TEE-BLACK-S','{"color":"v:black","size":"v:s"}',SHA2('{"color":"v:black","size":"v:s"}',256),1490.00,0,'images/minimarket/demo-tee-black-front.png','DEMO-TEE-BLACK-S',1,5),
(7407,1201,'DEMO-TEE-BLACK-M','{"color":"v:black","size":"v:m"}',SHA2('{"color":"v:black","size":"v:m"}',256),1490.00,15,'images/minimarket/demo-tee-black-front.png','DEMO-TEE-BLACK-M',1,6),
(7408,1201,'DEMO-TEE-BLACK-L','{"color":"v:black","size":"v:l"}',SHA2('{"color":"v:black","size":"v:l"}',256),1490.00,7,'images/minimarket/demo-tee-black-front.png','DEMO-TEE-BLACK-L',1,7),
(7411,1202,'DEMO-MUG-WHITE-300','{"color":"v:white","volume":"v:300"}',SHA2('{"color":"v:white","volume":"v:300"}',256),890.00,9,'images/minimarket/demo-mug-white-front.png','DEMO-MUG-WHITE-300',1,0),
(7412,1202,'DEMO-MUG-WHITE-450','{"color":"v:white","volume":"v:450"}',SHA2('{"color":"v:white","volume":"v:450"}',256),890.00,11,'images/minimarket/demo-mug-white-front.png','DEMO-MUG-WHITE-450',1,1),
(7413,1202,'DEMO-MUG-RED-300','{"color":"v:red","volume":"v:300"}',SHA2('{"color":"v:red","volume":"v:300"}',256),890.00,13,'images/minimarket/demo-mug-red-front.png','DEMO-MUG-RED-300',1,2),
(7414,1202,'DEMO-MUG-RED-450','{"color":"v:red","volume":"v:450"}',SHA2('{"color":"v:red","volume":"v:450"}',256),890.00,5,'images/minimarket/demo-mug-red-front.png','DEMO-MUG-RED-450',1,3)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_variant_value_assignments`
(variant_id,dimension_id,value_id) VALUES
(7401,5401,6401),
(7401,5402,6411),
(7402,5401,6401),
(7402,5402,6412),
(7403,5401,6402),
(7403,5402,6411),
(7404,5401,6402),
(7404,5402,6412),
(7405,5401,6402),
(7405,5402,6413),
(7406,5401,6403),
(7406,5402,6411),
(7407,5401,6403),
(7407,5402,6412),
(7408,5401,6403),
(7408,5402,6413),
(7411,5403,6421),
(7411,5404,6431),
(7412,5403,6421),
(7412,5404,6432),
(7413,5403,6422),
(7413,5404,6431),
(7414,5403,6422),
(7414,5404,6432)
ON DUPLICATE KEY UPDATE variant_id=variant_id;

INSERT INTO `nnop3_minimarket_product_images`
(id,product_id,path,alt,sort_order,visibility_mode)
VALUES
(12011,1201,'images/minimarket/demo-tee-blue-front.png','tee blue front',0,1),
(12012,1201,'images/minimarket/demo-tee-blue-detail.png','tee blue detail',1,1),
(12013,1201,'images/minimarket/demo-tee-white-front.png','tee white front',0,1),
(12014,1201,'images/minimarket/demo-tee-white-detail.png','tee white detail',1,1),
(12015,1201,'images/minimarket/demo-tee-black-front.png','tee black front',0,1),
(12016,1201,'images/minimarket/demo-tee-black-detail.png','tee black detail',1,1),
(12017,1202,'images/minimarket/demo-mug-white-front.png','mug white front',0,1),
(12018,1202,'images/minimarket/demo-mug-white-detail.png','mug white detail',1,1),
(12019,1202,'images/minimarket/demo-mug-red-front.png','mug red front',0,1),
(12020,1202,'images/minimarket/demo-mug-red-detail.png','mug red detail',1,1)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `nnop3_minimarket_product_image_variants`
(image_id,variant_id,is_cover) VALUES
(12011,7401,1),
(12011,7402,1),
(12012,7401,0),
(12012,7402,0),
(12013,7403,1),
(12013,7404,1),
(12013,7405,1),
(12014,7403,0),
(12014,7404,0),
(12014,7405,0),
(12015,7406,1),
(12015,7407,1),
(12015,7408,1),
(12016,7406,0),
(12016,7407,0),
(12016,7408,0),
(12017,7411,1),
(12017,7412,1),
(12018,7411,0),
(12018,7412,0),
(12019,7413,1),
(12019,7414,1),
(12020,7413,0),
(12020,7414,0)
ON DUPLICATE KEY UPDATE is_cover=is_cover;

COMMIT;
SELECT 'demo_products' section,COUNT(*) total FROM `nnop3_minimarket_products` WHERE id IN (1201,1202)
UNION ALL SELECT 'demo_variants',COUNT(*) FROM `nnop3_minimarket_product_variants` WHERE product_id IN (1201,1202)
UNION ALL SELECT 'demo_gallery_links',COUNT(*) FROM `nnop3_minimarket_product_image_variants` WHERE variant_id BETWEEN 7401 AND 7414;
