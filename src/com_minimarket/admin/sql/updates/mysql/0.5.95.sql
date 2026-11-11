-- 0.5.95: separate administrative name from the SEO document title.
ALTER TABLE #__minimarket_seo_filters
    ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '' AFTER title;
UPDATE #__minimarket_seo_filters SET name=title WHERE name='';
