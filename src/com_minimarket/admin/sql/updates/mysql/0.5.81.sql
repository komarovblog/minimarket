-- MiniMarket 0.5.81: delivery/payment snapshots without altering historical items.
-- Only additive columns; existing anonymous orders remain accessible to staff.
ALTER TABLE #__minimarket_orders
    ADD COLUMN delivery_method VARCHAR(16) NOT NULL DEFAULT 'pickup' AFTER customer_comment,
    ADD COLUMN delivery_city VARCHAR(120) NOT NULL DEFAULT '' AFTER delivery_method,
    ADD COLUMN delivery_address VARCHAR(512) NOT NULL DEFAULT '' AFTER delivery_city,
    ADD COLUMN delivery_rate_source VARCHAR(16) NOT NULL DEFAULT 'none' AFTER delivery_address,
    ADD COLUMN delivery_fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER delivery_rate_source,
    ADD COLUMN payment_method VARCHAR(24) NOT NULL DEFAULT 'on_receipt' AFTER delivery_fee_cents,
    ADD COLUMN items_total_cents BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER currency;

-- For historical orders the old total consisted entirely of item prices.
UPDATE #__minimarket_orders SET items_total_cents = total_cents
WHERE items_total_cents = 0;
