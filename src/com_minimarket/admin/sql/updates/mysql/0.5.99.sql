-- MiniMarket 0.5.99: mixed physical/digital orders with distinct payment and fulfillment states.
-- Additive upgrade; keep every existing product, order, order item, and order snapshot.
ALTER TABLE #__minimarket_products
    ADD COLUMN digital_asset_key VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN digital_filename VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE #__minimarket_orders
    ADD COLUMN payment_status VARCHAR(16) NOT NULL DEFAULT 'unpaid',
    ADD COLUMN paid_at DATETIME NULL,
    ADD COLUMN fulfillment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    ADD COLUMN fulfilled_at DATETIME NULL;
ALTER TABLE #__minimarket_order_items
    ADD COLUMN delivery_type VARCHAR(12) NOT NULL DEFAULT 'physical',
    ADD COLUMN digital_asset_key VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN digital_filename VARCHAR(255) NOT NULL DEFAULT '';
CREATE TABLE IF NOT EXISTS #__minimarket_digital_grants (
    order_item_id BIGINT UNSIGNED NOT NULL,
    token_nonce CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_downloads INT UNSIGNED NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL,
    last_sent_at DATETIME NULL,
    PRIMARY KEY (order_item_id),
    CONSTRAINT fk_mm_digital_grant_item FOREIGN KEY (order_item_id)
        REFERENCES #__minimarket_order_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS #__minimarket_order_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    old_value VARCHAR(32) NOT NULL,
    new_value VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_mm_order_event (order_id, created_at),
    CONSTRAINT fk_mm_order_event_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
