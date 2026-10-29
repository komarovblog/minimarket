# CURRENT_TASK — MiniMarket
Дата: 2026-10-29. Только `komarovblog/minimarket`, ветка `development`. Shop/Symfony не затрагивать.

## Текущий исполняемый шаг: 2.1a — локальный домен Open Server Panel 6
Пользователь подтвердил: Windows 11, Apache 2.4 + PHP 8.5 + MySQL 8.0 включены, Open Server Panel 6 запущен, поиск проектов включает `C:\Users\User\Desktop\WebProjects`; существующий Git-клон `C:\Users\User\Desktop\WebProjects\MiniMarket` на `development`; Joomla ещё не установлена. По словам пользователя проверка Git прошла, но автоматические логи и Git push **не подтверждены инструментами** (этап 0.3/0.4 остаётся открытым, не приписывать PASS).

### Действие пользователя сейчас
1. В существующей папке MiniMarket безопасно создать `.osp` и `public` (не внутри Shop) и файл `.osp\project.ini` **только если его нет**:
```ini
[minimarket.local]
http_engine = Apache
php_engine = PHP-8.5
web_root = {base_dir}\public
tls_enabled = off
base_url = http://minimarket.local
```
2. Обновить локальную ветку `development` через `git pull --ff-only` (не `reset`), чтобы применились правила `.gitignore` для `/.osp/` и `/public/`; при ошибке Git не продолжать и не удалять/перезаписывать файлов.
3. Перезапустить Open Server Panel 6, убедиться, что появился проект `minimarket.local`. Если профиль проекта не стартовал автоматически — через меню включить модули проекта. Не устанавливать Joomla, пока не подтверждён домен.
4. После подтверждения — шаг 2.1b: проверить HTTP/PHP 8.5, затем установить чистую Joomla 6 в `public`, собственную БД MySQL 8.0 (без изменения системных таблиц других проектов), провести smoke-проверки.

### Критерий завершения
Домен `minimarket.local` распознан Open Server Panel 6 и ведёт в `MiniMarket\public`. Доступ по HTTP подтверждён отдельным безопасным тестом (следующий шаг). Не утверждать, что Joomla уже установлена.

### Постоянные правила
- Ядро Joomla ни при каких условиях не изменять; использовать официальные API/расширения.
- `.osp` и `public` локальные, в Git не добавлять; `.env` с секретами локален.
- Работать только в MiniMarket, не читать/менять Shop на Symfony.
- Не делать опасных локальных git reset, force push или слияния в main.
- Старый блокер автоматического логирования остаётся видимым в `docs/PLAN.md`, при необходимости вернуться отдельно, не зацикливать установку Joomla на сломанном runner.
