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

Клон репозитория должен находиться в:

```text
C:\Users\User\Desktop\WebProjects\MiniMarket
```

Если локальный клон уже существует в неправильном виде, сначала переместить **весь старый MiniMarket** в `C:\Users\User\Desktop\MiniMarket-backups` (за пределы списка проектов OSP), и лишь затем создать новый клон `development` в прежнем месте. Не удалять файлы без резервной копии.

В консоли Open Server (CMD), после клонирования:
```cmd
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\Users\User\Desktop\WebProjects\MiniMarket\scripts\setup-local.ps1"
```

Это создаёт **только если отсутствует** временный локальный `public/index.php`, который отвечает `MINIMARKET_BOOTSTRAP_OK` и `PHP_VERSION`. При установке Joomla его должен заменить оригинальный индекс Joomla. Не коммитить файл.

После перезапуска Open Server проверить:
```cmd
curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/
```

Если до установки Joomla виден временный текст — HTTP и PHP работают. Затем создать отдельную БД и установить оригинальный Joomla 6 Full Package **непосредственно в `public`**, а будущий MiniMarket обновлять штатным установочным ZIP-пакетом. Актуальный один следующий шаг всегда описан в **[CURRENT_TASK.md](CURRENT_TASK.md)**.
