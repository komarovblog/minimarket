# CURRENT_TASK — MiniMarket
Обновлено: 2026-10-30. Только `komarovblog/minimarket`, `development`; не трогать main или Shop/Symfony.

## Что завершено
- Пользователь сообщил, что **чистая Joomla 6 развернута и всё работает** по адресу `http://minimarket.local/` в `C:\OSPanel\home\minimarket.local\public_html` (Open Server Panel 6.5.1, Apache/PHP 8.5, MySQL 8.0).
- Принята архитектура **MiniMarket Articles** (товары = материалы выбранных товарных категорий Joomla, категории/поля Joomla, штатные URL), затем **MiniMarket Catalog** (собственные таблицы товаров/категорий, плоские URL с ID).
- Общие безопасные таблицы заказов в двух версиях, общие корзина/checkout/платежи/auth, SEO-фильтр, Schema.org и dataLayer. Подробности `docs/VARIANTS_PROPOSAL.md`.
- Ядро Joomla, `public_html`, медиаданные, БД и секреты не попадают в GitHub; в `development` храним только наши исходники и инструкции.

## Текущий ОДИН шаг — включить правильный Joomla 6 SEF
Подготовлена инструкция `docs/JOOMLA_SEO_BASELINE.md`. Пользователь предложил сперва настроить SEF/устранение дублей — логично сделать **до создания товарных категорий и карточек**.

Сейчас сделать **только раздел A**:
1. Проводник: `C:\OSPanel\home\minimarket.local\public_html`: если `.htaccess` уже существует, не перезаписывать; иначе переименовать оригинальный Joomla `htaccess.txt` → `.htaccess`. Не трогать чужие проекты.
2. Joomla admin → System → Global Configuration → Site → SEO: `Search Engine Friendly URLs = Yes`, `Use URL Rewriting = Yes`, `Add Suffix to URL = No`, `Unicode Aliases = No`.
3. Сохранить, проверить `http://minimarket.local/` и `http://minimarket.local/administrator/`. При ошибке 404/500 остановиться и сообщить её; не менять Apache/DB/Joomla core наугад.

**Не включать Strict Routing до теста реального content URL**. Не задавать `minimarket.local` как постоянный canonical-домен будущего хостинга. `Force HTTPS` локально без доверенного сертификата не включать. Фактическое выполнение настроек пока **не подтверждено**.

## После подтверждения
- Настройки статей: `Content → Articles → Options → Integration → Routing → Remove IDs from URLs = Yes`.
- `System - SEF` и возможную strict routing проверить на реальных товарах и категориях, 301/404/canonical.
- Подготовить первую маленькую устанавливаемую версию MiniMarket Articles (выбор категорий Joomla как товарных, Custom Fields SKU/price, безопасный вывод, тест стандартных URL).
- Потом schema/dataLayer, SEO-фильтр, общая корзина/заказы. Для Catalog B — собственный маршрутизатор с ID.

## Ограничения
Не выдавать установленные локальные настройки Joomla за изменённые через GitHub. Не публиковать Joomla Core, конфиги, данные или пароли; не трогать main, другие проекты и базы. Не переписывать чужой `.htaccess` без проверки.
