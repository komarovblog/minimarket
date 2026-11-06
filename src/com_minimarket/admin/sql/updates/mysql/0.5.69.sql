-- MiniMarket 0.5.69: per-feed display order, without deleting or resetting existing feeds.
ALTER TABLE `#__minimarket_feeds`
    ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `state`;
