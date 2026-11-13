# MiniMarket 0.6.0 — действующий реестр настроек и полей

Обновлён 12 ноября 2026 по **фактическим XML исходникам** ветки `development`. Это перечисление полей конфигурации/форм. **Наличие XML-поля не означает рабочую бизнес-логику**: статусы ниже разделяют реально подключённые настройки и будущие связи.

## 0.6.13 — единое условное отображение настроек

**Стандарт для всех новых и поддерживаемых форм MiniMarket:** зависимый параметр видим только тогда, когда выбранная управляющая настройка делает его применимым. Для стандартных Joomla XML-форм использовать `showon` (включая `[AND]` и перечисление значений), а не disabled-поля, самописные карточки, произвольные цвета или CSS. Скрытие происходит сразу, не требует сохранения, не сбрасывает старые сохранённые значения. Сервер читает/проверяет только параметры, относящиеся к активному режиму; UI сам по себе не является защитой бизнес-логики.

Покрыто: оформление покупки (при `guest` скрыт выбор способа подтверждения; описания и поля шагов зависят от включения), маска телефона, дополнительные поля заказа (тип → маска/placeholder/варианты), отзывы, карточная сетка каталога, опции категорий с наследованием, атрибуты, длительность флажков товара, параметры комплектной цены, цифровые и физические товары, SKU-описания и SKU-видео. Режим `inherit` **показывает** настройки, пока их применимость зависит от глобального значения: не скрывать потенциально полезное поле на основании лишь локального `inherit`. Неприменимые старые значения маски/списка у дополнительного поля не валидируются как настройки нового типа.

Исключения: физические складские поля, которые блокируются с отправкой сохранённого значения ради защиты остатков; специальные редакторы со своими динамическими списками (SEO-условия, фиды, тарифы), где XML `showon` неприменим. Их принцип — та же динамическая видимость без уничтожения данных. Действительная визуальная проверка Joomla 6 в обеих темах остаётся отдельным этапом.

Тест: `scripts/test-0-6-13-conditional-settings.php`. Подробности: [RELEASE_0_6_13.md](RELEASE_0_6_13.md).

## Актуализация 13.11.2026: резервы, отчёт «Остатки», необязательный Scheduler

**Окончательное объяснение и ограничения:** [ORDER_STOCK_REPORTS_DECISIONS.md](ORDER_STOCK_REPORTS_DECISIONS.md). Версия схемы `0.6.12` не добавляет таблиц. Действующий резерв физического товара **уже внутри com_minimarket**; Joomla Scheduler plugin — только опциональный периодический запуск, не механизм резерва. В обычной сборке плагин не предлагается к установке.

| Поле из `admin/config.xml` | Значение по умолчанию | Фактический потребитель | Состояние |
|---|---|---|---|
| `inventory_enabled` | 1 | `StockReservation::allocate`, `Stockreport/HtmlView` (предупреждение), складские формы | Основной складской учёт |
| `pickup_unpaid_reserve_days` | 3 | `StockReservation::allocate`, `OrderController::fulfillment` | Период для неоплаченного самовывоза |
| `pickup_paid_review_days` | 7 | `OrderController::payment` | Контроль оплаченного самовывоза, НЕ автоматическая отмена |
| `pickup_remind_before_days` | 3 | `ReservationMonitor::run`, `Orders/HtmlView` | Порог фильтров доступен без Scheduler; автоматическая отправка только при реально работающем Scheduled Task |
| `pickup_remind_final_days` | 1 | Те же потребители | Вторая ступень, без гарантии отправки при отсутствии фонового запуска |
| `pickup_auto_cancel_unpaid` | 0 | `ReservationMonitor::run` | По умолчанию OFF; только завершённый сборкой, неоплаченный самовывоз `on_receipt`, при работающем Scheduler |

**Отчёт** `view=stockreport`: периода `filter_period=30/90`, текстового поиска, состояния запаса `filter_stock`, категории `filter_abc` и сортировки `filter_sort` — фильтры **экрана**, не глобальные опции и не дополнительные таблицы. Сервис `StockReport::load/analyze`, read-only. **Обратная приёмка после возврата отсутствует** и не подразумевается режимом `returns.status=received`.


## Новая настройка 0.6.9: основной статус заказа

| Поле | Хранение | Потребители | Значение |
|---|---|---|---|
| `order_manual_status_enabled` | `admin/config.xml` → `#__extensions.params` компонента | `OrderLifecycle::manualEnabled` → `OrderController::status`, `ListBulkActions::apply`, `Orders/HtmlView`, `Order/HtmlView` → карточка заказа / массовая панель | 1 (default): допустимые ручные переходы; 0: только автоматический основной статус. Не блокирует внесение достоверных фактов оплаты/доставки. |

