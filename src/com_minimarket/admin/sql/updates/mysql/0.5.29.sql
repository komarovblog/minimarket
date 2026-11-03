-- 0.5.29: descriptive attributes are completely separate from order options.
-- Remove only the obsolete classification column; no products, SKU rows,
-- SKU identifiers, price or stock values are changed.
ALTER TABLE `#__minimarket_attributes`
  DROP COLUMN `is_variant`;
