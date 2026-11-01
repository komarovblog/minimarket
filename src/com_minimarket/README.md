> **01.11.2026 — ОКОНЧАТЕЛЬНЫЙ выбор пользователя, 0.3.10:** пользователь отменил auto-disable при сохранении меню. **Никакого Joomla плагина и пакета**: их исходники (`plg_content_minimarketmenu`, `pkg_minimarket`), package build/test удалены из GitHub `development`. Новый **один** параметр в MiniMarket → Options: `primary_shop_itemid` / «Основной пункт меню для ссылок», `Автоматически` либо один опубликованный обычный пункт `MiniMarket → Магазин`. Custom Joomla form field `admin/src/Field/ShopmenuField.php`; все обычные storefront menu items остаются доступными. Router использует глобальную настройку и при удалённом/непубликованном выборе безопасно возвращается к Shop homepage → min ID. Старый per-menu `shop_primary_route` снят с формы и игнорируется. **Joomla runtime 0.3.10 NOT RUN**, PHP/Windows CI отдельно; сборка **ТОЛЬКО** `scripts/build.ps1`, устанавливать **ТОЛЬКО `dist/com_minimarket.zip`**, не старый `pkg_minimarket.zip`. Полный smoke `../../docs/M1K_GLOBAL_MENU_SELECTOR_SMOKE.md`, главный контекст `../../CURRENT_TASK.md`. `main`, Joomla core/SQL/.osp не трогать; никаких скрытых пунктов.

> **01.11.2026 — 0.3.9 РАЗРАБОТАНО, Joomla runtime NOT RUN:** пользователь выбрал per-menu флажок `shop_primary_route` с автоматическим сбросом в остальных пунктах при сохранении `Да`. Реализован небольшой **content-плагин Joomla 6**, подписанный на `onContentAfterSave` контекста `com_menus.item`, изменяет только JSON `shop_primary_route` у других MiniMarket shop items. Не трогает alias, прочие параметры, другую Joomla. **Один ZIP `pkg_minimarket.zip`** = прежний компонент + плагин; после первой установки плагин **нужно один раз включить** через Joomla System → Plugins. Стандартный `com_minimarket.zip` не содержит плагина. 0.3.9 в GitHub `development`, PHP 8.4/8.5 + тесты + Windows ZIP CI PASS: https://github.com/komarovblog/minimarket/actions/runs/41291137771 , но **Joomla NOT RUN**. Следующий единственный шаг: `../../docs/M1J_PRIMARY_MENU_SYNC_SMOKE.md`, строить `scripts/build-package.ps1` и проверять `scripts/inspect-package-bundle.ps1`, устанавливать **только пакет**, не удалять старую компоненту. Варианты товаров ещё DESIGN ONLY; никаких SQL-модификаций каталога или core.

> **01.11.2026 — MiniMarket 0.3.8 в GitHub development, Joomla runtime NOT RUN:** единый консолидированный выпуск включает 0.3.7 (любое число обычных MiniMarket menu items; один выбранный Itemid для ссылок на категории/товары, self-canonical независимых витрин) и новый опциональный **список всех товаров на странице «Магазин»**. `show_products` = Нет по умолчанию, поэтому прежние страницы не меняются; при Да доступны 12/24/48 и 4 сортировки, SQL LIMIT/OFFSET и публикация обоих объектов. Пагинация `?mm_page=N` сохраняет конкретный Shop Itemid; canonical страниц учитывает страницу. Новые `scripts/test-catalog.php` + `scripts/test-router.php`, оба PHP gate запускать до установки. **0.3.7 отдельно обновлять не нужно**. Следующая задача: один smoke 0.3.8 по `../../docs/M1I_CATALOG_ASSORTMENT_SMOKE.md`, затем SEO и варианты товаров. Никаких SQL/ядра/.osp/скрытого меню; `../../CURRENT_TASK.md` — контрольная точка.

> **0.3.7 (01.11.2026):** пользовательский FAIL 0.3.6 при двух одновременно опубликованных MiniMarket пунктах — `canonical URL is not absolute`. Исправление: несколько открытых menu items допускаются, каталоговый Itemid сохраняется для каждой страницы, категория/товар используют один основной (параметр `shop_primary_route` → Home MiniMarket → минимальный ID). `SeoHelper` делает каталог self canonical, товары/категории primary canonical. Изменения только Router, SeoHelper, XML пункта и admin translations, тесты. **Joomla runtime NOT RUN**. [M1G_MULTIPLE_SHOP_MENU_SMOKE.md](../../docs/M1G_MULTIPLE_SHOP_MENU_SMOKE.md).

> **0.3.6 (01.11.2026):** 0.3.5 frontend FAIL (ссылка меню «Каталог» возвращает главную). Исправлен `Router::getShopItems()`: Joomla SiteMenu уже возвращает только опубликованные пункты, `MenuItem` не содержит поле `published`, так что проверка `?? 0` была ошибкой. Добавлен `scripts/test-router.php` и [M1F_ROUTER_REGRESSION.md](../../docs/M1F_ROUTER_REGRESSION.md) для единого smoke. **Joomla runtime 0.3.6 ещё NOT RUN**; не переименовывать меню до PASS. Старые URL пока парсятся, 301 ещё нет.

