-- MiniMarket 0.5.93: per-category list-card visibility for characteristics and order options.
-- Additive only: old assignments remain shown (DEFAULT 1).
ALTER TABLE #__minimarket_category_attributes
  ADD COLUMN show_in_list TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER required;
ALTER TABLE #__minimarket_category_order_options
  ADD COLUMN show_in_list TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER mode;
