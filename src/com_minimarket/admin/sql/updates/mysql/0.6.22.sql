-- MiniMarket 0.6.22: manually entered monthly goods targets and immutable revisions.
-- Historical sales orders are not altered. No stock movements.
CREATE TABLE IF NOT EXISTS #__minimarket_sales_plans (
    period_year SMALLINT UNSIGNED NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL,
    target_goods_cents BIGINT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(period_year,period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_sales_plan_revisions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_year SMALLINT UNSIGNED NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL,
    previous_target_cents BIGINT UNSIGNED NULL,
    target_goods_cents BIGINT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    KEY idx_mm_plan_revision_period(period_year,period_month,created_at),
    CONSTRAINT fk_mm_sales_plan_revision FOREIGN KEY(period_year,period_month)
        REFERENCES #__minimarket_sales_plans(period_year,period_month) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
