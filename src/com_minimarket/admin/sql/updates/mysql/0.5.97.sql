-- MiniMarket 0.5.97: immutable user-defined checkout fields on each order.
CREATE TABLE IF NOT EXISTS #__minimarket_order_custom_values (
    order_id INT UNSIGNED NOT NULL,
    field_code VARCHAR(40) NOT NULL,
    field_title VARCHAR(120) NOT NULL,
    field_value TEXT NOT NULL,
    PRIMARY KEY (order_id, field_code),
    CONSTRAINT fk_mm_order_custom_parent FOREIGN KEY (order_id)
        REFERENCES #__minimarket_orders (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
