-- MiniMarket 0.5.70 canonical characteristic migration for MySQL 8.0/8.4.
-- Database: minimarket. Joomla prefix: nnop3_. Import ONCE before installing 0.5.70.
-- IMPORTANT: Take a complete database backup. DDL on MySQL autocommits.
-- Migrates old text/numeric presets and old multiple selections without losing
-- product mappings. Old columns/table are removed only after row-level checks.
USE minimarket;
SET NAMES utf8mb4;
START TRANSACTION;
CREATE TEMPORARY TABLE _mm70_values (
 attribute_id INT UNSIGNED NOT NULL,
 value_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 raw_value MEDIUMTEXT NOT NULL,
 sample_image VARCHAR(1024) NOT NULL DEFAULT '',
 PRIMARY KEY(attribute_id,value_hash)
) ENGINE=InnoDB;

-- Convert configured text choices, including object-style image metadata.
INSERT IGNORE INTO _mm70_values(attribute_id,value_hash,raw_value,sample_image)
SELECT parsed.attr_id, SHA2(CONCAT(parsed.attr_id, ':', parsed.raw_value),256),
       parsed.raw_value, parsed.image_path
FROM (
 SELECT a.id AS attr_id,
   CASE WHEN JSON_TYPE(j.item)='OBJECT'
        THEN JSON_UNQUOTE(JSON_EXTRACT(j.item,'$.value'))
        ELSE JSON_UNQUOTE(j.item) END AS raw_value,
   CASE WHEN JSON_TYPE(j.item)='OBJECT'
        THEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.item,'$.image')),'')
        ELSE '' END AS image_path
 FROM nnop3_minimarket_attributes a
 JOIN JSON_TABLE(
   CASE WHEN JSON_VALID(a.text_options) THEN a.text_options ELSE '[]' END,
   '$[*]' COLUMNS(item JSON PATH '$')
 ) j
 WHERE a.input_type='text' AND a.value_source='legacy'
   AND a.input_mode='presets'
) parsed
WHERE parsed.raw_value IS NOT NULL AND parsed.raw_value <> '';

-- Convert configured numeric choices to stable dictionary entries.
INSERT IGNORE INTO _mm70_values(attribute_id,value_hash,raw_value,sample_image)
SELECT a.id, SHA2(CONCAT(a.id,':',TRIM(j.raw_value)),256),
       TRIM(j.raw_value), ''
FROM nnop3_minimarket_attributes a
JOIN JSON_TABLE(
 CONCAT('["',REPLACE(TRIM(a.number_options),',','","'),'"]'),
 '$[*]' COLUMNS(raw_value VARCHAR(255) PATH '$')
) j
WHERE a.input_type='number' AND a.value_source='legacy'
  AND a.input_mode='presets' AND TRIM(a.number_options) <> ''
  AND j.raw_value IS NOT NULL AND TRIM(j.raw_value) <> '';

-- Preserve all actual scalar selections of attributes using old presets.
INSERT IGNORE INTO _mm70_values(attribute_id,value_hash,raw_value,sample_image)
SELECT a.id,SHA2(CONCAT(a.id,':',casted.txt),256),casted.txt,''
FROM nnop3_minimarket_attributes a
JOIN (
 SELECT product_id,attribute_id,
   COALESCE(value_text,CAST(value_number AS CHAR),CAST(value_boolean AS CHAR)) AS txt
 FROM nnop3_minimarket_product_attribute_scalars
) casted ON casted.attribute_id=a.id
WHERE casted.txt IS NOT NULL AND casted.txt <> ''
  AND (
    a.value_source='preset'
    OR (a.value_source='legacy' AND (
      a.input_type='list'
      OR (a.input_type IN ('text','number') AND a.input_mode='presets')
      OR (a.input_type='color' AND EXISTS(
         SELECT 1 FROM nnop3_minimarket_attribute_values x
         WHERE x.attribute_id=a.id
      ))
    ))
  );

-- Preserve old text multi-selects as stable dictionary selections
-- for preset attributes; manual entries are transferred separately.
INSERT IGNORE INTO _mm70_values(attribute_id,value_hash,raw_value,sample_image)
SELECT a.id,SHA2(CONCAT(a.id,':',m.value_text),256),m.value_text,''
FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
WHERE m.value_text <> ''
 AND (a.value_source='preset'
      OR (a.value_source='legacy'
          AND (a.input_type='list' OR a.input_mode='presets')));

