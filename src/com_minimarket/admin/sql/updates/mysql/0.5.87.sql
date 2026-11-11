-- MiniMarket 0.5.87: shipping zones, immutable promo redemption, SEO landing media.
-- Additive migration; no historical orders, prices or catalog records are deleted.
ALTER TABLE #__minimarket_products
  ADD COLUMN shipping_weight_grams INT UNSIGNED NOT NULL DEFAULT 0 AFTER option_stock_mode,
  ADD COLUMN shipping_volume_cm3 INT UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_weight_grams;

CREATE TABLE IF NOT EXISTS #__minimarket_delivery_zones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(160) NOT NULL,
  country_code CHAR(2) NOT NULL DEFAULT 'RU',
  region VARCHAR(120) NOT NULL DEFAULT '',
  cities TEXT NOT NULL,
  postcode_prefixes TEXT NOT NULL,
  rate_mode VARCHAR(16) NOT NULL DEFAULT 'fixed',
  base_fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  rate_per_unit_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  free_from_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  min_cart_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  max_weight_grams BIGINT UNSIGNED NOT NULL DEFAULT 0,
  max_volume_cm3 BIGINT UNSIGNED NOT NULL DEFAULT 0,
  estimated_days_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  estimated_days_max SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  priority SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  state TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_mm_zone_active_priority (state,priority,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE #__minimarket_orders
  ADD COLUMN delivery_country CHAR(2) NOT NULL DEFAULT 'RU' AFTER delivery_city,
  ADD COLUMN delivery_region VARCHAR(120) NOT NULL DEFAULT '' AFTER delivery_country,
  ADD COLUMN delivery_postcode VARCHAR(16) NOT NULL DEFAULT '' AFTER delivery_region,
  ADD COLUMN delivery_zone_id INT UNSIGNED NULL AFTER delivery_rate_source,
  ADD COLUMN promo_code_id INT UNSIGNED NULL AFTER items_total_cents,
  ADD COLUMN promo_code VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' AFTER promo_code_id,
  ADD COLUMN discount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER promo_code;

CREATE TABLE IF NOT EXISTS #__minimarket_promo_redemptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  promo_code_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  customer_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  discount_cents BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_mm_redemption_order (order_id),
  KEY idx_mm_redemption_customer (promo_code_id,customer_key),
  CONSTRAINT fk_mm_promo_redemption_order FOREIGN KEY (order_id)
    REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mm_promo_redemption_code FOREIGN KEY (promo_code_id)
    REFERENCES #__minimarket_promo_codes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE #__minimarket_seo_filters
  ADD COLUMN image VARCHAR(1024) NOT NULL DEFAULT '' AFTER description,
  ADD COLUMN image_alt VARCHAR(255) NOT NULL DEFAULT '' AFTER image,
  ADD COLUMN og_title VARCHAR(255) NOT NULL DEFAULT '' AFTER meta_description,
  ADD COLUMN og_description VARCHAR(320) NOT NULL DEFAULT '' AFTER og_title,
  ADD COLUMN og_image VARCHAR(1024) NOT NULL DEFAULT '' AFTER og_description;
