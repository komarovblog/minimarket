-- MiniMarket 0.5.82: identify manual/telephone orders separately from site orders.
ALTER TABLE #__minimarket_orders
    ADD COLUMN order_source VARCHAR(16) NOT NULL DEFAULT 'site' AFTER payment_method;
