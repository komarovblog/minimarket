-- MiniMarket 0.5.55: physical/digital product delivery classification.
-- 0.5.54 -> 0.5.55: additive only; all existing items remain physical by default.
ALTER TABLE `#__minimarket_products`
    ADD COLUMN `delivery_type` VARCHAR(12) NOT NULL DEFAULT 'physical' AFTER `product_type`;
