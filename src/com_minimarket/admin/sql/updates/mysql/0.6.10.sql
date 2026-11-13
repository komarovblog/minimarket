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

-- Canonical seven-stage fulfillment; keep completed/cancelled main order states intact.
ALTER TABLE #__minimarket_orders
    MODIFY fulfillment_status VARCHAR(24) NOT NULL DEFAULT 'awaiting_assembly';
ALTER TABLE #__minimarket_shipments
    MODIFY status VARCHAR(24) NOT NULL DEFAULT 'awaiting_assembly';
UPDATE #__minimarket_orders SET fulfillment_status='awaiting_assembly'
    WHERE fulfillment_status='pending';
UPDATE #__minimarket_orders SET fulfillment_status='in_transit'
    WHERE fulfillment_status='shipped';
UPDATE #__minimarket_shipments SET status='awaiting_assembly'
    WHERE status='new';


-- Notification preferences are owned by the real Joomla user ID, not a copy
-- of the CMS account. Only installed/allowed channels are exposed in the UI.
CREATE TABLE IF NOT EXISTS #__minimarket_notification_preferences (
  user_id INT UNSIGNED NOT NULL,
  event_code VARCHAR(40) NOT NULL,
  channel_code VARCHAR(80) NOT NULL,
  enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY(user_id,event_code,channel_code),
  KEY idx_mm_notify_user_enabled (user_id,enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One execution record per event reference and channel. No external API keys
-- and no full personal message body are stored in the log.
CREATE TABLE IF NOT EXISTS #__minimarket_notification_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  event_code VARCHAR(40) NOT NULL,
  channel_code VARCHAR(80) NOT NULL,
  event_reference VARCHAR(80) NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  error_code VARCHAR(40) NOT NULL DEFAULT '',
  PRIMARY KEY(id),
  UNIQUE KEY uq_mm_notify_event (order_id,event_code,channel_code,event_reference),
  KEY idx_mm_notify_status_created (status,created_at),
  CONSTRAINT fk_mm_notify_order FOREIGN KEY(order_id)
    REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

