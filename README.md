# MiniMarket — расширение интернет-магазина для Joomla 6

**GitHub:** `komarovblog/minimarket`, рабочая ветка `development` (не затрагивать `main`). Проект Shop на Symfony совершенно отдельный.

## Где находится сайт

**ВЕСЬ РАБОТАЮЩИЙ САЙТ лежит в `public`**, включая Joomla, `administrator`, `components`, `modules`, `plugins`, `templates` и медиа. Локальная папка `MiniMarket/public` — аналог `public_html` на хостинге: при переносе в `public_html` попадает **содержимое** `public`, а не `public` как вложенная папка.

При этом GitHub — это репозиторий **разработки расширения**, поэтому `.git`, `src`, `scripts`, `docs` и `.osp` находятся на уровень выше `public` и не доступны через веб-сервер. Joomla Core в GitHub **не коммитим**, никогда не редактируем системные файлы Joomla.

Полная схема локального проекта, установленной Joomla и хостинга: **[docs/STRUCTURE.md](docs/STRUCTURE.md)**.

## Что есть в GitHub до установки Joomla

```text
MiniMarket/
├── .osp/project.ini          # Open Server Panel 6.5.1, Apache, PHP 8.5, HTTP
├── public/.gitkeep           # Веб-корень (Joomla будет установлена сюда)
├── src/com_minimarket/       # Исходный код собственного расширения (пока только описание)
├── scripts/setup-local.ps1   # Локальная временная проверочная PHP-страница
├── docs/                     # Требования, структура, план
├── NEW_AGENT.md
└── CURRENT_TASK.md
```

## Подготовка на Windows 11 / Open Server Panel 6.5.1

**Актуальное размещение (30.10.2026):** стандартный каталог Open Server Panel 6.5.1:

```text
C:\OSPanel\home\minimarket.local
```

Домен проекта и имя директории одинаковые, поэтому Open Server обнаружит проект в `home`. Каталог `C:\Users\User\Desktop\WebProjects` **удалён пользователем из путей поиска проектов OSP и более не используется**. Старый клон оставляем нетронутым как временную резервную копию: **его не нужно переносить/удалять, чтобы создать новый клон в другом каталоге**.

В консоли Open Server (CMD) **только если каталога `C:\OSPanel\home\minimarket.local` ещё нет**:

```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\OSPanel\home\minimarket.local"
```

Если директория уже существует, не выполнять clone поверх неё, не удалять её и сначала выяснить содержимое.

После успешного клонирования запустить:

```cmd
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\OSPanel\home\minimarket.local\scripts\setup-local.ps1"
```

Скрипт, если `index.php` не существует, создаст одноразовую локальную проверочную страницу `public/index.php` (в Git не попадает). Затем перезапустить OSP, включить модули **только** MiniMarket (Apache + PHP 8.5) и проверить:

```cmd
curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/
```

Ожидается `MINIMARKET_BOOTSTRAP_OK` и `PHP_VERSION=8.5.*`. Затем создать БД `minimarket` через phpMyAdmin, установить официальный Joomla 6 Full Package непосредственно в `public` (временный `index.php` заменяется штатным оригинальным), и начать установку своего пакета MiniMarket. `public` равен будущему `public_html` по содержимому.

Текущий один шаг: [CURRENT_TASK.md](CURRENT_TASK.md).
