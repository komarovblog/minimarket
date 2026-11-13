-- MiniMarket 0.6.27: configurable event-based plain-text templates and marketing flow drafts.
-- These tables NEVER send a message by themselves. Future opt-in/scheduler required.
CREATE TABLE IF NOT EXISTS #__minimarket_message_templates (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 title VARCHAR(160) NOT NULL,
 audience VARCHAR(12) NOT NULL,
 event_code VARCHAR(48) NOT NULL DEFAULT '',
 channel_code VARCHAR(16) NOT NULL DEFAULT 'email',
 subject VARCHAR(190) NOT NULL,
 body TEXT NOT NULL,
 state TINYINT UNSIGNED NOT NULL DEFAULT 0,
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 PRIMARY KEY(id),
 KEY idx_mm_template_event(audience,event_code,channel_code,state),
 KEY idx_mm_template_state(state,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_marketing_flows (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 title VARCHAR(160) NOT NULL,
 trigger_code VARCHAR(32) NOT NULL,
 state VARCHAR(12) NOT NULL DEFAULT 'draft',
 stop_on_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 PRIMARY KEY(id),
 KEY idx_mm_flow_trigger(trigger_code,state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_marketing_flow_steps (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 flow_id INT UNSIGNED NOT NULL,
 step_no TINYINT UNSIGNED NOT NULL,
 delay_minutes INT UNSIGNED NOT NULL,
 template_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_mm_flow_step(flow_id,step_no),
 KEY idx_mm_flow_template(template_id),
 CONSTRAINT fk_mm_flow_step FOREIGN KEY(flow_id)
  REFERENCES #__minimarket_marketing_flows(id) ON DELETE CASCADE,
 CONSTRAINT fk_mm_flow_template FOREIGN KEY(template_id)
  REFERENCES #__minimarket_message_templates(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
