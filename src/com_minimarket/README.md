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
