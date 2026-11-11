# MiniMarket — реестр настроек и редактируемых параметров

**Состояние:** первичная инвентаризация XML из `development` от 11.11.2026. Это список полей, **не доказательство работы**, заполнения обработчика или правильного поведения.

## Правило для каждого параметра

Перед закрытием этапа заполнить отдельно: `владелец/этап`; `значение и допустимые варианты`; `UI/XML`; `место сохранения`; `рабочий потребитель (класс/метод/шаблон)`; `эффект на витрине или в заказе`; `наследование/приоритет`; `PHP/JS/DB/браузерный тест`; `RU/EN`; `статус (описано / реализовано / подключено / протестировано / принято)`.

**Нельзя** отмечать поле «готово», если найден только XML. Для `global` и `module` это настройки расширений; `category`, `attribute`, `option`, `product` — редактируемые поля сущностей. Служебные `hidden`, `note` и Joomla ACL `rules` здесь не перечислены и не считаются функциональными настройками. Полный машинный первоисточник — соответствующий XML.

**Источник:** `src/com_minimarket/admin/config.xml`, `admin/forms/category.xml`, `admin/forms/attribute.xml`, `admin/forms/orderoption.xml`, `admin/forms/product.xml`, `src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml`.

## Глобальные настройки магазина

Источник: `src/com_minimarket/admin/config.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `catalog_title` | `text` | `MiniMarket` | `basic` | `COM_MINIMARKET_CONFIG_CATALOG_TITLE` | требует подтверждения потребителя и теста |
| `primary_shop_itemid` | `Shopmenu` | `0` | `basic` | `COM_MINIMARKET_CONFIG_PRIMARY_MENU` | требует подтверждения потребителя и теста |
| `shop_type` | `list` | `physical` | `basic` | `COM_MINIMARKET_CONFIG_SHOP_TYPE` | требует подтверждения потребителя и теста |
| `inventory_enabled` | `radio` | `1` | `basic` | `COM_MINIMARKET_CONFIG_INVENTORY_ENABLED` | требует подтверждения потребителя и теста |
| `offcanvas_cart_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_CART` | требует подтверждения потребителя и теста |
| `offcanvas_filters_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_FILTERS` | требует подтверждения потребителя и теста |
| `offcanvas_account_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_ACCOUNT` | требует подтверждения потребителя и теста |
| `offcanvas_menu_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_MENU` | требует подтверждения потребителя и теста |
| `category_show_children` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_CHILDREN` | требует подтверждения потребителя и теста |
| `category_show_products` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_PRODUCTS` | требует подтверждения потребителя и теста |
| `category_show_bundles` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_BUNDLES` | требует подтверждения потребителя и теста |
| `category_show_descendant_products` | `radio` | `0` | `display` | `COM_MINIMARKET_GLOBAL_DESCENDANT_PRODUCTS` | требует подтверждения потребителя и теста |
| `category_show_descendant_bundles` | `radio` | `0` | `display` | `COM_MINIMARKET_GLOBAL_DESCENDANT_BUNDLES` | требует подтверждения потребителя и теста |
| `category_show_image` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_IMAGE` | требует подтверждения потребителя и теста |
| `category_show_introtext` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_INTRO` | требует подтверждения потребителя и теста |
| `category_show_description` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_DESCRIPTION` | требует подтверждения потребителя и теста |
| `category_product_layout` | `list` | `list` | `display` | `COM_MINIMARKET_CATEGORY_PRODUCT_LAYOUT` | требует подтверждения потребителя и теста |
| `category_cards_xs` | `Gridcards` | `1` | `display` | `COM_MINIMARKET_CARDS_XS` | требует подтверждения потребителя и теста |
| `category_cards_sm` | `Gridcards` | `1` | `display` | `COM_MINIMARKET_CARDS_SM` | требует подтверждения потребителя и теста |
| `category_cards_md` | `Gridcards` | `2` | `display` | `COM_MINIMARKET_CARDS_MD` | требует подтверждения потребителя и теста |
| `category_cards_lg` | `Gridcards` | `3` | `display` | `COM_MINIMARKET_CARDS_LG` | требует подтверждения потребителя и теста |
| `category_cards_xl` | `Gridcards` | `4` | `display` | `COM_MINIMARKET_CARDS_XL` | требует подтверждения потребителя и теста |
| `category_cards_xxl` | `Gridcards` | `4` | `display` | `COM_MINIMARKET_CARDS_XXL` | требует подтверждения потребителя и теста |
| `category_show_add_to_cart` | `radio` | `1` | `display` | `COM_MINIMARKET_LIST_ADD_TO_CART` | требует подтверждения потребителя и теста |
| `category_quick_view` | `radio` | `0` | `display` | `COM_MINIMARKET_LIST_QUICK_VIEW` | требует подтверждения потребителя и теста |
| `category_show_badges` | `radio` | `1` | `display` | `COM_MINIMARKET_LIST_SHOW_BADGES` | требует подтверждения потребителя и теста |
| `category_products_per_page` | `list` | `10` | `display` | `COM_MINIMARKET_PRODUCTS_PER_PAGE` | требует подтверждения потребителя и теста |
| `category_product_gallery_enabled` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_PRODUCT_GALLERY` | требует подтверждения потребителя и теста |
| `category_product_gallery_limit` | `list` | `5` | `display` | `COM_MINIMARKET_DISPLAY_PRODUCT_GALLERY_LIMIT` | требует подтверждения потребителя и теста |
| `attribute_display_location` | `list` | `both` | `display` | `COM_MINIMARKET_DISPLAY_ATTRIBUTES_LOCATION` | требует подтверждения потребителя и теста |
| `option_display_location` | `list` | `both` | `display` | `COM_MINIMARKET_DISPLAY_OPTIONS_LOCATION` | требует подтверждения потребителя и теста |
| `system_display_location` | `list` | `both` | `display` | `COM_MINIMARKET_DISPLAY_SYSTEM_LOCATION` | требует подтверждения потребителя и теста |
| `filter_price_mode` | `list` | `slider` | `display` | `COM_MINIMARKET_SYSTEM_PRICE_FILTER` | требует подтверждения потребителя и теста |
| `filter_sku_mode` | `list` | `none` | `display` | `COM_MINIMARKET_SYSTEM_SKU_FILTER` | требует подтверждения потребителя и теста |
| `filter_stock_enabled` | `radio` | `0` | `display` | `COM_MINIMARKET_SYSTEM_STOCK_FILTER` | требует подтверждения потребителя и теста |
| `checkout_auth_mode` | `list` | `guest` | `checkout` | `COM_MINIMARKET_CONFIG_CHECKOUT_AUTH` | требует подтверждения потребителя и теста |
| `checkout_name_mode` | `list` | `required` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_NAME` | требует подтверждения потребителя и теста |
| `checkout_phone_mode` | `list` | `required` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_PHONE` | требует подтверждения потребителя и теста |
| `checkout_email_mode` | `list` | `optional` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_EMAIL` | требует подтверждения потребителя и теста |
| `checkout_comment_mode` | `list` | `optional` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_COMMENT` | требует подтверждения потребителя и теста |
| `delivery_pickup_enabled` | `radio` | `1` | `delivery_methods` | `COM_MINIMARKET_DELIVERY_PICKUP_ENABLED` | требует подтверждения потребителя и теста |
| `delivery_pickup_address` | `textarea` | `—` | `delivery_methods` | `COM_MINIMARKET_DELIVERY_PICKUP_ADDRESS` | требует подтверждения потребителя и теста |
| `delivery_courier_enabled` | `radio` | `0` | `delivery_methods` | `COM_MINIMARKET_DELIVERY_COURIER_ENABLED` | требует подтверждения потребителя и теста |
| `delivery_rate_source` | `list` | `directory` | `delivery_methods` | `COM_MINIMARKET_DELIVERY_RATE_SOURCE` | требует подтверждения потребителя и теста |
| `delivery_city_rates` | `textarea` | `—` | `delivery_methods` | `COM_MINIMARKET_DELIVERY_CITY_RATES` | требует подтверждения потребителя и теста |
| `payment_on_receipt_enabled` | `radio` | `1` | `payment_methods` | `COM_MINIMARKET_PAYMENT_ON_RECEIPT` | требует подтверждения потребителя и теста |
| `payment_bank_transfer_enabled` | `radio` | `0` | `payment_methods` | `COM_MINIMARKET_PAYMENT_BANK_TRANSFER` | требует подтверждения потребителя и теста |
| `payment_bank_instructions` | `textarea` | `—` | `payment_methods` | `COM_MINIMARKET_METHOD_BANK_INSTRUCTIONS` | требует подтверждения потребителя и теста |
| `registration_phone` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_PHONE` | требует подтверждения потребителя и теста |
| `registration_email` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_EMAIL` | требует подтверждения потребителя и теста |
| `registration_yandex` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_YANDEX` | требует подтверждения потребителя и теста |
| `registration_sber` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_SBER` | требует подтверждения потребителя и теста |
| `registration_mts` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_MTS` | требует подтверждения потребителя и теста |
| `registration_vk` | `radio` | `0` | `registration` | `COM_MINIMARKET_REGISTRATION_VK` | требует подтверждения потребителя и теста |
| `reviews_enabled` | `radio` | `0` | `reviews` | `COM_MINIMARKET_REVIEWS_ENABLED` | требует подтверждения потребителя и теста |
| `reviews_author_mode` | `list` | `registered` | `reviews` | `COM_MINIMARKET_REVIEWS_WHO` | требует подтверждения потребителя и теста |
| `reviews_moderation` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_MODERATE` | требует подтверждения потребителя и теста |
| `reviews_media_photo` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_PHOTO` | требует подтверждения потребителя и теста |
| `reviews_media_video` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_VIDEO` | требует подтверждения потребителя и теста |

