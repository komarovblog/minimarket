# MiniMarket 0.3.5 — безопасный smoke новых маршрутов Joomla

**Дата:** 01.11.2026. **Статус:** исходники опубликованы в `development`, пользовательский Joomla runtime **NOT RUN**. Проверка для существующей Joomla 6.2.0 в Open Server.

## Изменения 0.3.5
- Вместо неоднозначного одного сегмента `alias-ID` роутер строит `category/alias-ID` и `product/alias-ID` после обычного пункта магазина.
- Одна стандартная опубликованная запись меню `MiniMarket → Магазин` (внутренний `view=catalog`) определяет базу маршрутов. Роутер переписывает `Itemid` сторонней страницы на этот явно назначенный пункт; если опубликованных пунктов магазина 0 или больше 1, появляется понятная ошибка конфигурации вместо скрытого fallback.
- При разборе нового адреса проверяются конкретный тип, существование опубликованной категории/товара и соответствие slug; ошибочный путь даёт 404.
- Старые одно-сегментные ссылки `/catalog/{alias}-{id}` на **прежнем пункте меню** временно работают для обратной совместимости. Их canonical ведёт на typed URL. **301-редиректы ещё не реализованы — не объявлять SEO-миграцию завершённой и не переносить эту версию на индексируемый сайт до отдельной проверки.**
- НЕТ правок ядра Joomla, SQL/таблиц, `.htaccess`, `RouteHelper`, цен, SKU, форм и галерей. Будущие товарные варианты — отдельная задача.

## Этап A — первый тест, без изменения Joomla menu

1. В CMD из `C:\OSPanel\home\minimarket.local`: `git status --short`. Известный `.osp/project.ini` не сбрасывать, при неожиданных изменениях остановиться.
2. `git pull --ff-only origin development`.
3. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`
4. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1` → ожидаем `PACKAGE_OK: 0.3.5`.
5. Через штатную Joomla Extensions установку **обновить компонент поверх 0.3.4**, без удаления и ручных SQL.
6. Оставить существующий обычный опубликованный пункт магазина с alias `catalog`. Должны открываться:
   - `http://minimarket.local/catalog`
   - `http://minimarket.local/catalog/category/testovaa-kategoria-1`
   - `http://minimarket.local/catalog/product/testovyj-tovar-1`
   - старые `http://minimarket.local/catalog/testovaa-kategoria-1` и `http://minimarket.local/catalog/testovyj-tovar-1`.
7. На новом товаре должен быть `TEST-001`, цена `1990.50`, остаток `2`; из каталога перейти в категорию, затем товар; админские Save и `show_categories` Да/Нет должны работать.
8. Если предыдущие проверки PASS: в HTML-исходнике нового товара один canonical на `/catalog/product/testovyj-tovar-1`; старый адрес также содержит canonical на typed. Неверный путь `/catalog/product/testovaa-kategoria-1` должен быть 404, как и `/catalog/product/missing-999999`.

**При ошибке:** не менять alias главной, не удалять меню, не править SQL/htaccess; передать короткое описание и URL/скриншот. Не публиковать консольные логи с персональными данными в чат.

## Этап B — только после подтверждённого A
- Контекст самостоятельного магазина: назначить **этот обычный пункт** главной, проверить `/category/testovaa-kategoria-1`, `/product/testovyj-tovar-1`.
- Контекст магазина в составе сайта: обычный пункт с alias `shop`, проверить `/shop/category/...` и `/shop/product/...`. **Нельзя одновременно держать 2 опубликованных пункта MiniMarket** в текущем контракте.
- До смены базы `/catalog` на `/` или `/shop` отдельно реализовать безопасные 301 со старых URL/неизменный canonical и проверить отсутствие SEO-дублей. Этап B пока **NOT RUN**.

## Ограничения тестирования
Проверены PHP 8.4 `php -l` для прототипа роутера и локальные mock-сценарии Menu/DB (типизированный parse/build, legacy-совместимость, совпадение category/product alias-ID, 404, 0/2 меню). Синтаксис и работа в реальной Joomla/SEF/Itemid должны быть проверены пользователем; mock-PASS не равен Joomla PASS. Документация Joomla: https://manual.joomla.org/docs/next/general-concepts/routing/build/ .
