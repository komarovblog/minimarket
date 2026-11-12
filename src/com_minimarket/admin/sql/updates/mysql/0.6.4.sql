-- MiniMarket 0.6.4: independent responsive subcategory cards and order-option filters.
ALTER TABLE #__minimarket_categories
    ADD COLUMN subcategory_cards_xs TINYINT NOT NULL DEFAULT -1,
    ADD COLUMN subcategory_cards_sm TINYINT NOT NULL DEFAULT -1,
    ADD COLUMN subcategory_cards_md TINYINT NOT NULL DEFAULT -1,
    ADD COLUMN subcategory_cards_lg TINYINT NOT NULL DEFAULT -1,
    ADD COLUMN subcategory_cards_xl TINYINT NOT NULL DEFAULT -1,
    ADD COLUMN subcategory_cards_xxl TINYINT NOT NULL DEFAULT -1;
ALTER TABLE #__minimarket_order_options
    ADD COLUMN filter_mode VARCHAR(24) NOT NULL DEFAULT 'checkboxes',
    ADD COLUMN filter_multiple TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN filter_search TINYINT NOT NULL DEFAULT 0;
