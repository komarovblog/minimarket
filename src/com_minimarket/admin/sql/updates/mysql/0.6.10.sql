-- MiniMarket 0.6.10 — additive allocation ledger and pickup deadlines.
-- Historical orders are marked 'legacy': NEVER invent their reserved stock.
ALTER TABLE #__minimarket_orders
  ADD COLUMN reserved_until DATETIME NULL,
  ADD COLUMN reservation_state VARCHAR(16) NOT NULL DEFAULT 'legacy',
  ADD COLUMN ready_at DATETIME NULL,
  ADD COLUMN accepted_at DATETIME NULL,
  ADD COLUMN accepted_by INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN cancelled_reason VARCHAR(512) NOT NULL DEFAULT '',
  ADD KEY idx_mm_order_reservation (reservation_state,reserved_until),
  ADD KEY idx_mm_order_payment_reservation (payment_status,reserved_until);

CREATE TABLE IF NOT EXISTS #__minimarket_stock_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    source_kind VARCHAR(8) NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'held',
    created_at DATETIME NOT NULL,
    released_at DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_allocation_key (order_id,source_kind,source_id),
    KEY idx_mm_allocation_order (order_id,status),
    CONSTRAINT fk_mm_stock_allocation_order FOREIGN KEY(order_id)
      REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
