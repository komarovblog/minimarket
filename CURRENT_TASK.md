# CURRENT_TASK — MiniMarket

**Дата:** 2026-11-01. Рабочая ветка `development` в `komarovblog/minimarket`; **не изменять `main`**. Не менять Joomla core, пользовательский `.osp/project.ini`, соседний Shop и существующие строки товаров.

## Подтверждение пользователя по маршрутизации/Options

В установленной Joomla 6.2 пользователь подтвердил, что **стандартная кнопка Options в MiniMarket появилась**, настройка **«Основной пункт меню для ссылок»** корректно выбирает каталог/главную и ссылки формируются правильно. Пользователь завершил этот smoke сообщением **«Все правильно»**. Не требовать повторной установки или тестирования 0.3.11 только из-за локализации: переводы `COM_MINIMARKET_CONFIGURATION` опубликованы в `development` и включены в следующий ZIP.

Окончательная архитектура: **одна глобальная настройка `primary_shop_itemid` в Options компонента**, «Автоматически» → MiniMarket home → min ID или выбранный опубликованный ordinary Shop menu item. Никаких per-menu флагов, автоматического выключения флагов, служебного плагина, пакета или скрытых пунктов меню. Пунктов MiniMarket может быть сколько угодно; они не ограничены одним URL.

## Текущий код: MiniMarket 0.4.0 — справочники характеристик, Joomla runtime NOT RUN

**Первый реальный этап вариантов без изменения существующих товаров.** Добавлены две новые таблицы:
- `#__minimarket_attributes`: ID, title, уникальный латинский code, `input_type` = `list/text/number/boolean`, `is_variant` (только для list), `is_filterable` (пока только флажок будущих фильтров), порядок, публикация.
- `#__minimarket_attribute_values`: ID, `attribute_id` (FK), title, code уникальный в пределах характеристик, порядок, публикация.

Есть Joomla MVC CRUD для `Attribute`/`Value`, списки `Attributes`/`Values`, формы `admin/forms/attribute.xml` и `value.xml`, пункт «Характеристики» в manifest и ссылка с Dashboard (если Joomla не обновит левое подменю при upgrade). Все операции сохранения используют штатную Joomla CSRF-проверку через FormController и штатную таблицу; ручного SQL для управления данными администратору не нужно. Валидация: без дублей, list-only для variant, нельзя менять тип list при наличии словарных значений, уникальные индексы/FK в БД.

**0.4.0 upgrade SQL только CREATE TABLE IF NOT EXISTS** (`admin/sql/updates/mysql/0.4.0.sql`); для чистой установки аналогичные CREATE в `install.mysql.sql`. **Не создаёт новых SKU/вариантов, не меняет `#__minimarket_products` или `#__minimarket_categories`, не переносит цену/остаток/URL, не изменяет Joomla core.** Состояние товара `TEST-001` по последнему скриншоту: SKU `TEST-001`, цена `1990.50`, остаток `3`. Проверить, что всё остаётся как перед установкой.

Новые регрессионные проверки `scripts/test-attributes.php` (без Joomla и БД), `scripts/test-attribute-schema.php` (**ТОЛЬКО disposable MySQL CI**, защита по env и имени базы). GitHub Actions PHP 8.4/8.5 lint и тесты; Windows ZIP build + inspect; отдельный MySQL 8 upgrade/fresh-install test проверяет сохранность строки TEST-001 и FK/уникальность. Настоящий Joomla runtime **ещё НЕ подтверждён**. В GitHub CI найдено и исправлено столкновение названий FK при одновременном тесте двух префиксов в одной временной базе; тест теперь очищает только свои тестовые таблицы между сценариями.

## Единственный пользовательский шаг — один smoke 0.4.0

**Полная инструкция:** `docs/M2A_ATTRIBUTES_SMOKE.md`. Перед установкой создать резервную копию БД; в CMD `C:\OSPanel\home\minimarket.local` выполнить `git status --short`, `git pull --ff-only origin development`, PHP `scripts/test-attributes.php`, прежние тесты Router/Catalog, `scripts/build.ps1`, `scripts/inspect-package.ps1`; ожидать `PACKAGE_OK: 0.4.0`. При FAIL НЕ устанавливать.

Затем один стандартный upgrade **только `dist/com_minimarket.zip`** поверх действующей версии, компонент/данные не удалять. В MiniMarket → «Характеристики» создать списки `color` (Красный/red, Жёлтый/yellow), `size` (S/s, M/m, L/l); проверить редактирование, сохранение, дубли и запрет смены типа со значениями, а также регрессию товара/URL/Options. Не создавать фиктивные товары и не менять товарные цены/остатки.

## После подтверждения PASS

**Следующий этап — реальная связь со SKU/вариантами**, с одним URL товара. Перед первым SQL переносом из старого `sku/price/stock` обязательны тестовая миграция, rollback plan и гарантия одного авторитетного источника цены/остатка; `docs/PRODUCT_VARIANTS.md`. Затем фотографии общие/по значению цвета/по конкретному SKU. Фильтры и корзина — отдельно. Не объявлять готовыми раньше кода и реального Joomla smoke.
