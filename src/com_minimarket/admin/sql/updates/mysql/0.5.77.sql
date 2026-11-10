-- MiniMarket 0.5.77: one attribute value per product. Multiple values in
-- public filtering remain independently controlled by filter_multiple.
-- Preserve every extra preset choice and every old manually entered value
-- before reducing active attribute rows. BACK UP THE DB FIRST: MySQL DDL
-- implicitly commits and the installer cannot roll this back atomically.
CREATE TABLE IF NOT EXISTS #__minimarket_attribute_multivalue_archive (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    attribute_id INT UNSIGNED NOT NULL,
    source_kind VARCHAR(16) NOT NULL,
    value_id INT UNSIGNED NULL,
    value_text MEDIUMTEXT NULL,
    previous_sort_order INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_mm_attr_archive_product (product_id, attribute_id),
    KEY idx_mm_attr_archive_kind (source_kind, attribute_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep the lowest preset value ID; archive every higher one exactly once.
INSERT INTO #__minimarket_attribute_multivalue_archive
    (product_id, attribute_id, source_kind, value_id, value_text, previous_sort_order)
SELECT o.product_id, o.attribute_id, 'preset_extra', o.value_id, NULL, NULL
FROM #__minimarket_product_attribute_options AS o
INNER JOIN #__minimarket_product_attribute_options AS first
  ON first.product_id = o.product_id
 AND first.attribute_id = o.attribute_id
 AND first.value_id < o.value_id
GROUP BY o.product_id, o.attribute_id, o.value_id;

-- Archive ALL old multi-entry free values, including the first kept value.
INSERT INTO #__minimarket_attribute_multivalue_archive
    (product_id, attribute_id, source_kind, value_id, value_text, previous_sort_order)
SELECT f.product_id, f.attribute_id, 'manual_legacy', NULL,
       f.value_text, f.sort_order
FROM #__minimarket_product_attribute_free_values AS f;

-- The earliest free value becomes the single canonical scalar. If there is
-- already a scalar, its stored value takes precedence; both remain archived.
INSERT IGNORE INTO #__minimarket_product_attribute_scalars
    (product_id, attribute_id, value_text, value_number, value_boolean)
SELECT f.product_id, f.attribute_id,
       CASE WHEN a.input_type IN ('text','editor','document','color')
            THEN f.value_text ELSE NULL END,
       CASE WHEN a.input_type = 'number'
            THEN CAST(f.value_text AS DECIMAL(18,4)) ELSE NULL END,
       CASE WHEN a.input_type = 'boolean'
            THEN IF(f.value_text = '1',1,0) ELSE NULL END
FROM #__minimarket_product_attribute_free_values AS f
INNER JOIN #__minimarket_attributes AS a ON a.id = f.attribute_id
WHERE a.value_source = 'manual'
  AND NOT EXISTS (
    SELECT 1 FROM #__minimarket_product_attribute_free_values AS prior
    WHERE prior.product_id = f.product_id
      AND prior.attribute_id = f.attribute_id
      AND (prior.sort_order < f.sort_order
        OR (prior.sort_order = f.sort_order AND prior.id < f.id))
  );

-- Active values become single-valued, after archiving original rows.
DELETE extra
FROM #__minimarket_product_attribute_options AS extra
INNER JOIN #__minimarket_product_attribute_options AS first
  ON first.product_id = extra.product_id
 AND first.attribute_id = extra.attribute_id
 AND first.value_id < extra.value_id;
DELETE FROM #__minimarket_product_attribute_free_values;
UPDATE #__minimarket_attributes SET is_multiple = 0 WHERE is_multiple <> 0;

-- Enforce single value in future admin and import operations at DB level.
ALTER TABLE #__minimarket_product_attribute_options
    ADD UNIQUE KEY idx_mm_pa_one_value (product_id, attribute_id);
ALTER TABLE #__minimarket_product_attribute_free_values
    ADD UNIQUE KEY idx_mm_free_one_value (product_id, attribute_id);
