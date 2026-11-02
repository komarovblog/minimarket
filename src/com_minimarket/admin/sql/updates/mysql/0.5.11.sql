-- MiniMarket 0.5.11: list value cardinality independent of variant/SKU flag.
-- Existing is_variant=1 lists previously allowed multiple selections. Preserve
-- that behaviour and all product selection rows on upgrade.
ALTER TABLE `#__minimarket_attributes`
    ADD COLUMN `is_multiple` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_variant`;

UPDATE `#__minimarket_attributes`
SET `is_multiple` = 1
WHERE `input_type` = 'list' AND `is_variant` = 1;
