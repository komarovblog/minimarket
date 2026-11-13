-- 0.6.15: procurement-cost snapshots and confirmed delivery expenses.
-- Unknown historical costs remain NULL. No backfill, no inventory movements.
ALTER TABLE #__minimarket_products
 ADD COLUMN cost_price DECIMAL(15,2) NULL DEFAULT NULL AFTER price;
ALTER TABLE #__minimarket_order_items
 ADD COLUMN unit_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL AFTER line_cents,
 ADD COLUMN line_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL AFTER unit_cost_cents;
ALTER TABLE #__minimarket_shipments
 ADD COLUMN actual_cost_cents BIGINT UNSIGNED NULL DEFAULT NULL AFTER tracking_number,
 ADD COLUMN cost_reference VARCHAR(128) NOT NULL DEFAULT '' AFTER actual_cost_cents,
 ADD COLUMN cost_confirmed_at DATETIME NULL DEFAULT NULL AFTER cost_reference,
 ADD COLUMN cost_confirmed_by INT UNSIGNED NULL DEFAULT NULL AFTER cost_confirmed_at;
CREATE TABLE IF NOT EXISTS #__minimarket_shipment_cost_revisions (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 shipment_id INT UNSIGNED NOT NULL,
 previous_cost_cents BIGINT UNSIGNED NULL,
 confirmed_cost_cents BIGINT UNSIGNED NOT NULL,
 evidence_reference VARCHAR(128) NOT NULL,
 actor_user_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 PRIMARY KEY(id),
 KEY idx_mm_shipment_cost_revision (shipment_id,id),
 CONSTRAINT fk_mm_shipment_cost_revision FOREIGN KEY(shipment_id)
   REFERENCES #__minimarket_shipments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
