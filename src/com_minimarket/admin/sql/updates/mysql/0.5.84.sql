-- MiniMarket 0.5.84: reversible trash keeps all order and line snapshots.
ALTER TABLE #__minimarket_orders
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER order_source;