-- Convert candidates to stable IDs. value_payload retains full original value.
INSERT INTO nnop3_minimarket_attribute_values
 (attribute_id,title,code,color_hex,sample_image,value_payload,sort_order,state)
SELECT v.attribute_id,LEFT(v.raw_value,255),
       CONCAT('mm70-',LEFT(v.value_hash,48)),
       CASE WHEN v.raw_value REGEXP '^#[A-Fa-f0-9]{6}$'
            THEN UPPER(v.raw_value) ELSE '' END,
       v.sample_image,v.raw_value,0,1
FROM _mm70_values v
LEFT JOIN nnop3_minimarket_attribute_values existing
  ON existing.attribute_id=v.attribute_id
 AND existing.code=CONCAT('mm70-',LEFT(v.value_hash,48))
WHERE existing.id IS NULL;

-- Remap typed scalar selections of preset attributes to option IDs.
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
 (product_id,attribute_id,value_id)
SELECT s.product_id,s.attribute_id,v.id
FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_attributes a ON a.id=s.attribute_id
JOIN nnop3_minimarket_attribute_values v
 ON v.attribute_id=a.id
 AND v.code=CONCAT('mm70-',LEFT(SHA2(CONCAT(a.id,':',
     COALESCE(s.value_text,CAST(s.value_number AS CHAR),
              CAST(s.value_boolean AS CHAR))),256),48))
WHERE (
    a.value_source='preset'
    OR (a.value_source='legacy' AND (
      a.input_type='list'
      OR (a.input_type IN ('text','number') AND a.input_mode='presets')
      OR (a.input_type='color' AND EXISTS(
         SELECT 1 FROM nnop3_minimarket_attribute_values x WHERE x.attribute_id=a.id
      ))
    ))
  );

-- Map old multiple preset selections to their dictionary values.
INSERT IGNORE INTO nnop3_minimarket_product_attribute_options
 (product_id,attribute_id,value_id)
SELECT m.product_id,m.attribute_id,v.id
FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
JOIN nnop3_minimarket_attribute_values v
 ON v.attribute_id=m.attribute_id
 AND v.code=CONCAT('mm70-',LEFT(SHA2(CONCAT(a.id,':',m.value_text),256),48))
WHERE a.value_source='preset'
   OR (a.value_source='legacy'
       AND (a.input_type='list' OR a.input_mode='presets'));

-- Legacy non-preset multi-selections become ordered free manual values.
INSERT INTO nnop3_minimarket_product_attribute_free_values
 (product_id,attribute_id,value_text,sort_order)
SELECT m.product_id,m.attribute_id,m.value_text,0
FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
WHERE NOT (
    a.value_source='preset'
    OR (a.value_source='legacy'
        AND (a.input_type='list' OR a.input_mode='presets'))
  )
  AND NOT EXISTS (
    SELECT 1 FROM nnop3_minimarket_product_attribute_free_values f
    WHERE f.product_id=m.product_id AND f.attribute_id=m.attribute_id
      AND f.value_text=m.value_text
  );

-- Guard all multi selections before DDL. Errors stop import, not silently drop.
CREATE TEMPORARY TABLE _mm70_guard (ok TINYINT NOT NULL CHECK(ok=1));
INSERT INTO _mm70_guard(ok)
SELECT IF(COUNT(*)=0,1,0)
FROM nnop3_minimarket_product_attribute_multi_values m
JOIN nnop3_minimarket_attributes a ON a.id=m.attribute_id
WHERE NOT (
  (
   (a.value_source='preset' OR
      (a.value_source='legacy' AND
       (a.input_type='list' OR a.input_mode='presets')))
   AND EXISTS (
     SELECT 1 FROM nnop3_minimarket_product_attribute_options o
     JOIN nnop3_minimarket_attribute_values v ON v.id=o.value_id
     WHERE o.product_id=m.product_id AND o.attribute_id=m.attribute_id
       AND v.code=CONCAT('mm70-',LEFT(SHA2(CONCAT(a.id,':',m.value_text),256),48))
   )
  )
  OR
  (
   NOT (a.value_source='preset' OR
      (a.value_source='legacy' AND
       (a.input_type='list' OR a.input_mode='presets')))
   AND EXISTS (
     SELECT 1 FROM nnop3_minimarket_product_attribute_free_values f
     WHERE f.product_id=m.product_id AND f.attribute_id=m.attribute_id
       AND f.value_text=m.value_text
   )
  )
);

