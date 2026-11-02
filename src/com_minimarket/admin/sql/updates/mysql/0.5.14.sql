-- 0.5.14: four data types for NEW attributes. Preserve legacy list IDs.
-- Existing products, selections, price, stock and media remain untouched.
ALTER TABLE `#__minimarket_attributes`
    ADD COLUMN `text_options` TEXT NULL AFTER `number_options`;
