-- MiniMarket 0.6.25: category recommendation sources and attribute matching.
-- Absent row means inherit from parent; no historic product recommendations changed.
CREATE TABLE IF NOT EXISTS #__minimarket_category_recommendations (
 category_id INT UNSIGNED NOT NULL,
 similar_scope VARCHAR(12) NOT NULL DEFAULT 'inherit',
 similar_categories VARCHAR(2000) NOT NULL DEFAULT '',
 similar_attributes VARCHAR(2000) NOT NULL DEFAULT '',
 together_scope VARCHAR(12) NOT NULL DEFAULT 'inherit',
 together_categories VARCHAR(2000) NOT NULL DEFAULT '',
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(category_id),
 CONSTRAINT fk_mm_cat_recommendation FOREIGN KEY(category_id)
  REFERENCES #__minimarket_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
