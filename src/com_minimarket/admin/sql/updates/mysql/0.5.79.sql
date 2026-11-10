-- MiniMarket 0.5.79 — account/phone verification foundation.
-- Existing orders with user_id=0 are retained; application no longer inserts
-- anonymous orders. All writes to these new tables will require an SMS provider.
CREATE TABLE IF NOT EXISTS #__minimarket_user_phones (
    user_id INT UNSIGNED NOT NULL,
    phone_e164 VARCHAR(16) NOT NULL,
    verified_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY idx_mm_phone_unique (phone_e164)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_phone_challenges (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    challenge_key CHAR(32) NOT NULL,
    phone_e164 VARCHAR(16) NOT NULL,
    intent VARCHAR(16) NOT NULL,
    code_digest CHAR(64) NOT NULL,
    nonce CHAR(32) NOT NULL,
    session_digest CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_mm_phone_challenge_key (challenge_key),
    KEY idx_mm_phone_challenge_phone_time (phone_e164, created_at),
    KEY idx_mm_phone_challenge_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
