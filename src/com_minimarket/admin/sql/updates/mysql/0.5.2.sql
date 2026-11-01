-- MiniMarket 0.5.2. Keep only the relative image paths for all existing records.
-- Strip precisely Joomla's #joomlaImage:// metadata; retain filename, image, ID, prices, SKU, stock.
UPDATE `#__minimarket_categories`
SET `image` = SUBSTRING_INDEX(`image`, '#joomlaImage://', 1)
WHERE `image` LIKE '%#joomlaImage://%';

UPDATE `#__minimarket_categories`
SET `og_image` = SUBSTRING_INDEX(`og_image`, '#joomlaImage://', 1)
WHERE `og_image` LIKE '%#joomlaImage://%';

UPDATE `#__minimarket_products`
SET `image` = SUBSTRING_INDEX(`image`, '#joomlaImage://', 1)
WHERE `image` LIKE '%#joomlaImage://%';

UPDATE `#__minimarket_products`
SET `og_image` = SUBSTRING_INDEX(`og_image`, '#joomlaImage://', 1)
WHERE `og_image` LIKE '%#joomlaImage://%';