## Параметры категории (локальные/наследуемые)

Источник: `src/com_minimarket/admin/forms/category.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` | требует подтверждения потребителя и теста |
| `alias` | `text` | `—` | `details` | `JFIELD_ALIAS_LABEL` | требует подтверждения потребителя и теста |
| `parent_id` | `Categoryparent` | `0` | `details` | `COM_MINIMARKET_CATEGORY_PARENT` | требует подтверждения потребителя и теста |
| `image` | `media` | `—` | `details` | `COM_MINIMARKET_CATEGORY_IMAGE` | требует подтверждения потребителя и теста |
| `image_alt` | `text` | `—` | `details` | `COM_MINIMARKET_CATEGORY_IMAGE_ALT` | требует подтверждения потребителя и теста |
| `introtext` | `editor` | `—` | `descriptions` | `COM_MINIMARKET_CATEGORY_INTRO` | требует подтверждения потребителя и теста |
| `description` | `editor` | `—` | `descriptions` | `JGLOBAL_DESCRIPTION` | требует подтверждения потребителя и теста |
| `show_children` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_CHILDREN` | требует подтверждения потребителя и теста |
| `show_bundles` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_BUNDLES` | требует подтверждения потребителя и теста |
| `show_products` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_PRODUCTS` | требует подтверждения потребителя и теста |
| `show_descendant_products` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_DESCENDANT_PRODUCTS` | требует подтверждения потребителя и теста |
| `show_descendant_bundles` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_DESCENDANT_BUNDLES` | требует подтверждения потребителя и теста |
| `product_layout` | `list` | `inherit` | `display` | `COM_MINIMARKET_CATEGORY_PRODUCT_LAYOUT` | требует подтверждения потребителя и теста |
| `cards_xs` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XS` | требует подтверждения потребителя и теста |
| `cards_sm` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_SM` | требует подтверждения потребителя и теста |
| `cards_md` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_MD` | требует подтверждения потребителя и теста |
| `cards_lg` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_LG` | требует подтверждения потребителя и теста |
| `cards_xl` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XL` | требует подтверждения потребителя и теста |
| `cards_xxl` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XXL` | требует подтверждения потребителя и теста |
| `products_per_page` | `list` | `0` | `display` | `COM_MINIMARKET_PRODUCTS_PER_PAGE` | требует подтверждения потребителя и теста |
| `filter_price_mode` | `list` | `inherit` | `system_filters` | `COM_MINIMARKET_SYSTEM_PRICE_FILTER` | требует подтверждения потребителя и теста |
| `filter_sku_mode` | `list` | `inherit` | `system_filters` | `COM_MINIMARKET_SYSTEM_SKU_FILTER` | требует подтверждения потребителя и теста |
| `filter_stock_mode` | `list` | `-1` | `system_filters` | `COM_MINIMARKET_SYSTEM_STOCK_FILTER` | требует подтверждения потребителя и теста |
| `attribute_rules` | `Categoryattributes` | `—` | `attributes` | `COM_MINIMARKET_CATATTR_LABEL` | требует подтверждения потребителя и теста |
| `order_option_rules` | `Categoryorderoptions` | `—` | `order_options` | `COM_MINIMARKET_ORDER_OPTIONS` | требует подтверждения потребителя и теста |
| `meta_title` | `text` | `—` | `seo` | `COM_MINIMARKET_META_TITLE` | требует подтверждения потребителя и теста |
| `meta_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_META_DESCRIPTION` | требует подтверждения потребителя и теста |
| `meta_robots` | `list` | `—` | `seo` | `COM_MINIMARKET_META_ROBOTS` | требует подтверждения потребителя и теста |
| `og_title` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_TITLE` | требует подтверждения потребителя и теста |
| `og_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_OG_DESCRIPTION` | требует подтверждения потребителя и теста |
| `og_image` | `media` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE` | требует подтверждения потребителя и теста |
| `og_image_alt` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE_ALT` | требует подтверждения потребителя и теста |
| `sort_order` | `number` | `0` | `publishing` | `COM_MINIMARKET_CATEGORY_ORDER` | требует подтверждения потребителя и теста |
| `state` | `list` | `0` | `publishing` | `JSTATUS` | требует подтверждения потребителя и теста |

