-- MiniMarket 0.5.43: optional photo for reusable order-option value.
-- Existing option codes, IDs, colors and SKU assignments are unchanged.
ALTER TABLE `#__minimarket_order_option_values`
  ADD COLUMN `sample_image` VARCHAR(1024) NOT NULL DEFAULT '' AFTER `color_hex`;
