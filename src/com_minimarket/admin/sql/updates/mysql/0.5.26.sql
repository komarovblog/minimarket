-- 0.5.26: order options are distinct from informational attributes.
-- option_stock_mode: 0 = shared product stock, 1 = physical stock for each SKU.
-- Existing products with SKU combinations retain their previous per-SKU behavior.
ALTER TABLE `#__minimarket_products`
  ADD COLUMN `option_stock_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE `#__minimarket_products` AS p
  SET p.`option_stock_mode` = 1
  WHERE EXISTS (
    SELECT 1 FROM `#__minimarket_product_variants` AS v
    WHERE v.`product_id` = p.`id`
  );

-- Independent products (different URLs, images, SEO) can belong to a family.
-- Product groups themselves have no public URL, stock or sales identity.
CREATE TABLE IF NOT EXISTS `#__minimarket_product_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_mm_product_group_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__minimarket_product_group_members` (
  `product_id` INT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`),
  KEY `idx_mm_group_member_order` (`group_id`,`sort_order`,`product_id`),
  CONSTRAINT `fk_mm_group_member_product` FOREIGN KEY (`product_id`)
    REFERENCES `#__minimarket_products`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_mm_group_member_group` FOREIGN KEY (`group_id`)
    REFERENCES `#__minimarket_product_groups`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