-- Delete old preset scalars only after their selected IDs exist.
DELETE s FROM nnop3_minimarket_product_attribute_scalars s
JOIN nnop3_minimarket_attributes a ON a.id=s.attribute_id
WHERE (
    a.value_source='preset'
    OR (a.value_source='legacy' AND (
      a.input_type='list'
      OR (a.input_type IN ('text','number') AND a.input_mode='presets')
      OR (a.input_type='color' AND EXISTS(
        SELECT 1 FROM nnop3_minimarket_attribute_values x WHERE x.attribute_id=a.id
      ))
    ))
  )
  AND EXISTS (
    SELECT 1 FROM nnop3_minimarket_product_attribute_options o
    JOIN nnop3_minimarket_attribute_values v ON v.id=o.value_id
    WHERE o.product_id=s.product_id AND o.attribute_id=s.attribute_id
     AND v.code=CONCAT('mm70-',LEFT(SHA2(CONCAT(a.id,':',
       COALESCE(s.value_text,CAST(s.value_number AS CHAR),
                CAST(s.value_boolean AS CHAR))),256),48))
  );

-- Existing dictionary IDs, SKU choices and category bindings are unchanged.
UPDATE nnop3_minimarket_attributes a SET
 a.value_source=CASE
   WHEN a.value_source IN ('manual','preset') THEN a.value_source
   WHEN a.input_type='list' THEN 'preset'
   WHEN a.input_type='color' AND EXISTS(
       SELECT 1 FROM nnop3_minimarket_attribute_values v
       WHERE v.attribute_id=a.id) THEN 'preset'
   WHEN a.input_type IN ('text','number') AND a.input_mode='presets'
       THEN 'preset'
   ELSE 'manual'
 END,
 a.input_type=CASE WHEN a.input_type IN ('list','textarea','date','url')
     THEN 'text' ELSE a.input_type END,
 a.filter_mode=CASE WHEN a.input_type IN ('textarea','date','url')
    THEN 'none' ELSE a.filter_mode END,
 a.is_filterable=CASE WHEN a.input_type IN ('textarea','date','url')
    THEN 0 ELSE a.is_filterable END;

-- Enforce multi-select if actual saved product data needs it.
UPDATE nnop3_minimarket_attributes a
SET a.is_multiple=1
WHERE EXISTS (
  SELECT 1 FROM nnop3_minimarket_product_attribute_options o
  WHERE o.attribute_id=a.id
  GROUP BY o.product_id HAVING COUNT(*)>1
) OR EXISTS (
  SELECT 1 FROM nnop3_minimarket_product_attribute_free_values f
  WHERE f.attribute_id=a.id
  GROUP BY f.product_id HAVING COUNT(*)>1
);

-- Ensure the old type/source state is completely gone before dropping columns.
INSERT INTO _mm70_guard(ok)
SELECT IF(COUNT(*)=0,1,0) FROM nnop3_minimarket_attributes
WHERE value_source NOT IN ('manual','preset')
   OR input_type NOT IN ('text','number','boolean','editor','document','color');

COMMIT;
-- Destructive DDL only after all old values have been copied and checked.
DROP TABLE nnop3_minimarket_product_attribute_multi_values;
ALTER TABLE nnop3_minimarket_attributes
 DROP COLUMN input_mode,
 DROP COLUMN number_options,
 DROP COLUMN text_options,
 MODIFY COLUMN input_type VARCHAR(16) NOT NULL DEFAULT 'text',
 MODIFY COLUMN value_source VARCHAR(12) NOT NULL DEFAULT 'manual';
DROP TEMPORARY TABLE IF EXISTS _mm70_values;
DROP TEMPORARY TABLE IF EXISTS _mm70_guard;
-- Verify:
-- SELECT COUNT(*) FROM nnop3_minimarket_attributes WHERE value_source='legacy';
-- SHOW COLUMNS FROM nnop3_minimarket_attributes;
