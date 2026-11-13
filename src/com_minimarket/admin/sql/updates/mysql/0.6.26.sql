-- MiniMarket 0.6.26. First-party cart recovery observability, no unsolicited messaging.
-- No shopper email/phone saved, no guessed guest identification, no prices.
CREATE TABLE IF NOT EXISTS #__minimarket_cart_snapshots (
 cart_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 user_id INT UNSIGNED NOT NULL DEFAULT 0,
 items_json MEDIUMTEXT NOT NULL,
 item_units SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 cart_status VARCHAR(16) NOT NULL DEFAULT 'active',
 converted_order_id INT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(cart_key),
 KEY idx_mm_cart_abandoned (cart_status,updated_at),
 KEY idx_mm_cart_user (user_id,updated_at),
 KEY idx_mm_cart_conversion (converted_order_id),
 CONSTRAINT fk_mm_cart_converted FOREIGN KEY(converted_order_id)
   REFERENCES #__minimarket_orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
