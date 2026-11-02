-- MiniMarket 0.5.12: independent data type, product input and filter presentation.
ALTER TABLE `#__minimarket_attributes`
  ADD COLUMN `input_mode` VARCHAR(24) NOT NULL DEFAULT 'auto' AFTER `input_type`,
  ADD COLUMN `number_options` VARCHAR(1024) NOT NULL DEFAULT '' AFTER `input_mode`,
  ADD COLUMN `number_unit` VARCHAR(32) NOT NULL DEFAULT '' AFTER `number_options`,
  ADD COLUMN `number_min` DECIMAL(18,4) NULL AFTER `number_unit`,
  ADD COLUMN `number_max` DECIMAL(18,4) NULL AFTER `number_min`,
  ADD COLUMN `number_step` DECIMAL(18,4) NULL AFTER `number_max`,
  ADD COLUMN `filter_mode` VARCHAR(24) NOT NULL DEFAULT 'none' AFTER `is_filterable`,
  ADD COLUMN `filter_multiple` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `filter_mode`;

-- Migrate settings only; keep value IDs, products and commercial data untouched.
UPDATE `#__minimarket_attributes`
SET `filter_mode` = CASE
    WHEN `input_type` = 'number' THEN 'range'
    WHEN `input_type` = 'boolean' THEN 'boolean'
    WHEN `input_type` = 'text' THEN 'text'
    WHEN `input_type` = 'list' THEN 'checkboxes'
    ELSE 'none' END,
    `filter_multiple` = CASE WHEN `input_type` = 'list' THEN 1 ELSE 0 END
WHERE `is_filterable` = 1;

ALTER TABLE `#__minimarket_product_attribute_scalars`
  MODIFY COLUMN `value_text` TEXT NULL;
