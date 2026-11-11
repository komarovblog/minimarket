-- MiniMarket 0.5.85: promotion definitions only. No checkout redemption enabled.
-- Existing fixed-amount codes remain disabled until explicitly published.
ALTER TABLE #__minimarket_promo_codes
  ADD COLUMN discount_type VARCHAR(12) NOT NULL DEFAULT 'fixed' AFTER amount_cents,
  ADD COLUMN percent_value DECIMAL(5,2) NULL DEFAULT NULL AFTER discount_type,
  ADD COLUMN starts_at DATETIME NULL AFTER percent_value,
  ADD COLUMN ends_at DATETIME NULL AFTER starts_at,
  ADD COLUMN min_cart_cents BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ends_at,
  ADD COLUMN usage_limit INT UNSIGNED NULL AFTER min_cart_cents,
  ADD COLUMN per_customer_limit INT UNSIGNED NULL AFTER usage_limit,
  ADD COLUMN used_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER per_customer_limit,
  ADD COLUMN applies_to VARCHAR(12) NOT NULL DEFAULT 'all' AFTER used_count,
  ADD COLUMN target_ids_json TEXT NULL AFTER applies_to,
  ADD COLUMN combinable TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER target_ids_json,
  ADD COLUMN updated_at DATETIME NULL AFTER created_at;
