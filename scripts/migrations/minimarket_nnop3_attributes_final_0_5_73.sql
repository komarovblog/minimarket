-- MiniMarket 0.5.73: FINAL canonical attribute storage, MySQL 8.0/8.4.
-- Joomla replaces nnop3_ with the configured prefix. Never run this file manually
-- AND through the installer. Take a full database backup before upgrade.
-- The transaction only covers data movement; verified DDL follows COMMIT.
-- Requires the 0.5.72 table layout. Fails CLOSED for unsupported values.
CREATE TEMPORARY TABLE mm73_attribute_guard (
    invalid_count INT NOT NULL CHECK (invalid_count = 0)
) ENGINE=InnoDB;
START TRANSACTION;

-- Do not convert undocumented old data types, sources or numeric presets.
INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_attributes
WHERE input_type NOT IN ('text','number','boolean','editor','document','color','list')
   OR value_source NOT IN ('legacy','manual','preset')
   OR (input_type='number' AND TRIM(COALESCE(number_options,'')) <> '')
   OR (input_type <> 'text' AND TRIM(COALESCE(text_options,'')) <> '');

INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_attributes
WHERE value_source='legacy' AND input_type='text' AND input_mode='presets'
  AND (COALESCE(JSON_VALID(text_options),0)=0
    OR CASE WHEN JSON_VALID(text_options) THEN JSON_TYPE(text_options)
       ELSE 'INVALID' END <> 'ARRAY');

-- Legacy text dictionary presets become stable ID-based value records.
-- Existing ID values (e.g., colors) are preserved.
INSERT INTO nnop3_minimarket_attribute_values
    (attribute_id,title,code,value_payload,sort_order,state)
SELECT a.id, j.label, CONCAT('mm-preset-',LPAD(j.ordinal,5,'0')),
       j.label, j.ordinal, 1
FROM nnop3_minimarket_attributes a
JOIN JSON_TABLE(a.text_options, '$[*]'
    COLUMNS (ordinal FOR ORDINALITY, label VARCHAR(255) PATH '$')) j
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets' AND j.label IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM nnop3_minimarket_attribute_values v
      WHERE v.attribute_id=a.id AND v.title=j.label COLLATE utf8mb4_unicode_ci
  );

-- A duplicate/missing declaration must stop BEFORE deleting the old rows.
INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_attributes a
JOIN JSON_TABLE(a.text_options, '$[*]'
    COLUMNS (ordinal FOR ORDINALITY, label VARCHAR(255) PATH '$')) j
LEFT JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=a.id AND v.title=j.label COLLATE utf8mb4_unicode_ci
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets' AND (j.label IS NULL OR v.id IS NULL);

-- Scalar text choices are converted to stable ID links.
INSERT INTO nnop3_minimarket_product_attribute_options
    (product_id,attribute_id,value_id)
SELECT s.product_id,s.attribute_id,v.id
FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_attributes a ON a.id=s.attribute_id
JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=s.attribute_id AND v.title=s.value_text
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets' AND s.value_text IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM nnop3_minimarket_product_attribute_options o
      WHERE o.product_id=s.product_id AND o.attribute_id=s.attribute_id
        AND o.value_id=v.id);

-- Multi-select text choices use the SAME ID relation.
INSERT INTO nnop3_minimarket_product_attribute_options
    (product_id,attribute_id,value_id)
SELECT m.product_id,m.attribute_id,v.id
FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=m.attribute_id AND v.title=m.value_text
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets'
  AND NOT EXISTS (SELECT 1 FROM nnop3_minimarket_product_attribute_options o
      WHERE o.product_id=m.product_id AND o.attribute_id=m.attribute_id
        AND o.value_id=v.id);

INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_attributes a ON a.id=s.attribute_id
LEFT JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=s.attribute_id AND v.title=s.value_text
LEFT JOIN nnop3_minimarket_product_attribute_options o
  ON o.product_id=s.product_id AND o.attribute_id=s.attribute_id AND o.value_id=v.id
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets' AND s.value_text IS NOT NULL AND o.value_id IS NULL;

INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
LEFT JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=m.attribute_id AND v.title=m.value_text
LEFT JOIN nnop3_minimarket_product_attribute_options o
  ON o.product_id=m.product_id AND o.attribute_id=m.attribute_id AND o.value_id=v.id
WHERE o.value_id IS NULL OR a.value_source<>'legacy'
   OR a.input_type<>'text' OR a.input_mode<>'presets';

-- Fail for old unsupported scalar/option types before changing the schema.
INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_attributes a
JOIN nnop3_minimarket_product_attribute_scalars s ON s.attribute_id=a.id
WHERE a.input_type='list'
   OR (a.value_source='legacy' AND a.input_type='text'
       AND a.input_mode='presets' AND s.value_text IS NULL);

-- Every old value is now represented in the canonical ID table.
DELETE s FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_attributes a ON a.id=s.attribute_id
JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=s.attribute_id AND v.title=s.value_text
JOIN nnop3_minimarket_product_attribute_options o
  ON o.product_id=s.product_id AND o.attribute_id=s.attribute_id AND o.value_id=v.id
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets';

DELETE m FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
JOIN nnop3_minimarket_attribute_values v
  ON v.attribute_id=m.attribute_id AND v.title=m.value_text
JOIN nnop3_minimarket_product_attribute_options o
  ON o.product_id=m.product_id AND o.attribute_id=m.attribute_id AND o.value_id=v.id
WHERE a.value_source='legacy' AND a.input_type='text'
  AND a.input_mode='presets';

UPDATE nnop3_minimarket_attributes
SET value_source = CASE
    WHEN input_type IN ('list','color') OR
         (input_type='text' AND input_mode='presets') THEN 'preset'
    ELSE 'manual' END,
    input_type=CASE WHEN input_type='list' THEN 'text' ELSE input_type END
WHERE value_source='legacy';

-- Hard fail unless ALL data have been canonicalized.
INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_attributes
WHERE value_source NOT IN ('manual','preset') OR input_type NOT IN
  ('text','number','editor','boolean','document','color');

INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_product_attribute_multi_values;

-- Scalar/dictionary rows cannot coexist for the same attribute+product.
INSERT INTO mm73_attribute_guard (invalid_count)
SELECT COUNT(*) FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_product_attribute_options o
  ON o.product_id=s.product_id AND o.attribute_id=s.attribute_id;

COMMIT;
DROP TEMPORARY TABLE mm73_attribute_guard;

-- MySQL DDL implicitly commits. No legacy columns/table survive this point.
ALTER TABLE nnop3_minimarket_attributes
  MODIFY COLUMN input_type VARCHAR(16) NOT NULL DEFAULT 'text',
  MODIFY COLUMN value_source VARCHAR(12) NOT NULL DEFAULT 'manual',
  DROP COLUMN input_mode,
  DROP COLUMN text_options,
  DROP COLUMN number_options;

DROP TABLE nnop3_minimarket_product_attribute_multi_values;
