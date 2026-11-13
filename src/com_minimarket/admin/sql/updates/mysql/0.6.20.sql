-- MiniMarket 0.6.20: automatic discount rules (no stock changes).
-- Automatic customer-facing offers; separate from one-time coupon redemptions.
CREATE TABLE IF NOT EXISTS #__minimarket_auto_discounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    state TINYINT UNSIGNED NOT NULL DEFAULT 0,
    discount_type VARCHAR(10) NOT NULL DEFAULT 'percent',
    amount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    basis_points INT UNSIGNED NOT NULL DEFAULT 0,
    applies_to VARCHAR(12) NOT NULL DEFAULT 'all',
    target_ids_json TEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_mm_auto_discount_period (state,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE #__minimarket_orders
  ADD COLUMN auto_discount_id INT UNSIGNED NULL DEFAULT NULL AFTER promo_code_id,
  ADD COLUMN auto_discount_title VARCHAR(160) NOT NULL DEFAULT '' AFTER auto_discount_id;
