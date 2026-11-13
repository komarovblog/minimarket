-- 0.6.11: verified payment operations. Additive; do not backfill a fictional
-- bank receipt for legacy paid orders.
CREATE TABLE IF NOT EXISTS #__minimarket_payment_records (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 order_id INT UNSIGNED NOT NULL,
 amount_cents BIGINT UNSIGNED NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'RUB',
 method VARCHAR(24) NOT NULL,
 source VARCHAR(24) NOT NULL DEFAULT 'manual',
 external_reference VARCHAR(128) NOT NULL,
 recorded_by INT UNSIGNED NOT NULL DEFAULT 0,
 confirmed_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY uq_mm_payment_origin_reference (source,external_reference),
 KEY idx_mm_payment_order (order_id,confirmed_at),
 CONSTRAINT fk_mm_payment_order FOREIGN KEY(order_id)
     REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