## Характеристики и правила фильтрации

Источник: `src/com_minimarket/admin/forms/attribute.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` | требует подтверждения потребителя и теста |
| `code` | `text` | `—` | `details` | `COM_MINIMARKET_ATTRIBUTE_CODE` | требует подтверждения потребителя и теста |
| `display_location` | `list` | `inherit` | `details` | `COM_MINIMARKET_ENTITY_DISPLAY_LOCATION` | требует подтверждения потребителя и теста |
| `input_type` | `list` | `text` | `details` | `COM_MINIMARKET_ATTRIBUTE_TYPE` | требует подтверждения потребителя и теста |
| `value_source` | `list` | `manual` | `details` | `COM_MINIMARKET_VALUE_SOURCE` | требует подтверждения потребителя и теста |
| `unified_values` | `subform` | `—` | `details` | `COM_MINIMARKET_UNIFIED_VALUES` | требует подтверждения потребителя и теста |
| `color_display` | `list` | `both` | `details` | `COM_MINIMARKET_COLOR_DISPLAY` | требует подтверждения потребителя и теста |
| `number_unit` | `text` | `—` | `details` | `COM_MINIMARKET_NUMBER_UNIT` | требует подтверждения потребителя и теста |
| `number_min` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_MIN` | требует подтверждения потребителя и теста |
| `number_max` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_MAX` | требует подтверждения потребителя и теста |
| `number_step` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_STEP` | требует подтверждения потребителя и теста |
| `category_rules` | `Attributecategories` | `—` | `categories` | `COM_MINIMARKET_ATTRCAT_LABEL` | требует подтверждения потребителя и теста |
| `filter_mode` | `list` | `none` | `filter` | `COM_MINIMARKET_FILTER_MODE` | требует подтверждения потребителя и теста |
| `filter_multiple` | `radio` | `0` | `filter` | `COM_MINIMARKET_FILTER_MULTIPLE` | требует подтверждения потребителя и теста |
| `filter_search` | `radio` | `0` | `filter` | `COM_MINIMARKET_FILTER_SEARCH_VALUES` | требует подтверждения потребителя и теста |
| `sort_order` | `number` | `0` | `publishing` | `JGRID_HEADING_ORDERING` | требует подтверждения потребителя и теста |
| `state` | `list` | `0` | `publishing` | `JSTATUS` | требует подтверждения потребителя и теста |

## Опции заказа

Источник: `src/com_minimarket/admin/forms/orderoption.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` | требует подтверждения потребителя и теста |
| `display_location` | `list` | `inherit` | `details` | `COM_MINIMARKET_ENTITY_DISPLAY_LOCATION` | требует подтверждения потребителя и теста |
| `code` | `text` | `—` | `details` | `COM_MINIMARKET_ORDER_OPTION_CODE` | требует подтверждения потребителя и теста |
| `option_values` | `subform` | `—` | `values` | `COM_MINIMARKET_ORDER_OPTION_VALUES` | требует подтверждения потребителя и теста |
| `category_rules` | `Optioncategories` | `—` | `categories` | `COM_MINIMARKET_ORDER_OPTION_CATEGORIES` | требует подтверждения потребителя и теста |
| `state` | `list` | `1` | `publishing` | `JSTATUS` | требует подтверждения потребителя и теста |
| `sort_order` | `number` | `0` | `publishing` | `JGRID_HEADING_ORDERING` | требует подтверждения потребителя и теста |

## Товар / комплект

Источник: `src/com_minimarket/admin/forms/product.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` | требует подтверждения потребителя и теста |
| `alias` | `text` | `—` | `details` | `JFIELD_ALIAS_LABEL` | требует подтверждения потребителя и теста |
| `category_id` | `Productcategory` | `—` | `details` | `COM_MINIMARKET_PRODUCT_CATEGORY` | требует подтверждения потребителя и теста |
| `sku` | `text` | `—` | `details` | `COM_MINIMARKET_PRODUCT_SKU` | требует подтверждения потребителя и теста |
| `price` | `number` | `0.00` | `details` | `COM_MINIMARKET_PRODUCT_PRICE` | требует подтверждения потребителя и теста |
| `old_price` | `number` | `—` | `details` | `COM_MINIMARKET_BADGE_OLD_PRICE` | требует подтверждения потребителя и теста |
| `shipping_weight_grams` | `number` | `0` | `details` | `COM_MINIMARKET_SHIPPING_WEIGHT_GRAMS` | требует подтверждения потребителя и теста |
| `shipping_volume_cm3` | `number` | `0` | `details` | `COM_MINIMARKET_SHIPPING_VOLUME_CM3` | требует подтверждения потребителя и теста |
| `stock` | `number` | `0` | `details` | `COM_MINIMARKET_PRODUCT_STOCK` | требует подтверждения потребителя и теста |
| `option_stock_mode` | `radio` | `1` | `details` | `COM_MINIMARKET_OPTION_STOCK_MODE` | требует подтверждения потребителя и теста |
| `bundle_price_mode` | `list` | `fixed` | `bundle_pricing` | `COM_MINIMARKET_BUNDLE_PRICE_MODE` | требует подтверждения потребителя и теста |
| `bundle_discount_pct` | `number` | `0.00` | `bundle_pricing` | `COM_MINIMARKET_BUNDLE_DISCOUNT_PCT` | требует подтверждения потребителя и теста |
| `badge_sale` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_SALE_ENABLE` | требует подтверждения потребителя и теста |
| `badge_sale_display` | `list` | `percent` | `badges` | `COM_MINIMARKET_BADGE_DISPLAY` | требует подтверждения потребителя и теста |
| `badge_new` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_NEW_ENABLE` | требует подтверждения потребителя и теста |
| `badge_new_days` | `number` | `—` | `badges` | `COM_MINIMARKET_BADGE_DAYS` | требует подтверждения потребителя и теста |
| `badge_popular` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_POPULAR_ENABLE` | требует подтверждения потребителя и теста |
| `badge_popular_days` | `number` | `—` | `badges` | `COM_MINIMARKET_BADGE_DAYS` | требует подтверждения потребителя и теста |
| `badge_benefit` | `checkbox` | `0` | `bundle_benefit` | `COM_MINIMARKET_BADGE_BENEFIT_ENABLE` | требует подтверждения потребителя и теста |
| `badge_benefit_display` | `list` | `money` | `bundle_benefit` | `COM_MINIMARKET_BADGE_DISPLAY` | требует подтверждения потребителя и теста |
| `brand_id` | `Brandpicker` | `0` | `merchandising` | `COM_MINIMARKET_PRODUCT_BRAND` | требует подтверждения потребителя и теста |
| `collection_ids` | `Collectionpicker` | `—` | `merchandising` | `COM_MINIMARKET_PRODUCT_COLLECTIONS` | требует подтверждения потребителя и теста |
| `introtext` | `editor` | `—` | `descriptions` | `COM_MINIMARKET_CATEGORY_INTRO` | требует подтверждения потребителя и теста |
| `description` | `editor` | `—` | `descriptions` | `JGLOBAL_DESCRIPTION` | требует подтверждения потребителя и теста |
| `attribute_values` | `Productattributes` | `—` | `attributes` | `COM_MINIMARKET_PRODUCT_TAB_ATTRIBUTES` | требует подтверждения потребителя и теста |
| `image` | `media` | `—` | `images` | `COM_MINIMARKET_PRODUCT_IMAGE` | требует подтверждения потребителя и теста |
| `image_alt` | `text` | `—` | `images` | `COM_MINIMARKET_CATEGORY_IMAGE_ALT` | требует подтверждения потребителя и теста |
| `gallery` | `subform` | `—` | `images` | `COM_MINIMARKET_GALLERY_ADDITIONAL` | требует подтверждения потребителя и теста |
| `promo_video` | `subform` | `—` | `videos` | `COM_MINIMARKET_PROMO_VIDEO_SECTION` | требует подтверждения потребителя и теста |
| `videos` | `subform` | `—` | `videos` | `COM_MINIMARKET_PRODUCT_TAB_VIDEOS` | требует подтверждения потребителя и теста |
| `category_option_preview` | `Productcategoryoptions` | `—` | `variants` | `COM_MINIMARKET_PRODUCT_OPTIONS_FROM_CATEGORY` | требует подтверждения потребителя и теста |
| `variant_dimensions` | `subform` | `—` | `variants` | `COM_MINIMARKET_LOCAL_VARIANTS_TITLE` | требует подтверждения потребителя и теста |
| `variant_matrix` | `Variantbuilder` | `—` | `variants` | `COM_MINIMARKET_LOCAL_PREVIEW` | требует подтверждения потребителя и теста |
| `sku_matrix_payload` | `Skumatrix` | `—` | `variants` | `COM_MINIMARKET_MATRIX_TITLE` | требует подтверждения потребителя и теста |
| `variants` | `subform` | `—` | `variants` | `COM_MINIMARKET_PRODUCT_TAB_VARIANTS` | требует подтверждения потребителя и теста |
| `bundle_items` | `Bundlepicker` | `—` | `bundle` | `COM_MINIMARKET_BUNDLE_COMPONENTS` | требует подтверждения потребителя и теста |
| `group_editor` | `Groupeditor` | `—` | `groups` | `COM_MINIMARKET_PRODUCT_TAB_GROUPS` | требует подтверждения потребителя и теста |
| `meta_title` | `text` | `—` | `seo` | `COM_MINIMARKET_META_TITLE` | требует подтверждения потребителя и теста |
| `meta_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_META_DESCRIPTION` | требует подтверждения потребителя и теста |
| `meta_robots` | `list` | `—` | `seo` | `COM_MINIMARKET_META_ROBOTS` | требует подтверждения потребителя и теста |
| `og_title` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_TITLE` | требует подтверждения потребителя и теста |
| `og_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_OG_DESCRIPTION` | требует подтверждения потребителя и теста |
| `og_image` | `media` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE` | требует подтверждения потребителя и теста |
| `og_image_alt` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE_ALT` | требует подтверждения потребителя и теста |
| `sort_order` | `number` | `0` | `publishing` | `COM_MINIMARKET_CATEGORY_ORDER` | требует подтверждения потребителя и теста |
| `state` | `list` | `0` | `publishing` | `JSTATUS` | требует подтверждения потребителя и теста |

## Настройки отдельного административного модуля

Источник: `src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml`.

| Поле | XML-тип | По умолчанию | Секция | Метка RU/EN | Интеграция |
|---|---|---|---|---|---|
| `enabled` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_ENABLED` | требует подтверждения потребителя и теста |
| `label` | `text` | `MiniMarket` | `basic` | `MOD_MINIMARKET_TOOLBAR_LABEL` | требует подтверждения потребителя и теста |
| `show_icon` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_SHOW_ICON` | требует подтверждения потребителя и теста |
| `show_label` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_SHOW_LABEL` | требует подтверждения потребителя и теста |

## Контроль завершения

- Для каждого поля проверять default, сохранение, повторное открытие, изменение видимого поведения и восстановление значения на другой установке.
- Для параметров категории проверять приоритеты «глобально → категория → наследование», шесть Bootstrap 5 уровней, отключение недоступных опций при выборе списка.
- Для SKU, атрибутов, фильтров, стоимости и доставки обязательно тестировать серверное действие, не только JS-реакцию.
- Если поле относится к будущему режиму магазина, пометить «не реализовано» или деактивировать; не выдавать макет как рабочую интеграцию.
