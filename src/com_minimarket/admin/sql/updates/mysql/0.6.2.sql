-- MiniMarket 0.6.2: three-state SEO landing predicates (legacy pages remain unchanged).
ALTER TABLE #__minimarket_seo_filters
    ADD COLUMN conditions_json TEXT NULL DEFAULT NULL;
