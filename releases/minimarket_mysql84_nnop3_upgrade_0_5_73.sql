-- Final one-time MiniMarket 0.5.73 database upgrade for MySQL 8.4 / Joomla nnop3_.
-- First make a complete database backup and test this script on a database COPY.
-- Import this SQL only BEFORE installing 0.5.73 ZIP. NEVER import again after
-- Joomla has automatically applied 0.5.73 via Extensions -> Install.
-- MySQL DDL is not transactional; failure after COMMIT may require backup restore.
-- Only #__minimarket_* tables are migrated; Joomla #__schemas is version-marked.
-- BEGIN GRID PRELUDE
-- If the installation is still at 0.5.69, apply the 0.5.71 Bootstrap grid
-- before marking the schema at 0.5.73. Already migrated installs are skipped.
SET @mm73_old_grid = (
 SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='nnop3_minimarket_categories'
 AND COLUMN_NAME IN ('cards_mobile','cards_tablet','cards_desktop','cards_wide')
);
SET @mm73_new_grid = (
 SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='nnop3_minimarket_categories'
 AND COLUMN_NAME IN ('cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl')
);
CREATE TEMPORARY TABLE mm73_grid_guard (invalid_count INT NOT NULL CHECK (invalid_count=0)) ENGINE=InnoDB;
INSERT INTO mm73_grid_guard(invalid_count)
VALUES (IF((@mm73_old_grid=4 AND @mm73_new_grid=0) OR
           (@mm73_old_grid=0 AND @mm73_new_grid=6),0,1));
DROP TEMPORARY TABLE mm73_grid_guard;
SET @mm73_grid_sql = IF(@mm73_old_grid=4,
 'ALTER TABLE `nnop3_minimarket_categories` RENAME COLUMN `cards_mobile` TO `cards_xs`, RENAME COLUMN `cards_tablet` TO `cards_md`, RENAME COLUMN `cards_desktop` TO `cards_lg`, RENAME COLUMN `cards_wide` TO `cards_xl`, ADD COLUMN `cards_sm` TINYINT NOT NULL DEFAULT -1 AFTER `cards_xs`, ADD COLUMN `cards_xxl` TINYINT NOT NULL DEFAULT -1 AFTER `cards_xl`',
 'SELECT 1');
PREPARE mm73_grid_stmt FROM @mm73_grid_sql;
EXECUTE mm73_grid_stmt;
DEALLOCATE PREPARE mm73_grid_stmt;
-- END GRID PRELUDE

-- BEGIN SHARED MIGRATION
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
-- END SHARED MIGRATION

-- Skip the equivalent Joomla updater SQL when 0.5.73 component is later installed.
-- This is for MANUAL SQL workflow only, not needed for regular Joomla installation.
UPDATE nnop3_schemas SET version_id='0.5.73'
WHERE extension_id IN
 (SELECT extension_id FROM nnop3_extensions
  WHERE type='component' AND element='com_minimarket');

-- Confirm: each should be 0.
SELECT COUNT(*) AS invalid_attribute_sources
FROM nnop3_minimarket_attributes
WHERE value_source NOT IN ('manual','preset')
   OR input_type NOT IN ('text','number','editor','boolean','document','color');
SELECT COUNT(*) AS dangling_dictionary_links
FROM nnop3_minimarket_product_attribute_options o
LEFT JOIN nnop3_minimarket_attribute_values v
 ON v.id=o.value_id AND v.attribute_id=o.attribute_id
WHERE v.id IS NULL;
