-- MiniMarket 0.5.55: OPTIONAL example digital product for MySQL 8.4.
-- Database minimarket, table prefix nnop3_. Install the 0.5.55 Joomla ZIP first.
-- Only one UNPUBLISHED sample product; the digital checkout / delivery flow
-- is NOT implemented yet, so it must not become available for purchase.
-- Safe to repeat; no existing product, SKU, variants, stock or images changed.
USE `minimarket`;
START TRANSACTION;
INSERT INTO `nnop3_minimarket_products`
  (id,category_id,product_type,delivery_type,title,alias,sku,price,stock,
   state,introtext,description)
SELECT 1200,c.id,'simple','digital',
 'Электронное руководство по STEM-конструктору',
 'elektronnoe-rukovodstvo-stem-1200','DIGITAL-STEM-1200',490.00,0,0,
 '<p>Пример цифрового товара, не опубликован до появления электронной доставки.</p>',
 '<p>Демонстрационная карточка цифрового PDF-руководства. Покупку и выдачу файла подключим на этапе корзины и заказов.</p>'
FROM `nnop3_minimarket_categories` c
WHERE c.id=104 AND c.alias='konstruktory'
  AND NOT EXISTS (
    SELECT 1 FROM `nnop3_minimarket_products` p
    WHERE p.id=1200 OR p.sku='DIGITAL-STEM-1200'
       OR p.alias='elektronnoe-rukovodstvo-stem-1200'
  );
COMMIT;
SELECT id,title,sku,product_type,delivery_type,price,state
FROM `nnop3_minimarket_products` WHERE sku='DIGITAL-STEM-1200';
