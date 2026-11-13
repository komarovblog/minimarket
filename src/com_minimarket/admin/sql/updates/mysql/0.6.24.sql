-- MiniMarket 0.6.24: personal read receipts and genuine ticket priority/references.
-- All queue items remain sourced from orders/shipments/returns/tickets.
-- Read does not resolve a business problem; no external messages are sent.
CREATE TABLE IF NOT EXISTS #__minimarket_queue_reads (
 user_id INT UNSIGNED NOT NULL,
 source_kind VARCHAR(16) NOT NULL,
 source_id INT UNSIGNED NOT NULL,
 source_signature CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 read_at DATETIME NOT NULL,
 PRIMARY KEY(user_id,source_kind,source_id),
 KEY idx_mm_queue_kind_source (source_kind,source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE #__minimarket_support_tickets
 ADD COLUMN priority VARCHAR(8) NOT NULL DEFAULT 'normal',
 ADD COLUMN shipment_id INT UNSIGNED NULL DEFAULT NULL,
 ADD COLUMN return_id INT UNSIGNED NULL DEFAULT NULL,
 ADD KEY idx_mm_ticket_priority (priority,status,created_at),
 ADD KEY idx_mm_ticket_shipment (shipment_id),
 ADD KEY idx_mm_ticket_return (return_id),
 ADD CONSTRAINT fk_mm_ticket_shipment FOREIGN KEY(shipment_id)
  REFERENCES #__minimarket_shipments(id) ON DELETE RESTRICT,
 ADD CONSTRAINT fk_mm_ticket_return FOREIGN KEY(return_id)
  REFERENCES #__minimarket_returns(id) ON DELETE RESTRICT;
