> **01.11.2026 — ОКОНЧАТЕЛЬНЫЙ выбор пользователя, 0.3.10:** пользователь отменил auto-disable при сохранении меню. **Никакого Joomla плагина и пакета**: их исходники (`plg_content_minimarketmenu`, `pkg_minimarket`), package build/test удалены из GitHub `development`. Новый **один** параметр в MiniMarket → Options: `primary_shop_itemid` / «Основной пункт меню для ссылок», `Автоматически` либо один опубликованный обычный пункт `MiniMarket → Магазин`. Custom Joomla form field `admin/src/Field/ShopmenuField.php`; все обычные storefront menu items остаются доступными. Router использует глобальную настройку и при удалённом/непубликованном выборе безопасно возвращается к Shop homepage → min ID. Старый per-menu `shop_primary_route` снят с формы и игнорируется. **Joomla runtime 0.3.10 NOT RUN**, PHP/Windows CI отдельно; сборка **ТОЛЬКО** `scripts/build.ps1`, устанавливать **ТОЛЬКО `dist/com_minimarket.zip`**, не старый `pkg_minimarket.zip`. Полный smoke `../docs/M1K_GLOBAL_MENU_SELECTOR_SMOKE.md`, главный контекст `../CURRENT_TASK.md`. `main`, Joomla core/SQL/.osp не трогать; никаких скрытых пунктов.

> **01.11.2026 — 0.3.9 РАЗРАБОТАНО, Joomla runtime NOT RUN:** пользователь выбрал per-menu флажок `shop_primary_route` с автоматическим сбросом в остальных пунктах при сохранении `Да`. Реализован небольшой **content-плагин Joomla 6**, подписанный на `onContentAfterSave` контекста `com_menus.item`, изменяет только JSON `shop_primary_route` у других MiniMarket shop items. Не трогает alias, прочие параметры, другую Joomla. **Один ZIP `pkg_minimarket.zip`** = прежний компонент + плагин; после первой установки плагин **нужно один раз включить** через Joomla System → Plugins. Стандартный `com_minimarket.zip` не содержит плагина. 0.3.9 в GitHub `development`, PHP 8.4/8.5 + тесты + Windows ZIP CI PASS: https://github.com/komarovblog/minimarket/actions/runs/41291137771 , но **Joomla NOT RUN**. Следующий единственный шаг: `../docs/M1J_PRIMARY_MENU_SYNC_SMOKE.md`, строить `scripts/build-package.ps1` и проверять `scripts/inspect-package-bundle.ps1`, устанавливать **только пакет**, не удалять старую компоненту. Варианты товаров ещё DESIGN ONLY; никаких SQL-модификаций каталога или core.

> **Важное изменение сборки с 0.3.9:** обычный `scripts/build.ps1` собирает только `dist/com_minimarket.zip`. Для пользовательской установки **компонента и плагина одним действием** выполнять `scripts/build-package.ps1` → `dist/pkg_minimarket.zip`, затем `scripts/inspect-package-bundle.ps1`. Прежний `inspect-package.ps1` проверяет вложенный компонентный ZIP отдельно; оба инспектора обязательны. Плагин активировать после первой установки пакетного ZIP.

> **01.11.2026 — MiniMarket 0.3.8 в GitHub development, Joomla runtime NOT RUN:** единый консолидированный выпуск включает 0.3.7 (любое число обычных MiniMarket menu items; один выбранный Itemid для ссылок на категории/товары, self-canonical независимых витрин) и новый опциональный **список всех товаров на странице «Магазин»**. `show_products` = Нет по умолчанию, поэтому прежние страницы не меняются; при Да доступны 12/24/48 и 4 сортировки, SQL LIMIT/OFFSET и публикация обоих объектов. Пагинация `?mm_page=N` сохраняет конкретный Shop Itemid; canonical страниц учитывает страницу. Новые `scripts/test-catalog.php` + `scripts/test-router.php`, оба PHP gate запускать до установки. **0.3.7 отдельно обновлять не нужно**. Следующая задача: один smoke 0.3.8 по `../docs/M1I_CATALOG_ASSORTMENT_SMOKE.md`, затем SEO и варианты товаров. Никаких SQL/ядра/.osp/скрытого меню; `../CURRENT_TASK.md` — контрольная точка.

> **0.3.6 — новый обязательный regression gate перед Joomla upgrade:** `php scripts/test-router.php` (или `C:\OSPanel\modules\PHP-8.5\php.exe` из CMD). Изолированный тест использует реальный `Router.php`, имитацию Joomla 6 SiteMenu/MenuItem **без свойства `published`** и mock DB; ему не нужны Joomla, MySQL или Docker. При `ROUTER_TEST_FAIL` не устанавливать ZIP. Только после `ROUTER_TEST_OK` и `PACKAGE_OK: 0.3.6` выполнить **один** upgrade; [M1F_ROUTER_REGRESSION.md](../docs/M1F_ROUTER_REGRESSION.md).

# Скрипты MiniMarket

Рабочий скрипт: \`build.ps1\` — из собственных исходников \`src/com_minimarket\` собирает архив \`dist/com_minimarket.zip\` для штатного менеджера расширений Joomla 6.

На Windows из корня репозитория:

\`\`\`powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
\`\`\`

Скрипт не требует Docker и не изменяет Joomla Core, .env, конфигурацию или БД. Результат и локальные временные файлы не коммитятся. Если установка компонента не прошла, нужны экранная ошибка Joomla и отчёт проверки, а не принудительная очистка сайта.


После M1a (0.2.0) builder также проверяет наличие SQL-файлов install/update и формы категорий. Перед обновлением локальной Joomla с SQL-миграцией рекомендуется экспортировать отдельную БД `minimarket` в приватный локальный архив. Инструкция: [M1a smoke](../docs/M1A_CATEGORIES_SMOKE.md).


В 0.2.1 добавлена проверка product.xml и MVC файлов редактора товаров. SQL обновлений не менялся, каталог и товары используют уже созданные таблицы из 0.2.0. Проверка: [M1b smoke](../docs/M1B_PRODUCT_EDITOR_SMOKE.md).


Версия 0.3.0 добавляет site-router, helper, repository и публичные представления категорий/товаров. `build.ps1` проверяет их наличие при сборке. [Чек-лист M1c](../docs/M1C_FRONTEND_ROUTING_SMOKE.md).


## Диагностика ZIP при ошибке Joomla
Запустить `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1`. Проверяется **существующий** `dist/com_minimarket.zip`: верхний `minimarket.xml` (`<extension type="component">`), версия и обязательные файлы. Выдаёт один короткий `PACKAGE_OK` или `PACKAGE_FAIL`. Это не проверка установки Joomla. Подробности: [docs/INSTALL_ZIP_DIAGNOSTIC.md](../docs/INSTALL_ZIP_DIAGNOSTIC.md).


**31.10.2026:** ZIP теперь записывается через .NET `ZipFileExtensions.CreateEntryFromFile` с внутренними путями `/`, вместо платформозависимого `Compress-Archive`. `inspect-package.ps1` возвращает `PACKAGE_FAIL: NONPORTABLE_ENTRY_PATHS` или конкретное имя отсутствующего файла. Результат на Windows пользователя пока ожидается.
