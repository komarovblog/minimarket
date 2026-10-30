# CURRENT_TASK — MiniMarket
Обновлено: 2026-10-30. Только `komarovblog/minimarket` / ветка `development`. `main`, Shop и Bidder не трогать.

## Актуальная архитектура
- **Один самостоятельный `com_minimarket`**, собственные категории, товары, комплекты и собственные защищённые заказы. Никакого магазина на материалах Joomla, `plg_content_minimarket_articles` и второго каталога. Полная спецификация в `docs/COMPONENT_ARCHITECTURE.md`.
- Товары/категории с плоскими alias-ID URL, SEO-фильтр, ручные SEO-лендинги, Product/Offer Schema.org, GA4 dataLayer, доставка/оплата, телефон/ID авторизация и уведомления остаются обязательными на дальнейших этапах.
- Локальная Joomla 6 установлена и работает по словам пользователя: `C:\OSPanel\home\minimarket.local\public_html`, `http://minimarket.local/`, SEF включён. Она только тестовый стенд, не источник кода.

## M0 исходники опубликованы на GitHub
- Создан **настоящий исходный компонент версии `0.1.0`** в `src/com_minimarket/`: Joomla XML manifest, админ/сайт MVC контроллеры/представления, provider DI, ru/en переводы, admin Options `catalog_title`, тип пункта меню каталога. Пока **нет БД, товарного CRUD, корзины и оплаты**.
- Добавлен `scripts/build.ps1` для сборки архива `dist/com_minimarket.zip` из `src` с проверками manifest/внутренних путей. `.gitignore` уже исключает `dist/`.
- Проверено через GitHub чтение: **все 17 исходных файлов/скрипт присутствуют**, namespace/manifest и опорные пути соответствуют ожидаемым. **Не проверены** фактический запуск Windows PowerShell, PHP на установленной Joomla, установка ZIP и админский интерфейс. Не заявлять PASS до локального теста. Исходники опубликованы коммитом `3e3c36de21238baf2785559d749515aedf7666cc`.

## Единственный следующий шаг — локальная сборка и установка
1. Пользователь в PowerShell: `Set-Location 'C:\OSPanel\home\minimarket.local'`, `git pull --ff-only origin development`, `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`. Если есть незакоммиченные локальные изменения, сначала проверить `git status --short`; не делать force/reset.
2. Убедиться, что создан `C:\OSPanel\home\minimarket.local\dist\com_minimarket.zip`. Никакой ручной копии в `public_html`.
3. Joomla admin → Система → Установка → Расширения → Загрузка файла пакета, выбрать этот ZIP. После установки «Компоненты → MiniMarket»; проверить «Настройки» — поле заголовка каталога. Сайт: `http://minimarket.local/index.php?option=com_minimarket` должен показать заголовок и временное описание. Не делать публичный menu item, пока нет каталога; можно проверить прямой URL.
4. При ошибке — показать текст ошибки Joomla или короткий результат сборки (без паролей/секретов). После фактического PASS зафиксировать в GitHub logs/validation, затем M1 CRUD категорий/товаров.

## Безопасность
Никогда не менять Joomla Core или `public_html` через Git напрямую. Не коммитить `.env`, CMS файлы, БД/PII, `configuration.php`; не трогать `main`, чужие проекты и существующие данные. Не называть M0 «установленным», пока пользователь не проверил.
