-- MiniMarket 0.6.21: managed product recommendations; no inventory movement.
-- Managed recommendations: independent marketing relations, not SKU/variant groups.
CREATE TABLE IF NOT EXISTS #__minimarket_recommendation_settings (
    product_id INT UNSIGNED NOT NULL,
    similar_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
    together_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(product_id),
    CONSTRAINT fk_mm_recommendation_source FOREIGN KEY(product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS #__minimarket_recommendation_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_product_id INT UNSIGNED NOT NULL,
    target_product_id INT UNSIGNED NOT NULL,
    kind VARCHAR(12) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE KEY uq_mm_recommendation_link (source_product_id,target_product_id,kind),
    KEY idx_mm_recommendation_target(target_product_id),
    CONSTRAINT fk_mm_recommendation_link_source FOREIGN KEY(source_product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mm_recommendation_link_target FOREIGN KEY(target_product_id)
        REFERENCES #__minimarket_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

