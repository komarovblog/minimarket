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
