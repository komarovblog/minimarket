-- MiniMarket 0.6.0: checkout purchase verification, guest digital recipient snapshots.
-- Additive; all existing digital orders fall back to historical buyer email.
ALTER TABLE #__minimarket_orders
    ADD COLUMN digital_recipient_email VARCHAR(254) NOT NULL DEFAULT '';
ALTER TABLE #__minimarket_products
    ADD COLUMN digital_delivery_mode VARCHAR(16) NOT NULL DEFAULT 'file',
    ADD COLUMN digital_delivery_note VARCHAR(2000) NOT NULL DEFAULT '';
ALTER TABLE #__minimarket_order_items
    ADD COLUMN digital_delivery_mode VARCHAR(16) NOT NULL DEFAULT 'file',
    ADD COLUMN digital_delivery_note VARCHAR(2000) NOT NULL DEFAULT '',
    ADD COLUMN digital_fulfilled_at DATETIME NULL,
    ADD COLUMN digital_manual_message TEXT NULL;
CREATE TABLE IF NOT EXISTS #__minimarket_digital_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    encrypted_code TEXT NOT NULL,
    order_item_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    assigned_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mm_digital_key_fingerprint (product_id, fingerprint),
    KEY idx_mm_digital_key_available (product_id, order_item_id, id),
    CONSTRAINT fk_mm_digital_key_product FOREIGN KEY (product_id)
        REFERENCES #__minimarket_products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

