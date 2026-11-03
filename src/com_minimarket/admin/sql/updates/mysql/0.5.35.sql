-- 0.5.35: stable external keys across imported catalog entities.
-- No change to products, SKU tables, old import keys or media data.
CREATE TABLE IF NOT EXISTS `#__minimarket_import_entity_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `entity_kind` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL,
  `external_key` VARCHAR(128) COLLATE utf8mb4_bin NOT NULL,
  `local_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`,`entity_kind`,`external_key`),
  KEY `idx_mm_import_entity_local` (`entity_kind`,`local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
