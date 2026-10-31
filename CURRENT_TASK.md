# CURRENT_TASK — MiniMarket

**Дата:** 2026-11-01. Репозиторий `komarovblog/minimarket`, ветка `development`. Не менять `main`, Joomla core, локальные файлы `.osp/project.ini`, SQL/данные и другой проект Shop.

## Подтверждённый статус
- Joomla 6.2.0/Open Server: 0.3.1 ZIP+установка PASS, версия 0.3.2 сохраняет категории и тестовый товар `TEST-001` (цена `1990.50`, остаток `3→2`), старые frontend ссылки `/catalog/testovaa-kategoria-1` и `/catalog/testovyj-tovar-1` открываются.
- На smoke MiniMarket 0.3.4 пользователь ответил «Готово»: обычный тип пункта меню «Магазин», переключатель категорий Да/Нет/Да и регрессия прежних ссылок — **USER-REPORTED PASS**. Нет независимого аудита консоли/SQL.
- Пользователь утвердил один товар с множеством цветовых/размерных вариантов под одним URL, с собственными SKU/ценами/остатками/фото и фильтрацией по одному реальному variant_id. Правила галереи: общая, по цвету/значению свойства, точный вариант. Всё **DESIGN_ONLY** — `docs/PRODUCT_VARIANTS.md`.

## Опубликован код 0.3.5: typed Router (Joomla RUNTIME_NOT_VERIFIED)
- Изменены **только** `src/com_minimarket/site/src/Service/Router.php` и версия манифеста на `0.3.5`. Новые сегменты `category/{alias}-{id}`, `product/{alias}-{id}` после **одного** обычного опубликованного пункта меню `MiniMarket → Магазин`; нет скрытых Itemid/автосоздания меню. Чужой Itemid заменяется на единственный пункт магазина, ноль/несколько опубликованных пунктов вызывает понятную ошибку.
- Старые одно-сегментные `/catalog/{alias}-{id}` при прежнем menu item пока распознаются; canonical формируется на новый typed URL. **301 НЕ РЕАЛИЗОВАН**: до публикации/смены базы обязателен отдельный SEO-этап.
- Исходный PHP прототип прошёл локальный PHP 8.4 lint и изолированные mock Menu/DB сценарии. **Реального ZIP/Joomla 0.3.5 PASS ещё нет.**
- Новые URL при текущем alias `catalog` (без изменения пользовательских настроек): `/catalog/category/testovaa-kategoria-1`, `/catalog/product/testovyj-tovar-1`; в дальнейшем один обычный пункт как главная `/` или раздел `/shop`. Не переключать меню до отдельного smoke и 301.

## Единственный следующий шаг пользователя — локальный тест
1. `git status --short` из `C:\OSPanel\home\minimarket.local`; известный пользовательский `.osp/project.ini` не трогать, на неожиданных изменениях остановиться.
2. `git pull --ff-only origin development`.
3. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`.
4. `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1`, ожидается `PACKAGE_OK: 0.3.5`.
5. Штатно установить `dist/com_minimarket.zip` поверх 0.3.4, **не переименовывая** текущий menu item `/catalog`. Проверить новый URL категории, новый URL товара и старые ссылки, сохранность `TEST-001`, работу категорий/админки. Подробный чек-лист `docs/M1E_TYPED_ROUTING_SMOKE.md`.
6. При ошибке сообщить краткий симптом/URL/скриншот; не менять SQL/.htaccess/menu alias вручную.

После PASS — отдельно 301 старых URL и проверка схем `/`/ `/shop`, затем миграция простых товаров в варианты с сохранением ID.
