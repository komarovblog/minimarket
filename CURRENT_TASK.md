# CURRENT_TASK — MiniMarket

**Дата:** 2026-11-01. Репозиторий `komarovblog/minimarket`, ветка `development`. `main` не изменять. Не менять ядро Joomla, данные пользователей/магазина, `.osp/project.ini` и соседний Shop.

## Реальный отзыв пользователя по 0.3.10

Пользователь сообщил, что после установки 0.3.10 **в меню MiniMarket видны только «Категории» и «Товары» и новая настройка не обнаружена**. Причина: `primary_shop_itemid` находится в стандартных Joomla Component Options (`com_config`), а не является отдельным подразделом `com_minimarket`. Уже в исходниках 0.3.10 `Dashboard/HtmlView.php` имел `ToolbarHelper::preferences('com_minimarket')`, но только при `core.admin`, а экран списков Categories/Products вообще не показывал кнопку. Это проблема доступности настроек, а не подтверждённый отказ поля `ShopmenuField`.

**Рабочий прямой путь уже в установленном 0.3.10:** `http://minimarket.local/administrator/index.php?option=com_config&view=component&component=com_minimarket`. Для открытия нужна роль с `core.admin` или `core.options` на `com_minimarket`. Если по прямой ссылке появляется ошибка самого поля, тестировать отдельно, не утверждать заранее PASS.

## Исправление в исходниках: MiniMarket 0.3.11 — Joomla RUNTIME NOT RUN

- **Один компонент**, никаких плагинов, скрытых menu items или пакетов. Глобальный `primary_shop_itemid` сохраняет прежнюю согласованную логику: «Автоматически» → MiniMarket на главной / min опубликованный ID, либо один опубликованный обычный MiniMarket menu item. Независимые пункты `/`, `/catalog`, `/shop` остаются.
- В стандартной панели инструментов Dashboard, Categories, Products теперь показывается Joomla `ToolbarHelper::preferences('com_minimarket')` при `core.admin` **или** `core.options`. В Dashboard шаблоне также явная кнопка «Настройки MiniMarket» (с проверкой тех же разрешений), ведущая в `com_config` без дополнительного самописного MVC view.
- **Не добавляем новый пункт в административное подменю** для существующих установок: Joomla installer не гарантирует добавления нового подменю без полной перестройки, а перестраивать меню в работающей базе недопустимо. Слева по-прежнему могут отображаться только «Категории» и «Товары» — это нормально; настройки открываются сверху кнопкой «Настройки / Options».
- `scripts/test-admin-options.php` — независимые mock-проверки прав доступа к стандартной кнопке на 3 экранах + наличие видимой ссылки Dashboard и поля Options; включён в GitHub CI вместе с прежним `test-router.php`, `test-catalog.php`, `test-shopmenu.php`.
- Манифест `0.3.11`. Нет миграций DB или изменения данных. Только `dist/com_minimarket.zip`; сборка `scripts/build.ps1` и инспекция `scripts/inspect-package.ps1`.
- **Joomla 0.3.11 НЕ ПРОВЕРЕНА**; убедиться в наличии кнопок после обновления. Проверки CI — отдельно записать после статуса GitHub Actions.

## CI версии 0.3.11 — PASS

GitHub Actions по коммиту `63ee5807`: [run 41300073360](https://github.com/komarovblog/minimarket/actions/runs/41300073360), **PHP 8.4 и 8.5 lint / Router / Catalog / Shop Menu selector / Admin Options ACL regression** и **Windows компонентная ZIP сборка + инспектор** — все задания PASS. Ранние CI FAIL были вызваны сигнатурой тестовой заглушки HtmlView::display():void, исправлены только в тесте. **Реальная Joomla 6.2 0.3.11 ещё НЕ проверена.**

## Один следующий тест пользователя

В CMD из `C:\OSPanel\home\minimarket.local`: `git status --short` (не сбрасывать `.osp/project.ini`), `git pull --ff-only origin development`, `scripts/build.ps1`, `scripts/inspect-package.ps1` и при желании PHP тест. Ожидать `PACKAGE_OK: 0.3.11`. Если есть ошибки — не ставить ZIP.

Через Joomla Extensions Installer обновить **только `dist/com_minimarket.zip`** поверх существующей 0.3.10, не удалять компонент. Затем открыть **Компоненты → MiniMarket → Категории или Товары** и проверить кнопку «Настройки / Options» в верхнем тулбаре; либо открыть главный Dashboard MiniMarket и видимую кнопку «Настройки MiniMarket»; либо напрямую `com_config` URL выше. Выбрать «Каталог» в `primary_shop_itemid`, сохранить, проверить автоматические URL `/catalog/category/...` и `/catalog/product/...`, вернуть нужный основной пункт. Остальные параметры/данные/публикации/URLs не должны измениться.

## Следующий крупный функциональный срез только после PASS

`docs/PRODUCT_VARIANTS.md`: характеристики/варианты SKU, цены, остатки, цвет/размер, фотографии, без замены существующего родительского URL и без разрушительных SQL/миграций. Решение об auto-disable и служебном Joomla content plugin **ОТМЕНЕНО** окончательно.
