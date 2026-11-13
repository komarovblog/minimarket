-- MiniMarket 0.6.18: CRM notes, support tickets and messages. No stock changes.

-- CRM notes are tied to verified Joomla user IDs; guests are not merged.
CREATE TABLE IF NOT EXISTS #__minimarket_customer_notes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 author_user_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 KEY idx_mm_customer_note_user (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal tickets and messages. Never invent a customer identity.
CREATE TABLE IF NOT EXISTS #__minimarket_support_tickets (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NULL,
 user_id INT UNSIGNED NOT NULL DEFAULT 0,
 subject VARCHAR(180) NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'new',
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY idx_mm_ticket_status (status,created_at),
 KEY idx_mm_ticket_customer (user_id,created_at),
 KEY idx_mm_ticket_order (order_id),
 CONSTRAINT fk_mm_ticket_order FOREIGN KEY(order_id)
  REFERENCES #__minimarket_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_support_messages (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 ticket_id INT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 internal TINYINT NOT NULL DEFAULT 1,
 author_user_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 KEY idx_mm_support_timeline (ticket_id,id),
 CONSTRAINT fk_mm_support_message_ticket FOREIGN KEY(ticket_id)
  REFERENCES #__minimarket_support_tickets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
