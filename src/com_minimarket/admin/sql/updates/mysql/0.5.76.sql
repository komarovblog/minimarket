-- MiniMarket 0.5.76: filter presentation settings and category system overrides.
-- Only additive DDL. Does not remove attributes, values, products or SKUs.
ALTER TABLE #__minimarket_attributes
    ADD COLUMN filter_search TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER filter_multiple;
ALTER TABLE #__minimarket_categories
    ADD COLUMN products_per_page SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER cards_xxl,
    ADD COLUMN filter_price_mode VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER products_per_page,
    ADD COLUMN filter_sku_mode VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER filter_price_mode,
    ADD COLUMN filter_stock_mode TINYINT NOT NULL DEFAULT -1 AFTER filter_sku_mode;

-- Migrate outdated visual modes to the new two explicit list presentations.
-- Previously selected product values and the independent multiplicity flag
-- are retained; only obsolete display-mode names are normalized.
UPDATE #__minimarket_attributes
SET filter_search = CASE
        WHEN input_type IN ('text','color')
         AND filter_mode IN ('dropdown','values','checkboxes','swatches','text')
        THEN 1 ELSE 0 END,
    filter_mode = CASE
        WHEN filter_mode IN ('values','text') THEN 'dropdown'
        WHEN filter_mode IN ('radio','swatches','boolean') THEN 'checkboxes'
        ELSE filter_mode END,
    filter_multiple = CASE
        WHEN input_type='boolean' OR filter_mode IN ('range','slider','radio','boolean')
        THEN 0 ELSE filter_multiple END
WHERE filter_mode IN ('values','text','radio','swatches','boolean','dropdown',
    'checkboxes','range','slider');
