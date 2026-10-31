# CURRENT_TASK — MiniMarket
Дата: **2026-10-31**. Репозиторий `komarovblog/minimarket`, ветка `development`. `main`, Shop и Bidder не трогать.

## Что пользователь подтвердил
- Joomla 6 на Open Server `C:\OSPanel\home\minimarket.local\public_html` работает, базовый SEF настроен.
- Первая версия **`com_minimarket 0.1.0` собрана и установлена** через Joomla Extension Installer — пользователь сообщил «Готово» после инструкции по установке. Детальные HTTP/admin проверки не присланы; считать подтверждением пользователя, не автоматическим PASS.
- Архитектура: **один самостоятельный компонент со своими категориями/товарами/заказами**, плоскими URL с ID. Joomla Articles и второй компонент на Articles полностью отменены.

## Готово в GitHub, не проверено на локальной Joomla
- **M1a — `com_minimarket 0.2.0`** опубликован в `development` (commit `e55de0bcb46d4a25b4d18b3950353e47c2eeefb5`).
- SQL install+upgrade `0.2.0`: `#__minimarket_categories`, `#__minimarket_products`. Без SQL DROP и без правок существующих Joomla таблиц.
- Админка категорий: список, создать/редактировать, статус. Товары: **только пустой/list view**, редактор следующим этапом.
- Публично показываются опубликованные категории. В `scripts/build.ps1` обновлены обязательные файлы и ZIP-проверка.
- **Статическая проверка файлов/манифеста/дублирования SQL завершена**, Windows PowerShell/PHP runtime/SQL upgrade/Joomla CRUD **НЕ тестированы**, не утверждать PASS.

## Текущий ОДИН шаг — безопасно обновить компонент до 0.2.0
1. Перед установкой **экспортировать БД `minimarket` в приватный локальный архив через phpMyAdmin**, не публиковать.
2. В PowerShell `C:\OSPanel\home\minimarket.local`: сначала `git status --short`. Если есть неожиданные локальные изменения — STOP.
3. `git pull --ff-only origin development` и `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`; ZIP `dist/com_minimarket.zip`.
4. Joomla admin → Система → Установка → Расширения → **обновить** тот же `com_minimarket.zip` (НЕ удалять `0.1.0`).
5. Проверить «Компоненты → MiniMarket → Категории»: создать опубликованную «Тестовая категория», затем список и фронт `http://minimarket.local/index.php?option=com_minimarket`. «Товары» показывают пустой список.
6. Ошибки — короткий текст Joomla, никаких сырых логов/секретов; исправлять исходники, не Joomla core.

Полный чек-лист: `docs/M1A_CATEGORIES_SMOKE.md`.

## После подтверждения
- M1b — форма товаров (категория, SKU, цена, остаток, фото позже), frontend карточки и плоские URL с ID, тест изменений.
- SEO-фильтр/ручные SEO-лендинги/Schema.org Product/Offer/GA4 dataLayer, корзина, оформляемые заказы, OTP/ID, YooKassa и уведомления позже по плану.
- База знаний сложных проблем `docs/SHARED_ENGINEERING_KB.md` и `AGENTS.md` применяются; не создавать KB только за рутинную работу.

## Безопасность
GitHub только наш `src`/документация; `public_html`, `configuration.php`, `.env`, дампы и реальные заказы не коммитить. Не делать reset/force push. Не изменять `main` или чужие сайты/БД.
