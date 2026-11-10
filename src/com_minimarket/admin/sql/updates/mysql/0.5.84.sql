-- MiniMarket 0.5.84: reversible trash keeps all order and line snapshots.
ALTER TABLE #__minimarket_orders
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER order_source;

-- Shipment fulfillment is independent of order payment and catalog records.
CREATE TABLE IF NOT EXISTS #__minimarket_shipments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    carrier VARCHAR(16) NOT NULL DEFAULT 'own',
    tracking_number VARCHAR(120) NOT NULL DEFAULT '',
    status VARCHAR(16) NOT NULL DEFAULT 'new',
    note TEXT NULL,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY idx_mm_shipment_one_per_order(order_id),
    KEY idx_mm_shipment_status(status,created_at),
    CONSTRAINT fk_mm_shipment_order FOREIGN KEY(order_id)
        REFERENCES #__minimarket_orders(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Discount codes are an admin catalog only until the checkout promotion
-- rules are agreed. Never invent a discount or mark any order as redeemed.
CREATE TABLE IF NOT EXISTS #__minimarket_promo_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    state TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    UNIQUE KEY idx_mm_promo_code(code),
    KEY idx_mm_promo_state(state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
