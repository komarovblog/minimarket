# CURRENT_TASK — MiniMarket

**Дата:** 2026-11-01. Рабочая ветка `development`, `main` не изменять. Joomla core, данные товаров, локальный `.osp/project.ini` и другой проект Shop не менять.

## Согласованное окончательное решение по выбору пункта меню

Пользователь отказался от автоотключения флагов в других пунктах и от служебного Joomla-плагина. **Одна глобальная настройка в самом `com_minimarket`: «Основной пункт меню для ссылок».** Значения: «Автоматически» (MiniMarket на главной, иначе пункт с минимальным ID) либо один из опубликованных обычных пунктов `MiniMarket → Магазин`. Выбранный пункт используется для генерации category/product ссылок и canonical; каждая страница `/`, `/catalog`, `/shop` остаётся самостоятельной. Пунктов магазина — неограниченно, никаких запретов на SEO-дубли. Если выбранный пункт удалён или не опубликован — fallback «Автоматически».

**Устаревшие настройки per-menu `shop_primary_route` из старых версий больше не используются и не отображаются.** Их сохранённые данные в параметрах Joomla не стираются автоматически; выбирается значение в общих настройках. Ни чужие menu items, ни SQL-схема не меняются.

## Сейчас в GitHub: com_minimarket 0.3.10 — готов к Joomla smoke

- В `admin/config.xml` новое поле `primary_shop_itemid` типа `Shopmenu`, с одним выбором; `admin/src/Field/ShopmenuField.php` получает из БД опубликованные ordinary Joomla `MiniMarket → Магазин` menu items и исключает Menu Item Alias/другие компоненты; для понятности показывает название, путь, ID.
- `site/src/Service/Router.php` использует `ComponentHelper::getParams('com_minimarket')->get('primary_shop_itemid', 0)` и сверяет ID с реально доступными опубликованными Shop Menu Items. Когда ID отсутствует/не подходит — home или наименьший ID. **Явный Itemid для страницы каталога** сохраняется; продукт и категория всегда формируют ссылки через один выбранный пункт. Исторический per-menu flag игнорируется.
- Сохранена функциональность ранее установленной и проверенной пользователем 0.3.8: категории, общая выдача товаров, независимые настройки отображения пунктов меню, сортировка, пагинация и typed/legacy URL.
- Из GitHub `development` **удалены** `src/plg_content_minimarketmenu`, `src/pkg_minimarket`, `scripts/build-package.ps1`, `scripts/inspect-package-bundle.ps1`, `scripts/test-menu-sync.php`. Ранее планировавшаяся 0.3.9 **ОТМЕНЕНА** — `pkg_minimarket.zip` **НЕ устанавливать**; плагин НЕ нужен. Нужен только `dist/com_minimarket.zip`, собрать через `scripts/build.ps1`.
- Регрессионные `scripts/test-router.php` (глобальный выбор, fallback, legacy flag ignored), `scripts/test-shopmenu.php` (опции, фильтрация, контекст menu items), `scripts/test-catalog.php`. GitHub CI запускает PHP 8.4/8.5 lint/tests и Windows build/inspect; **Joomla 0.3.10 runtime ещё NOT RUN**.
- Порядок изменения: только `development`, не удалять Joomla компонент или меню; не делать прямых SQL корректировок реальной БД. Joomla plugin из прежней 0.3.9 не устанавливался пользователем согласно имеющейся истории; если был — отдельно отключить/удалить через Joomla Extension Manager, не автоматически из исходников.

## Следующий пользовательский шаг

`docs/M1K_GLOBAL_MENU_SELECTOR_SMOKE.md` — единая проверка: `git pull --ff-only origin development`, выполнить 3 PHP теста, `build.ps1`, `inspect-package.ps1`. Ожидаемый `PACKAGE_OK: 0.3.10`. Один штатный upgrade `dist/com_minimarket.zip` поверх текущей работающей Joomla 0.3.8; не ставить старый `pkg_minimarket.zip`. В **Компоненты → MiniMarket → Настройки** выбрать «Автоматически»/«Каталог»/«Главная», проверить изменение ссылок товара/категории и canonical; все обычные пункты MiniMarket остаются доступны. `TEST-001` и админские Save не ломаются.

Следующий крупный функциональный срез только после пользовательского PASS: модель характеристик/вариантов товара по `docs/PRODUCT_VARIANTS.md`, затем фото по цвету; не смешивать с обновлением роутера/настроек.
