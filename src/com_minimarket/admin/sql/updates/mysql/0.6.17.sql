-- MiniMarket 0.6.17: real pickup points; no backfill, order address unchanged.

CREATE TABLE IF NOT EXISTS #__minimarket_pickup_points (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(150) NOT NULL,
 address VARCHAR(512) NOT NULL,
 schedule VARCHAR(1000) NOT NULL DEFAULT '',
 map_url VARCHAR(1024) NOT NULL DEFAULT '',
 fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
 state TINYINT NOT NULL DEFAULT 1,
 ordering INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY idx_mm_pickup_state (state,ordering,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE #__minimarket_orders ADD COLUMN pickup_point_id INT UNSIGNED NULL DEFAULT NULL AFTER delivery_method;
