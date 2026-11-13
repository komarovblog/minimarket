-- MiniMarket 0.6.16: shipment events and problems; no inventory movement.
ALTER TABLE #__minimarket_shipments
  ADD COLUMN problem_status VARCHAR(16) NOT NULL DEFAULT 'none' AFTER status,
  ADD COLUMN problem_code VARCHAR(24) NOT NULL DEFAULT '' AFTER problem_status;
-- Immutable delivery events, never inventory movements.
CREATE TABLE IF NOT EXISTS #__minimarket_shipment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipment_id INT UNSIGNED NOT NULL,
    event_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'manual',
    source_reference VARCHAR(128) NOT NULL,
    event_code VARCHAR(32) NOT NULL,
    problem_code VARCHAR(24) NOT NULL DEFAULT '',
    note VARCHAR(2000) NOT NULL DEFAULT '',
    occurred_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_shipment_event_key (shipment_id,event_key),
    UNIQUE KEY uq_mm_shipment_source_ref (shipment_id,source,source_reference),
    KEY idx_mm_shipment_events_date (shipment_id,occurred_at,id),
    CONSTRAINT fk_mm_shipment_event_parent FOREIGN KEY(shipment_id)
      REFERENCES #__minimarket_shipments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

