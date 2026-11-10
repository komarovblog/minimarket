-- MiniMarket 0.5.82: identify manual/telephone orders separately from site orders.
ALTER TABLE #__minimarket_orders
    ADD COLUMN order_source VARCHAR(16) NOT NULL DEFAULT 'site' AFTER payment_method;

-- MiniMarket 0.5.82: location of info attributes and order option summaries.
ALTER TABLE #__minimarket_attributes
    ADD COLUMN display_location VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER color_display;
ALTER TABLE #__minimarket_order_options
    ADD COLUMN display_location VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER code;