Автоматический путь: DeliveryController.create/update и массовые доставки → OrderLifecycle::refresh → orders.fulfillment_status/status → карточка / список, с аудитом OrderHistory. Оплата → OrderController.payment → refresh. Закрытие Return → ReturncaseController.status → refresh. Открытый Return останавливает завершение незавершённого заказа; терминальный completed не переоткрывается. CI: `scripts/test-0-6-9-lifecycle.php`; CI 6/6 PASS [#43305465980](https://github.com/komarovblog/minimarket/actions/runs/43305465980), браузерная приёмка ещё не подтверждена. Внешние ЮKassa/СДЭК и безопасные резервы SKU не добавлены.

## Настройки 0.6.4 — сквозная проверка

| Параметры | Владелец / хранилище | Читает и исполняет | Значение / наследование | Статус |
|---|---|---|---|---|
| `subcategory_cards_xs/sm/md/lg/xl/xxl` | `admin/config.xml`, `com_minimarket` component params | `Site/Service/CategoryDisplayOptions::resolve` → `subcategoryGridClasses` → `site/tmpl/category/default.php` | 1–6 колонок, defaults 1/1/2/3/4/4 | CI PASS; Joomla browser pending |
| `subcategory_cards_*` в категории | `admin/forms/category.xml`; `#__minimarket_categories` + upgrade `0.6.4.sql` | тот же `CategoryDisplayOptions` → Bootstrap `row-cols-*` | `-1` = глобальная настройка; отдельно от режима `product_layout` | CI PASS; Joomla browser pending |
| `orderoption.filter_mode` | `admin/forms/orderoption.xml`; `#__minimarket_order_options` + upgrade | `OrderoptionTable::check` → `FacetedCatalog::build` → `site/tmpl/category/filters.php` | `none/dropdown/checkboxes`, default `checkboxes` | CI PASS; Joomla browser pending |
| `orderoption.filter_multiple` | там же | `FacetedCatalog`, `FacetedCatalog::selection`, публичный filter input | 0/1 (одиночный / множественный), default 1 | CI PASS; Joomla browser pending |
| `orderoption.filter_search` | там же | `FacetedCatalog`, витрина и фильтр поиска значений | Show/Hide, default 0 | CI PASS; Joomla browser pending |
| `attribute.filter_multiple/filter_search` | `admin/forms/attribute.xml`, прежние колонки `#__minimarket_attributes` | `AttributeSettings`, `FacetedCatalog`, публичный facet | Show/Hide с native select вместо radio; схема без изменений | CI PASS; Joomla browser pending |

Новые редакторы значений используют только UI-обёртку `compact-values.js` вокруг имеющихся Joomla Subform: не изменяют схему или ID значений. Общий reference picker — `admin/tmpl/shared/reference-picker.php`, `media/js/admin-reference-picker.js`; после выбора одиночной сущности «Сменить», при переданном адресе Edit. Отдельный `mod_minimarket_toolbar` фиксирован на 0.6.3 и больше не выпускается при обновлении компонента. См. [RELEASE_0_6_4.md](RELEASE_0_6_4.md), [Actions #43244843772](https://github.com/komarovblog/minimarket/actions/runs/43244843772).

## Правила для каждого параметра

Документировать: владелец / функциональный этап; значение/default; UI/XML; путь хранения; рабочий потребитель (метод/шаблон); приоритет наследования; RU/EN; unit/integration/браузерный тест; статус «описано → реализовано → интегрировано → автоматические тесты PASS → браузерная приёмка PASS → принято».

После принятия не создавать вторую настройку в другом расширении. В частности, методы оплаты/доставки редактируются в собственных разделах и хранятся в Joomla component params; способы регистрации используют `com_users`. Глобальные вкладки этих методов удалены без удаления сохранённых значений.

## Работающие связи настроек 0.5.91

| Настройка / набор | Хранилище | Читает/исполняет | Статус |
|---|---|---|---|
| `category_show_children/products/bundles/descendant_*` | com_minimarket params → локальная категория | `CategoryDisplayOptions::resolve`, `View/Category/HtmlView`, `CatalogRepository` | Реализовано, CI PASS |
| `category_product_layout`, `category_cards_{xs,sm,md,lg,xl,xxl}` | component params → category overrides | `CategoryDisplayOptions`, category template; Bootstrap row-cols | Реализовано, CI PASS |
| `category_show_sort` | component params | category template | Реализовано, CI PASS |
| `category_lazy_loading` | component params | category template; lazy/eager | Реализовано, CI PASS |
| `category_children_per_page` (устаревший параметр) | Сохранённые значения params игнорируются | Category HtmlView всегда показывает все опубликованные прямые подкатегории | Удалён из формы 0.5.98, миграция не нужна |
| `category_allow_layout_switch` | component params | Category HtmlView + grid/list links | Реализовано, CI PASS |
| `category_list_show_attributes/options` | component params | category template | Реализовано как **глобальные** флаги, индивидуальное управление на привязках ещё не создано |
| `category_show_add_to_cart/quick_view/show_badges/gallery_*` | component params | category template | Существующий функционал сохранён |
| `filter_price_mode/sku_mode/stock_enabled`, `system_display_location` | component params → category filters | CategoryDisplayOptions + FacetedCatalog/site UI | Параметры перенесены в «Основные», расширенное поле-на-поле управление не готово |
| `checkout_auth_mode`: `guest` / `email` | component params | `CheckoutSettings`, Checkout controller/view | Гость доступен, email-режим требует уже вошедшего Joomla-пользователя; встроенной регистрации в момент заказа нет |
| `checkout_verification_method=email` | component params | Joomla `com_users` | Только стандартная email-активация Joomla; **не одноразовый код**, не SMS |
| `checkout_{name,phone,email,comment}_mode`, `checkout_phone_mask`, `checkout_custom_fields` | component params → order custom values | CheckoutSettings, CheckoutExtraFields, CheckoutData, OrderStorage | Поля и маски подключены 0.5.97, CI PASS; браузерная приёмка ожидается |
| Built-in Delivery/Payment | component params через `MethodSettings` | `CheckoutDelivery`, MethodsettingsController | Два встроенных метода каждого типа, отдельные карточки редакторов, CI PASS |
| Email Registration | `com_users` params | Joomla Users, Registrationmethods view | Только нативная активация; провайдеры SMS/OAuth без настоящих плагинов недоступны |
| `feed.category_ids`, products, manual/auto | `#__minimarket_feeds` | FeedSelection, FeedConfig, feed editor, public FeedController | Только явные категории; отдельные поисковые фильтры справа/слева |
| Промокоды `fixed/percent`, публикация | `#__minimarket_promo_codes` | PromoCodeData/Controller, PromotionService, checkout | Новые/отредактированные — скидка со всей корзины, публикация с ACL; старые ограниченные купоны не перенормализованы |

**Не реализовано:** Sitemap XML c Joomla articles; SKU-row export/import v2; category→option→value combination builder; независимые типы системной фильтрации помимо существующих Цена/Артикул/Наличие; проверка по коду SMS/Email в самом checkout и живые провайдерские адаптеры. Эти задачи запрещено обозначать «готово» по одному UI/включенному флагу.


## SEO-страницы — новая спецификация 0.6.2

| Поле | Место → потребитель | Условия |
|---|---|---|
| `#__minimarket_seo_filters.category_id` | Категория в SEO-редакторе → SeoCategoryChoices, SEO View | **Одна** опубликованная категория. Только её опубликованные фильтруемые характеристики и доступные опции. |
| `#__minimarket_seo_filters.conditions_json` | Редактор SEO → SeoLandingRules / SeoLandingResolver | Ключи `a<ID>`, `s<ID>`, `price`, `sku`, `stock`. Режим `unused` **не записывается**; `any` — фильтр обязательно использован, `exact` — ровно один выбор или диапазон. Не менее одного exact. |
| `#__minimarket_seo_filters.filters_json` | SeoFilterData → Seofilter HtmlView / FacetedCatalog | Только exact для базового, чистого URL; выбранные значения wildcard могут передаваться временными параметрами `mmf`. |
| Состояние публикации | Controller::save / state → SeoLandingResolver::assertUnambiguous | Публикуемые правила одинаковой специфичности не могут пересекаться; более конкретная страница имеет приоритет. |
| Canonical и robots | Category/Seofilter view → SeoHelper | Чистый URL landing индексируем согласно настройкам. Вариации wildcard с параметрами `mmf` — `noindex,follow` и canonical чистого landing. |
| Старые страницы `conditions_json IS NULL` | Site/Seofilter View | Сохраняют прежние URL и прямую фильтрацию, но не участвуют в автоматическом выборе SEO-страницы до ручного перевода. |

[RELEASE_0_6_2.md](RELEASE_0_6_2.md) — правила и ограничения; 0.6.1 checkout и все другие настройки не изменяются.

## Актуальное уточнение 0.6.1 — имеет приоритет над ограничениями 0.6.0

| Параметр | Где хранится → используется | Действие |
|---|---|---|
| `checkout_step_{buyer,delivery,payment,digital}_enabled` | Joomla component params → CheckoutSettings / CheckoutController / HtmlView | Все четыре OFF безусловно, без проверки обязательных полей/наличия методов |
| `delivery_method='pending'` | `#__minimarket_orders.delivery_method` VARCHAR → OrderStorage / Order editor | Доставка пропущена, тариф ещё не определён, `fee_cents=0` не обещает бесплатную доставку |
| `payment_method='pending'` | `#__minimarket_orders.payment_method` VARCHAR → OrderStorage / Order editor | Оплата не выбрана, `payment_status=unpaid` |
| `checkout_consent_text` | Joomla component params → Checkout HtmlView / template | Нативный Joomla HTML-editor: текст и ссылки перед кнопкой, без checkbox и без отдельного шага |
| Старое `checkout_confirmation_description` | историческое component params | Только fallback вывода в новом consent field при отсутствии нового сохранённого значения |
| `checkout_require_terms` / `checkout_terms_itemid` | историческое component params | Удалены из UI и не валидируются как обязательная галочка |
| `checkout_auth_mode=guest/guests/all` | params → CheckoutSettings | Не зависит от переключателей шагов, при `all` OTP обязателен всегда |

Регрессионный тест: `scripts/test-0-6-1.php`. См. [RELEASE_0_6_1.md](RELEASE_0_6_1.md).

## Актуальная спецификация 0.6.0 — checkout и digital

| Настройка | Место | Значения / потребитель |
|---|---|---|
| `checkout_auth_mode` | Оформление заказа | `guest`, `guests`, `all`. Режим `all` требует одноразовый код, привязанный к сессии, пользователю и заказу. Старые `email`/`sms` трактуются как `guests`. |
| `checkout_verification_method` | Раздел Регистрация (в config скрыт) | `email`, выбранный как основной, затем отдельные проверенные плагины. |
| `checkout_step_{buyer,delivery,payment,digital}_enabled` | Оформление заказа | 0/1, каждый со своим описанием. Бизнес-проверки остаются включены. |
| `checkout_digital_description` | Оформление заказа | Описание условного шага получения цифрового товара (HTML editor). |
| `checkout_confirmation_description` | Устаревший, не показывать | Удалён отдельный шаг; итог и согласия остаются в финальном блоке. |
| `product.delivery_type` | Карточка товара | `physical`/`digital`, без глобального режима магазина. |
| `product.digital_delivery_mode` | Карточка цифрового товара | `file`/`license`/`manual`/`cloud`, последний требует ручного ввода доступа до реального API. |
| `digital_recipient_email` | Заказ | Отдельный email выдачи, если не указан — email покупателя; альтернативный адрес требует OTP. |
| `#__minimarket_digital_keys` | DB (приватные коды) | Зашифрованные ключи, fingerprint, уникальность кода, резервирование по order_item во время подтверждения оплаты. |

**Реестр функциональных ограничений и приёмка:** [RELEASE_0_6_0.md](RELEASE_0_6_0.md). Прежние примеры ниже исторические; актуальные `admin/config.xml` и код сервисов имеют приоритет.

## Изменения после ревизии 0.5.99

Сохранённые значения устаревших полей не удаляются из `#__extensions.params`, но интерфейс больше не показывает их и рабочий код не использует:

- `shop_type` — замена: `product.delivery_type` (физический или цифровой), из формы отдельного продукта.
- `checkout_{buyer,delivery,payment}_skip` — замена: автоматическое решение по полноте данных Joomla, обязательным полям и количеству способов. Скрипты подтверждают правила сервером.
- `category_pagination_page_links`, `category_pagination_next`, `category_pagination_previous`, `category_load_previous` — ссылки навигации всегда включены, оставлены режим и порция товаров.
- `product_detail_show_reviews` — теперь только `reviews_enabled` во вкладке «Отзывы».
- Новые: `product_detail_gallery_effect` (lightbox/zoom), `checkout_confirmation_description`, `checkout_require_terms`, `checkout_terms_itemid`; их рабочие потребители находятся в продуктовой галерее и Checkout view/controller.
- `inventory_enabled` оставлен для физического каталога и не применяется к цифровым позициям; **не** означает складской резерв.
- Режим `checkout_auth_mode` сохраняет исторические значения guest/email; реальное SMS-подтверждение по коду всё ещё требует провайдера.
- Код больше не зависит от глобального типа магазина. Заказ хранит отдельные `payment_status`/`fulfillment_status`. Правила работы цифровой выдачи в [RELEASE_0_5_99.md](RELEASE_0_5_99.md).

**Важно:** нижеследующая историческая инвентаризация XML старых версий не является текущей спецификацией; актуальный `admin/config.xml` и таблица выше имеют приоритет. После браузерной приёмки потребуется переснять полную машинную инвентаризацию XML без старых полей.

## Связи настроек, добавленные в 0.5.98 (до браузерной приёмки)

Все параметры сохраняются в Joomla `#__extensions.params` для `com_minimarket`; дублей в БД нет. Их XML: `src/com_minimarket/admin/config.xml`; обе локализации: `admin/language/{ru-RU,en-GB}/com_minimarket.ini`.

| Параметр | Значения / умолчание | Потребитель → наблюдаемый эффект | Тест |
|---|---|---|---|
| `product_detail_layout` | `full` / `tabs`, default `full` | `View/Product/HtmlView` → `tmpl/product/default.php` → `js/product-page.js`; последовательно или tab-панели | `test-0-5-98.php`, Joomla |
| `product_detail_show_similar` | 0/1, default 0 | `ProductRecommendations::similar`, опубликованные доступные товары той же категории, кроме текущего | `test-0-5-98.php`, Joomla |
| `product_detail_show_bought_together` | 0/1, default 0 | `ProductRecommendations::boughtTogether`; реальные подтверждённые/завершённые совместные заказы, скрытые/недоступные товары отфильтрованы; нет данных → нет блока | `test-0-5-98.php`, MySQL/Browser pending |
| `product_detail_show_reviews` | 0/1, default 1 | `View/Product/HtmlView` + global `reviews_enabled`; оба условия обязательны | `test-0-5-98.php`, Joomla |
| `product_detail_sticky_bar` | 0/1, default 0 | `tmpl/product/default.php` + `js/product-page.js`; один существующий POST, SKU, CSRF и количество наследуются от исходной формы | `test-0-5-98.php`, Joomla |
| `product_detail_section_nav` | 0/1, default 0 | Якорная навигация; в режиме tabs JS переключает активную панель | `test-0-5-98.php`, Joomla |
| `checkout_{buyer,delivery,payment}_description` | HTML, default пусто | Native Joomla `editor` + `filter=safehtml` → `tmpl/checkout/default.php` с InputFilter HTML | `test-0-5-98.php`, Joomla |
| `checkout_auth_mode` | `guest` / `email` (хранимое значение для совместимости) | В интерфейсе «Обязательное подтверждение», без привязки заголовка к будущему провайдеру. Реальный SMS не активирован | `test-0-5-98.php`, Joomla |
| `mm_commands_note` + `offcanvas_*_target` | прежние значения | Прежние потребители; поля теперь **в конце** Basic | `test-0-5-98.php` |
| `filter_{price,sku,stock}` и `system_*_display_location` | прежние значения | Две настройки на атрибут без отдельного заголовка | `test-0-5-98.php` |

## Машинная инвентаризация XML (скрытые служебные поля, примечания и ACL исключены)

### Глобальные параметры

Источник `src/com_minimarket/admin/config.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `catalog_title` | `text` | `MiniMarket` | `basic` | `COM_MINIMARKET_CONFIG_CATALOG_TITLE` |
| `primary_shop_itemid` | `Shopmenu` | `0` | `basic` | `COM_MINIMARKET_CONFIG_PRIMARY_MENU` |
| `shop_type` | `list` | `physical` | `basic` | `COM_MINIMARKET_CONFIG_SHOP_TYPE` |
| `inventory_enabled` | `radio` | `1` | `basic` | `COM_MINIMARKET_CONFIG_INVENTORY_ENABLED` |
| `offcanvas_cart_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_CART` |
| `offcanvas_filters_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_FILTERS` |
| `offcanvas_account_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_ACCOUNT` |
| `offcanvas_menu_target` | `text` | `—` | `basic` | `COM_MINIMARKET_COMMANDS_MENU` |
| `filter_price_mode` | `list` | `slider` | `basic` | `COM_MINIMARKET_SYSTEM_PRICE_FILTER` |
| `filter_sku_mode` | `list` | `none` | `basic` | `COM_MINIMARKET_SYSTEM_SKU_FILTER` |
| `filter_stock_enabled` | `radio` | `0` | `basic` | `COM_MINIMARKET_SYSTEM_STOCK_FILTER` |
| `system_display_location` | `list` | `both` | `basic` | `COM_MINIMARKET_DISPLAY_SYSTEM_LOCATION` |
| `category_show_image` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_IMAGE` |
| `category_show_introtext` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_INTRO` |
| `category_show_description` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_CATEGORY_DESCRIPTION` |
| `category_show_children` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_CHILDREN` |
| `category_show_products` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_PRODUCTS` |
| `category_show_bundles` | `radio` | `1` | `display` | `COM_MINIMARKET_GLOBAL_SHOW_BUNDLES` |
| `category_show_descendant_products` | `radio` | `0` | `display` | `COM_MINIMARKET_GLOBAL_DESCENDANT_PRODUCTS` |
| `category_show_descendant_bundles` | `radio` | `0` | `display` | `COM_MINIMARKET_GLOBAL_DESCENDANT_BUNDLES` |
| `category_show_sort` | `radio` | `1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_SORT` |
| `category_lazy_loading` | `radio` | `1` | `display` | `COM_MINIMARKET_CATEGORY_LAZY_LOADING` |
| `category_children_per_page` | `list` | `10` | `display` | `COM_MINIMARKET_CHILDREN_PER_PAGE` |
| `category_product_layout` | `list` | `grid` | `display` | `COM_MINIMARKET_CATEGORY_PRODUCT_LAYOUT` |
| `category_cards_xs` | `Gridcards` | `1` | `display` | `COM_MINIMARKET_CARDS_XS` |
| `category_cards_sm` | `Gridcards` | `1` | `display` | `COM_MINIMARKET_CARDS_SM` |
| `category_cards_md` | `Gridcards` | `2` | `display` | `COM_MINIMARKET_CARDS_MD` |
| `category_cards_lg` | `Gridcards` | `3` | `display` | `COM_MINIMARKET_CARDS_LG` |
| `category_cards_xl` | `Gridcards` | `4` | `display` | `COM_MINIMARKET_CARDS_XL` |
| `category_cards_xxl` | `Gridcards` | `4` | `display` | `COM_MINIMARKET_CARDS_XXL` |
| `category_allow_layout_switch` | `radio` | `1` | `display` | `COM_MINIMARKET_CATEGORY_ALLOW_LAYOUT_SWITCH` |
| `category_products_per_page` | `list` | `10` | `display` | `COM_MINIMARKET_PRODUCTS_PER_PAGE` |
| `category_list_show_attributes` | `radio` | `1` | `display` | `COM_MINIMARKET_CATEGORY_LIST_ATTRIBUTES` |
| `category_list_show_options` | `radio` | `1` | `display` | `COM_MINIMARKET_CATEGORY_LIST_OPTIONS` |
| `category_show_add_to_cart` | `radio` | `1` | `display` | `COM_MINIMARKET_LIST_ADD_TO_CART` |
| `category_quick_view` | `radio` | `0` | `display` | `COM_MINIMARKET_LIST_QUICK_VIEW` |
| `category_product_gallery_enabled` | `radio` | `1` | `display` | `COM_MINIMARKET_DISPLAY_PRODUCT_GALLERY` |
| `category_product_gallery_limit` | `list` | `5` | `display` | `COM_MINIMARKET_DISPLAY_PRODUCT_GALLERY_LIMIT` |
| `category_show_badges` | `radio` | `1` | `display` | `COM_MINIMARKET_LIST_SHOW_BADGES` |
| `checkout_auth_mode` | `list` | `guest` | `checkout` | `COM_MINIMARKET_CONFIG_CHECKOUT_AUTH` |
| `checkout_verification_method` | `list` | `email` | `checkout` | `COM_MINIMARKET_CHECKOUT_CONFIRMATION_METHOD` |
| `checkout_name_mode` | `list` | `required` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_NAME` |
| `checkout_phone_mode` | `list` | `required` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_PHONE` |
| `checkout_email_mode` | `list` | `optional` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_EMAIL` |
| `checkout_comment_mode` | `list` | `optional` | `checkout` | `COM_MINIMARKET_CONFIG_FIELD_COMMENT` |
| `reviews_enabled` | `radio` | `0` | `reviews` | `COM_MINIMARKET_REVIEWS_ENABLED` |
| `reviews_author_mode` | `list` | `registered` | `reviews` | `COM_MINIMARKET_REVIEWS_WHO` |
| `reviews_moderation` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_MODERATE` |
| `reviews_media_photo` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_PHOTO` |
| `reviews_media_video` | `radio` | `1` | `reviews` | `COM_MINIMARKET_REVIEWS_VIDEO` |

### Категории

Источник `src/com_minimarket/admin/forms/category.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` |
| `alias` | `text` | `—` | `details` | `JFIELD_ALIAS_LABEL` |
| `parent_id` | `Categoryparent` | `0` | `details` | `COM_MINIMARKET_CATEGORY_PARENT` |
| `image` | `media` | `—` | `details` | `COM_MINIMARKET_CATEGORY_IMAGE` |
| `image_alt` | `text` | `—` | `details` | `COM_MINIMARKET_CATEGORY_IMAGE_ALT` |
| `introtext` | `editor` | `—` | `descriptions` | `COM_MINIMARKET_CATEGORY_INTRO` |
| `description` | `editor` | `—` | `descriptions` | `JGLOBAL_DESCRIPTION` |
| `show_children` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_CHILDREN` |
| `show_bundles` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_BUNDLES` |
| `show_products` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_SHOW_PRODUCTS` |
| `show_descendant_products` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_DESCENDANT_PRODUCTS` |
| `show_descendant_bundles` | `list` | `-1` | `display` | `COM_MINIMARKET_CATEGORY_DESCENDANT_BUNDLES` |
| `product_layout` | `list` | `inherit` | `display` | `COM_MINIMARKET_CATEGORY_PRODUCT_LAYOUT` |
| `cards_xs` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XS` |
| `cards_sm` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_SM` |
| `cards_md` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_MD` |
| `cards_lg` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_LG` |
| `cards_xl` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XL` |
| `cards_xxl` | `Gridcards` | `-1` | `display` | `COM_MINIMARKET_CARDS_XXL` |
| `products_per_page` | `list` | `0` | `display` | `COM_MINIMARKET_PRODUCTS_PER_PAGE` |
| `filter_price_mode` | `list` | `inherit` | `system_filters` | `COM_MINIMARKET_SYSTEM_PRICE_FILTER` |
| `filter_sku_mode` | `list` | `inherit` | `system_filters` | `COM_MINIMARKET_SYSTEM_SKU_FILTER` |
| `filter_stock_mode` | `list` | `-1` | `system_filters` | `COM_MINIMARKET_SYSTEM_STOCK_FILTER` |
| `attribute_rules` | `Categoryattributes` | `—` | `attributes` | `COM_MINIMARKET_CATATTR_LABEL` |
| `order_option_rules` | `Categoryorderoptions` | `—` | `order_options` | `COM_MINIMARKET_ORDER_OPTIONS` |
| `meta_title` | `text` | `—` | `seo` | `COM_MINIMARKET_META_TITLE` |
| `meta_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_META_DESCRIPTION` |
| `meta_robots` | `list` | `—` | `seo` | `COM_MINIMARKET_META_ROBOTS` |
| `og_title` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_TITLE` |
| `og_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_OG_DESCRIPTION` |
| `og_image` | `media` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE` |
| `og_image_alt` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE_ALT` |
| `sort_order` | `number` | `0` | `publishing` | `COM_MINIMARKET_CATEGORY_ORDER` |
| `state` | `list` | `0` | `publishing` | `JSTATUS` |

### Характеристики

Источник `src/com_minimarket/admin/forms/attribute.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` |
| `code` | `text` | `—` | `details` | `COM_MINIMARKET_ATTRIBUTE_CODE` |
| `display_location` | `list` | `inherit` | `details` | `COM_MINIMARKET_ENTITY_DISPLAY_LOCATION` |
| `input_type` | `list` | `text` | `details` | `COM_MINIMARKET_ATTRIBUTE_TYPE` |
| `value_source` | `list` | `manual` | `details` | `COM_MINIMARKET_VALUE_SOURCE` |
| `unified_values` | `subform` | `—` | `details` | `COM_MINIMARKET_UNIFIED_VALUES` |
| `color_display` | `list` | `both` | `details` | `COM_MINIMARKET_COLOR_DISPLAY` |
| `number_unit` | `text` | `—` | `details` | `COM_MINIMARKET_NUMBER_UNIT` |
| `number_min` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_MIN` |
| `number_max` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_MAX` |
| `number_step` | `number` | `—` | `details` | `COM_MINIMARKET_NUMBER_STEP` |
| `category_rules` | `Attributecategories` | `—` | `categories` | `COM_MINIMARKET_ATTRCAT_LABEL` |
| `filter_mode` | `list` | `none` | `filter` | `COM_MINIMARKET_FILTER_MODE` |
| `filter_multiple` | `radio` | `0` | `filter` | `COM_MINIMARKET_FILTER_MULTIPLE` |
| `filter_search` | `radio` | `0` | `filter` | `COM_MINIMARKET_FILTER_SEARCH_VALUES` |
| `sort_order` | `number` | `0` | `publishing` | `JGRID_HEADING_ORDERING` |
| `state` | `list` | `0` | `publishing` | `JSTATUS` |

### Опции заказа

Источник `src/com_minimarket/admin/forms/orderoption.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` |
| `display_location` | `list` | `inherit` | `details` | `COM_MINIMARKET_ENTITY_DISPLAY_LOCATION` |
| `code` | `text` | `—` | `details` | `COM_MINIMARKET_ORDER_OPTION_CODE` |
| `option_values` | `subform` | `—` | `values` | `COM_MINIMARKET_ORDER_OPTION_VALUES` |
| `category_rules` | `Optioncategories` | `—` | `categories` | `COM_MINIMARKET_ORDER_OPTION_CATEGORIES` |
| `state` | `list` | `1` | `publishing` | `JSTATUS` |
| `sort_order` | `number` | `0` | `publishing` | `JGRID_HEADING_ORDERING` |

### Товары и комплекты

Источник `src/com_minimarket/admin/forms/product.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `title` | `text` | `—` | `details` | `JGLOBAL_TITLE` |
| `alias` | `text` | `—` | `details` | `JFIELD_ALIAS_LABEL` |
| `category_id` | `Productcategory` | `—` | `details` | `COM_MINIMARKET_PRODUCT_CATEGORY` |
| `sku` | `text` | `—` | `details` | `COM_MINIMARKET_PRODUCT_SKU` |
| `price` | `number` | `0.00` | `details` | `COM_MINIMARKET_PRODUCT_PRICE` |
| `old_price` | `number` | `—` | `details` | `COM_MINIMARKET_BADGE_OLD_PRICE` |
| `shipping_weight_grams` | `number` | `0` | `details` | `COM_MINIMARKET_SHIPPING_WEIGHT_GRAMS` |
| `shipping_volume_cm3` | `number` | `0` | `details` | `COM_MINIMARKET_SHIPPING_VOLUME_CM3` |
| `stock` | `number` | `0` | `details` | `COM_MINIMARKET_PRODUCT_STOCK` |
| `option_stock_mode` | `radio` | `1` | `details` | `COM_MINIMARKET_OPTION_STOCK_MODE` |
| `bundle_price_mode` | `list` | `fixed` | `bundle_pricing` | `COM_MINIMARKET_BUNDLE_PRICE_MODE` |
| `bundle_discount_pct` | `number` | `0.00` | `bundle_pricing` | `COM_MINIMARKET_BUNDLE_DISCOUNT_PCT` |
| `badge_sale` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_SALE_ENABLE` |
| `badge_sale_display` | `list` | `percent` | `badges` | `COM_MINIMARKET_BADGE_DISPLAY` |
| `badge_new` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_NEW_ENABLE` |
| `badge_new_days` | `number` | `—` | `badges` | `COM_MINIMARKET_BADGE_DAYS` |
| `badge_popular` | `checkbox` | `0` | `badges` | `COM_MINIMARKET_BADGE_POPULAR_ENABLE` |
| `badge_popular_days` | `number` | `—` | `badges` | `COM_MINIMARKET_BADGE_DAYS` |
| `badge_benefit` | `checkbox` | `0` | `bundle_benefit` | `COM_MINIMARKET_BADGE_BENEFIT_ENABLE` |
| `badge_benefit_display` | `list` | `money` | `bundle_benefit` | `COM_MINIMARKET_BADGE_DISPLAY` |
| `brand_id` | `Brandpicker` | `0` | `merchandising` | `COM_MINIMARKET_PRODUCT_BRAND` |
| `collection_ids` | `Collectionpicker` | `—` | `merchandising` | `COM_MINIMARKET_PRODUCT_COLLECTIONS` |
| `introtext` | `editor` | `—` | `descriptions` | `COM_MINIMARKET_CATEGORY_INTRO` |
| `description` | `editor` | `—` | `descriptions` | `JGLOBAL_DESCRIPTION` |
| `attribute_values` | `Productattributes` | `—` | `attributes` | `COM_MINIMARKET_PRODUCT_TAB_ATTRIBUTES` |
| `image` | `media` | `—` | `images` | `COM_MINIMARKET_PRODUCT_IMAGE` |
| `image_alt` | `text` | `—` | `images` | `COM_MINIMARKET_CATEGORY_IMAGE_ALT` |
| `gallery` | `subform` | `—` | `images` | `COM_MINIMARKET_GALLERY_ADDITIONAL` |
| `promo_video` | `subform` | `—` | `videos` | `COM_MINIMARKET_PROMO_VIDEO_SECTION` |
| `videos` | `subform` | `—` | `videos` | `COM_MINIMARKET_PRODUCT_TAB_VIDEOS` |
| `category_option_preview` | `Productcategoryoptions` | `—` | `variants` | `COM_MINIMARKET_PRODUCT_OPTIONS_FROM_CATEGORY` |
| `variant_dimensions` | `subform` | `—` | `variants` | `COM_MINIMARKET_LOCAL_VARIANTS_TITLE` |
| `variant_matrix` | `Variantbuilder` | `—` | `variants` | `COM_MINIMARKET_LOCAL_PREVIEW` |
| `sku_matrix_payload` | `Skumatrix` | `—` | `variants` | `COM_MINIMARKET_MATRIX_TITLE` |
| `variants` | `subform` | `—` | `variants` | `COM_MINIMARKET_PRODUCT_TAB_VARIANTS` |
| `bundle_items` | `Bundlepicker` | `—` | `bundle` | `COM_MINIMARKET_BUNDLE_COMPONENTS` |
| `group_editor` | `Groupeditor` | `—` | `groups` | `COM_MINIMARKET_PRODUCT_TAB_GROUPS` |
| `meta_title` | `text` | `—` | `seo` | `COM_MINIMARKET_META_TITLE` |
| `meta_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_META_DESCRIPTION` |
| `meta_robots` | `list` | `—` | `seo` | `COM_MINIMARKET_META_ROBOTS` |
| `og_title` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_TITLE` |
| `og_description` | `textarea` | `—` | `seo` | `COM_MINIMARKET_OG_DESCRIPTION` |
| `og_image` | `media` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE` |
| `og_image_alt` | `text` | `—` | `seo` | `COM_MINIMARKET_OG_IMAGE_ALT` |
| `sort_order` | `number` | `0` | `publishing` | `COM_MINIMARKET_CATEGORY_ORDER` |
| `state` | `list` | `0` | `publishing` | `JSTATUS` |

### Панель администратора

Источник `src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml`.

| Параметр | Контрол | Default | Вкладка | Локализация |
|---|---|---|---|---|
| `enabled` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_ENABLED` |
| `label` | `text` | `MiniMarket` | `basic` | `MOD_MINIMARKET_TOOLBAR_LABEL` |
| `show_icon` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_SHOW_ICON` |
| `show_label` | `radio` | `1` | `basic` | `MOD_MINIMARKET_TOOLBAR_SHOW_LABEL` |

## 0.5.93 — флаг видимости связи в карточке товара категории

Это **не глобальный параметр компонента**, а отдельное свойство двух существующих связей. `#__minimarket_category_attributes.show_in_list` и `#__minimarket_category_order_options.show_in_list` (оба `TINYINT UNSIGNED DEFAULT 1`). UI в вкладках «Характеристики»/«Опции» редактора категории, рядом с режимом. Влияет только на карточку товара внутри списка категории; полная карточка, фасеты, SKU/корзина не изменяются. Наследуется через иерархию категорий; прямое включение/обязательность задаёт override. Отключенные связи не появляются в листинге независимо от флага. Перезапись из редактора характеристики/опции сохраняет старый флаг. Регрессионные тесты + реальная Joomla обязательны.

## 0.5.96 — системные атрибуты и способы как источник истины

Цена: `filter_price_mode` + `system_price_display_location`; Артикул: `filter_sku_mode` + `system_sku_display_location`; Наличие: `filter_stock_enabled` + `system_stock_display_location`. Каждая пара оформлена отдельно в `admin/config.xml`, но сохраняет прежние ключи и значения, уже читаемые `FacetedCatalog`, `CategoryDisplayOptions` и шаблонами. Дополнительные характеристики/опции имеют `display_location` на своей сущности; умолчание — полная карточка товара. Права/статус плагинов в `#__extensions.enabled`, параметры из их штатного XML в `#__extensions.params`; изменения возможны из карточки способа, без отдельной БД. Email подтверждение оформляется через родной `com_users`; не заявлять OTP как готовый механизм. SEO-страницы сейчас привязаны к одной категории; выбор значений защищён `SeoCategoryChoices`.
