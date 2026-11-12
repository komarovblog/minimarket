-- MiniMarket 0.6.7: audit-ready returns and repeatable deliveries.
-- Order snapshots, prices, payments and existing shipment rows are preserved.
ALTER TABLE #__minimarket_orders
    ADD COLUMN payment_reference VARCHAR(128) NOT NULL DEFAULT '',
    ADD COLUMN payment_url VARCHAR(2048) NOT NULL DEFAULT '';

ALTER TABLE #__minimarket_shipments
    DROP INDEX idx_mm_shipment_one_per_order,
    ADD INDEX idx_mm_shipment_order (order_id),
    ADD COLUMN reason VARCHAR(24) NOT NULL DEFAULT 'initial';

CREATE TABLE IF NOT EXISTS #__minimarket_returns (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    shipment_id INT UNSIGNED NULL,
    reason VARCHAR(24) NOT NULL DEFAULT 'defective',
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    note TEXT NULL,
    resolution VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_mm_return_order (order_id,created_at),
    KEY idx_mm_return_status (status,created_at),
    KEY idx_mm_return_shipment (shipment_id),
    CONSTRAINT fk_mm_return_order FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mm_return_shipment FOREIGN KEY (shipment_id)
        REFERENCES #__minimarket_shipments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_return_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    return_id INT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mm_return_item (return_id,order_item_id),
    KEY idx_mm_return_item_order_line (order_item_id),
    CONSTRAINT fk_mm_return_item_return FOREIGN KEY(return_id)
        REFERENCES #__minimarket_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_mm_return_item_order_item FOREIGN KEY(order_item_id)
        REFERENCES #__minimarket_order_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
