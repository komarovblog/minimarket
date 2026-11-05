-- MiniMarket: additional, non-destructive data for brand/collection DEMO ONLY.
-- Select your Joomla database in phpMyAdmin before running. Table prefix: wquew_.
-- Requires com_minimarket >= 0.5.49 and its database migration.
-- Safe to re-run: adds missing demo entities and assignments, leaves real data intact.
-- Only touches demo products where id=1001..1099 AND sku exactly TOY-<id>.
-- No DROP, DELETE, TRUNCATE, CREATE or ALTER. Back up before any SQL import.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `wquew_minimarket_brands` (title,alias,description,state)
 SELECT 'Лесные игрушки','demo-forest-toys','<p>Игрушки и мягкие зверята (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_brands` WHERE alias='demo-forest-toys');
INSERT INTO `wquew_minimarket_brands` (title,alias,description,state)
 SELECT 'ГеоЛаб','demo-geo-lab','<p>Конструкторы и механизмы (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_brands` WHERE alias='demo-geo-lab');
INSERT INTO `wquew_minimarket_brands` (title,alias,description,state)
 SELECT 'Игра вместе','demo-play-together','<p>Настольные и семейные игры (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_brands` WHERE alias='demo-play-together');
INSERT INTO `wquew_minimarket_brands` (title,alias,description,state)
 SELECT 'Первые открытия','demo-first-discoveries','<p>Игры для малышей (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_brands` WHERE alias='demo-first-discoveries');
INSERT INTO `wquew_minimarket_brands` (title,alias,description,state)
 SELECT 'Мастерская игры','demo-toy-workshop','<p>Творческие и прочие игровые наборы (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_brands` WHERE alias='demo-toy-workshop');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'Мягкие друзья','demo-soft-friends','<p>Плюшевые зверята (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-soft-friends');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'STEM и механика','demo-stem','<p>Интерактивные механизмы и конструкторы (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-stem');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'Для семейного вечера','demo-family-evening','<p>Настольные и семейные игры (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-family-evening');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'Маленьким исследователям','demo-little-explorers','<p>Игры для малышей (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-little-explorers');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'Творческая мастерская','demo-creativity','<p>Творческие игры (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-creativity');
INSERT INTO `wquew_minimarket_collections` (title,alias,description,state)
 SELECT 'Идеи подарков','demo-gifts','<p>Подборка подарков из разных категорий (демонстрация).</p>',1
 WHERE NOT EXISTS (SELECT 1 FROM `wquew_minimarket_collections` WHERE alias='demo-gifts');

-- The brand field is nullable, so existing intentional choices are not replaced.
UPDATE `wquew_minimarket_products` p
INNER JOIN `wquew_minimarket_brands` b ON b.alias = CASE
 WHEN p.category_id IN (101,102,103) THEN 'demo-forest-toys'
 WHEN p.category_id IN (104,105,106) THEN 'demo-geo-lab'
 WHEN p.category_id IN (107,108,109) THEN 'demo-play-together'
 WHEN p.category_id IN (110,111,112) THEN 'demo-first-discoveries'
 ELSE 'demo-toy-workshop' END
SET p.brand_id=b.id
WHERE p.id BETWEEN 1001 AND 1099
 AND p.sku=CONCAT('TOY-',p.id) AND p.brand_id IS NULL;

-- One main collection for each toy demo product, without removing existing links.
INSERT INTO `wquew_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `wquew_minimarket_products` p
JOIN `wquew_minimarket_collections` c ON c.alias = CASE
 WHEN p.category_id IN (101,102,103) THEN 'demo-soft-friends'
 WHEN p.category_id IN (104,105,106,125,126) THEN 'demo-stem'
 WHEN p.category_id IN (107,108,109) THEN 'demo-family-evening'
 WHEN p.category_id IN (110,111,112) THEN 'demo-little-explorers'
 ELSE 'demo-creativity' END
WHERE p.id BETWEEN 1001 AND 1099 AND p.sku=CONCAT('TOY-',p.id)
 AND NOT EXISTS (SELECT 1 FROM `wquew_minimarket_product_collections` pc
  WHERE pc.product_id=p.id AND pc.collection_id=c.id);

-- Some demo products deliberately belong to two collections.
INSERT INTO `wquew_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,1 FROM `wquew_minimarket_products` p
JOIN `wquew_minimarket_collections` c ON c.alias='demo-gifts'
WHERE p.id BETWEEN 1001 AND 1099 AND p.sku=CONCAT('TOY-',p.id)
 AND MOD(p.id,3)=0
 AND NOT EXISTS (SELECT 1 FROM `wquew_minimarket_product_collections` pc
  WHERE pc.product_id=p.id AND pc.collection_id=c.id);

COMMIT;
SELECT 'Demo brands' AS section, COUNT(*) AS total FROM `wquew_minimarket_brands` WHERE alias LIKE 'demo-%'
UNION ALL SELECT 'Demo collections', COUNT(*) FROM `wquew_minimarket_collections` WHERE alias LIKE 'demo-%'
UNION ALL SELECT 'Example products assigned a brand', COUNT(*) FROM `wquew_minimarket_products`
 WHERE id BETWEEN 1001 AND 1099 AND sku=CONCAT('TOY-',id) AND brand_id IS NOT NULL
UNION ALL SELECT 'Example collection assignments', COUNT(*) FROM `wquew_minimarket_product_collections` pc
 JOIN `wquew_minimarket_products` p ON p.id=pc.product_id
 WHERE p.id BETWEEN 1001 AND 1099 AND p.sku=CONCAT('TOY-',p.id);
