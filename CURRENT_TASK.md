# CURRENT_TASK — MiniMarket

Дата: 2026-11-01. Репозиторий `komarovblog/minimarket`, ветка `development`; не менять `main`, Joomla core, DB, `.osp/project.ini` или Shop.

## Согласовано
- Один обычный опубликованный пункт меню `MiniMarket → Магазин`, без скрытых пунктов: если назначен главной, URL категорий/товаров `/category/alias-id` / `/product/alias-id`; если пункт `/shop`, URL `/shop/category/alias-id` / `/shop/product/alias-id`. Этот новый маршрутизатор **ещё НЕ реализован**.
- На первом этапе в пункте меню только `show_categories` (Да по умолчанию). Joomla title/alias/home/ACL не дублировать.
- **Отдельные модули Joomla:** категории (`mod_minimarket_categories`), обычные товары (`mod_minimarket_products`), популярные (`mod_minimarket_popular`), новинки (`mod_minimarket_new`), ручные подборки (`mod_minimarket_selection`), фильтры характеристик (`mod_minimarket_filters`). В меню **НЕ** добавлять настройки популярности/новинок/подборок/характеристик. Модули пока **НЕ реализованы**; создавать одновременно с источниками данных, не заглушки.
- Полная спецификация: `docs/MENU_MVP_PLAN.md` и `docs/COMPONENT_ARCHITECTURE.md`; вся история: `docs/PROJECT_PROGRESS.md`.

## Сейчас в исходниках — 0.3.4 (runtime ещё NOT RUN)
- На основании существующего `site/tmpl/catalog/default.xml` добавлена одна рабочая настройка `show_categories` со значением Да по умолчанию, доступная в обычном пункте Joomla; при Нет пропускается загрузка списка категорий, но шаблон MiniMarket остаётся доступным для обычных Joomla-модулей.
- Подпись типа меню в языках en-GB/ru-RU заменена на **Магазин / Shop**, описание обновлено. Старый внутренний `view=catalog` и проверенные frontend URL не менялись; SQL, Joomla core и Router не тронуты.
- Манифест 0.3.4 (включает ранее опубликованный, но ещё не проверенный i18n fix 0.3.3). **Не писать PASS** для ZIP/Joomla 0.3.4 до теста пользователя.

## Подтверждённый runtime перед патчем
- Joomla 6.2.0 / Open Server PHP 8.5, MySQL 8.0: ZIP 0.3.1 `PACKAGE_OK: 0.3.1`, штатная установка и admin MiniMarket PASS.
- 0.3.2 исправила `form.validate`: пользователь подтвердил сохранение тестовой категории и создание/редактирование товара `TEST-001`, цена `1990.50`, остаток 3→2.
- Frontend каталог/категория/товар работали, старые URL `/catalog/testovaa-kategoria-1`, `/catalog/testovyj-tovar-1`. Joomla SEF и rewriting включены.

## Единственный следующий локальный smoke-test
В CMD из `C:\OSPanel\home\minimarket.local` сначала `git status --short` — известный локальный `.osp/project.ini` не трогать, при неожиданных правках остановиться. Затем `git pull --ff-only origin development`, `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`, `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1`. Ожидаемый итог `PACKAGE_OK: 0.3.4`. После PASS штатно установить ZIP **поверх** текущего компонента без удаления Joomla, категорий, товара и меню.

Проверить существующий пункт Joomla: тип `Магазин / Shop`, вкладка параметров содержит `Показывать категории` Да/Нет. При Да категория присутствует на `/catalog`; при Нет категории скрыты, страница не даёт 404/500; снова Да — категории возвращаются. Проверить ссылки на прежнюю категорию и товар и админский Save. Не переименовывать пункт `/catalog` в `/shop` до готовности нового Router. При ошибке — краткий экран/симптом, без SQL или сырых логов.

**После проверки 0.3.4:** реализовать типизированный штатный роутер для `/` и `/shop` на одном обычном пункте магазина. Затем общий список всех товаров и опции его выдачи, далее независимые модули и фильтры.
