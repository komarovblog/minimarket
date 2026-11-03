-- 0.5.32: optional overlapping groups of independent products.
-- Preserve all existing groups and product membership rows.
-- Keep a leading product_id index for the existing product foreign key.
ALTER TABLE `#__minimarket_product_group_members`
  ADD KEY `idx_mm_group_member_product` (`product_id`);

ALTER TABLE `#__minimarket_product_group_members`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`group_id`, `product_id`);

ALTER TABLE `#__minimarket_product_groups`
  ADD COLUMN `type` VARCHAR(32) NOT NULL DEFAULT 'similar',
  ADD COLUMN `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `#__minimarket_import_group_keys` (
  `source_key` VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `external_group_key` VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_key`, `external_group_key`),
  KEY `idx_mm_import_group` (`group_id`),
  CONSTRAINT `fk_mm_import_group` FOREIGN KEY (`group_id`)
    REFERENCES `#__minimarket_product_groups`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