> **0.3.5 (01.11.2026):** компонентный Router теперь формирует типизированные маршруты `category/{alias}-{id}` и `product/{alias}-{id}` после обычного Shop Menu Item; скрытых пунктов нет. Legacy `catalog/{alias}-{id}` временно распознаётся, canonical новый typed, 301 ещё НЕ готов. PHP 8.4 lint и isolated mock scenarios PASS, Joomla runtime NOT RUN. Тестировать на существующем меню `/catalog` по [M1E_TYPED_ROUTING_SMOKE.md](../../docs/M1E_TYPED_ROUTING_SMOKE.md), не переименовывать до PASS.

> **Приоритетный контракт 01.11.2026:** один обычный открыто выбранный пункт Joomla `MiniMarket — Магазин` — главная `/` либо раздел `/shop`. Никаких скрытых menu items/Itemid или автосоздания меню. Для отдельного ИМ `/category/alias-id`, `/product/alias-id`, для раздела `/shop/category/alias-id`, `/shop/product/alias-id`, далее bundle/filter. Блоки (категории/товары/фильтры, сортировка/пагинация) настраиваются в пункте; отдельные модули можно разместить где угодно. **Это DESIGN_ONLY, не реализовано в версии 0.3.3**. Полная хронология: [PROJECT_PROGRESS.md](../../docs/PROJECT_PROGRESS.md).

> **0.3.3 (01.11.2026):** в административных `com_minimarket.sys.ini` (en-GB/ru-RU) добавлены подписи для `site/tmpl/catalog/default.xml`, чтобы в выборе типа пункта меню Joomla отображались названия вместо `COM_MINIMARKET_CATALOG_MENU_TITLE/DESCRIPTION`. БД и роутер не менялись. Windows/Joomla smoke **NOT RUN**. [CURRENT_TASK.md](../../CURRENT_TASK.md).

> **0.3.2 (31.10.2026):** подключён штатный JS `form.validate` для Save/Apply в административных формах категорий и товаров. Исправление создано после реального FAIL Save на Joomla 6.2.0, но Windows и Joomla smoke новой версии ещё не выполнен. SQL-миграций нет. См. [CURRENT_TASK.md](../../CURRENT_TASK.md).

> **0.3.1:** канонические ссылки для каталога, категорий и товаров через официальный Joomla Router; без SQL миграций/301. В GitHub опубликовано, локальный smoke ещё не подтверждён. [Тест SEO](../../docs/M1D_CANONICAL_SMOKE.md).

> **0.3.0 M1c:** реальный frontend каталог, категория и карточка товара с одним `alias-ID` сегментом через штатный Joomla RouterService. Нет новой SQL-миграции; добавлены router и публичные MVC views. Установка/HTTP-проверки пользователя ещё ожидаются. [Чек-лист M1c](../../docs/M1C_FRONTEND_ROUTING_SMOKE.md).

> **0.2.1 M1b:** добавлен создаваемый/редактируемый товар через штатную Joomla-форму. Таблицы категорий/товаров наследуются из 0.2.0. Реальная проверка обновления пока не выполнена. Инструкция: [M1b smoke](../../docs/M1B_PRODUCT_EDITOR_SMOKE.md).

> **Актуально 31.10.2026:** исходники `com_minimarket 0.2.0` (M1a) готовы в GitHub и ждут проверки обновления на локальной Joomla. [Чек-лист](../../docs/M1A_CATEGORIES_SMOKE.md).

# MiniMarket / com_minimarket — M0

Этот каталог — **исходники отдельного компонента Joomla 6**, а не содержимое установленной CMS. В M1a уже есть SQL-таблицы категорий и товаров, CRUD категорий, но нет редактора товаров, заказов или интеграции с материалами Joomla. Версия манифеста: 0.2.0.

## Состав

- \`minimarket.xml\` — установочный манифест Joomla.
- \`admin/\` — административная страница, настройки и переводы.
- \`site/\` — представление каталога-заглушки и тип пункта меню Joomla.
- \`scripts/build.ps1\` в корне репозитория создаёт готовый ZIP.

## Сборка в Windows PowerShell

Из \`C:\\OSPanel\\home\\minimarket.local\`:

\`\`\`powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
\`\`\`

Архив: \`dist/com_minimarket.zip\`. Сборка не трогает файлы ядра Joomla или БД. \`dist/\` игнорируется Git.

## Установка

Joomla → Система → Установка → Расширения → Загрузка файла пакета → \`dist/com_minimarket.zip\`.

Проверка:
- Компоненты → MiniMarket — видна админ-страница.
- В её панели «Настройки» можно сохранить заголовок каталога.
- \`http://minimarket.local/index.php?option=com_minimarket\` — публичная страница с установленным заголовком.
- На сайте нет созданных товаров/категорий и не меняются Joomla Articles.

**Реальное обновление до версии 0.2.0 на Joomla пока не проверено** — это следующий локальный smoke-test после публикации кода.
