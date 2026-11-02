-- 0.5.16: required attributes per category. Existing relations remain optional.
ALTER TABLE `#__minimarket_category_attributes`
    ADD COLUMN `required` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `enabled`;
