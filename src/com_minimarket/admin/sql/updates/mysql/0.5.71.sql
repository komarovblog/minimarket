-- MiniMarket 0.5.71: all six Bootstrap 5 grid tiers. Preserve the four
-- existing category counts by renaming columns; add SM and XXL as inherited.
-- MySQL 8.0/8.4; executed once by Joomla's SQL update mechanism.
ALTER TABLE `#__minimarket_categories`
    RENAME COLUMN `cards_mobile` TO `cards_xs`,
    RENAME COLUMN `cards_tablet` TO `cards_md`,
    RENAME COLUMN `cards_desktop` TO `cards_lg`,
    RENAME COLUMN `cards_wide` TO `cards_xl`,
    ADD COLUMN `cards_sm` TINYINT NOT NULL DEFAULT -1 AFTER `cards_xs`,
    ADD COLUMN `cards_xxl` TINYINT NOT NULL DEFAULT -1 AFTER `cards_xl`;
