-- MiniMarket 0.5.78: checkout request ledger. No payment, shipping,
-- invoice, inventory hold/deduction or email delivery at this stage.
CREATE TABLE IF NOT EXISTS #__minimarket_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    checkout_key CHAR(32) NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(254) NOT NULL DEFAULT '',
    customer_comment TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    currency CHAR(3) NOT NULL DEFAULT 'RUB',
    total_cents BIGINT UNSIGNED NOT NULL,
    item_count INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_mm_order_checkout_key (checkout_key),
    KEY idx_mm_order_status_created (status, created_at),
    KEY idx_mm_order_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NOT NULL DEFAULT 0,
    sku VARCHAR(100) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL,
    unit_cents BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_cents BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_mm_order_line_order (order_id),
    CONSTRAINT fk_mm_order_line_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
