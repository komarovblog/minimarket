# CURRENT_TASK — MiniMarket

Обновлено: 2026-10-30. Только репозиторий `komarovblog/minimarket` (ветка `development`). **Shop/Symfony не трогать.**

## Последнее согласованное решение
С 30.10.2026 единственный веб-корень называется **`public_html`** и локально, и на хостинге.

- Рабочая папка: `C:\OSPanel\home\minimarket.local`.
- Веб-корень: `C:\OSPanel\home\minimarket.local\public_html`.
- `.osp/project.ini`: `web_root = {base_dir}\public_html`.
- В GitHub есть `public_html/.gitkeep`. Прежний `public/.gitkeep` удалён из новой версии ветки.
- `scripts/setup-local.ps1` создаёт проверочный временный `public_html/index.php`, **только если файла ещё нет**.
- `src/com_minimarket` — исходники; работающая Joomla и установленный MiniMarket живут в `public_html`.
- Joomla и база данных `minimarket` пока **не установлены** и не подтверждены.

Предыдущие указания, где веб-корень называется `public` или фигурирует `WebProjects`, **устарели**. Старые каталоги не удалять автоматически.

## Текущий ОДИН шаг — получить новую структуру из GitHub
Пользователь сообщил, что **создал папку вручную** в стандартном `C:\OSPanel\home`, но **не подтвердил выполнение** предыдущей команды `git clone`.

Если `C:\OSPanel\home\minimarket.local` **пустая**, в CMD:
```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\OSPanel\home\minimarket.local"
```

Если в этой папке **уже есть `.git` и клонирован MiniMarket на `development`**, при чистых локальных изменениях выполнить:
```cmd
git -C "C:\OSPanel\home\minimarket.local" pull --ff-only origin development
```

Если папка непустая, но не является Git-репозиторием — не перезаписывать. Не удалять существующий старый `public`, `public_html` или скрытые файлы. Git-pull не заменяет локальную конфигурацию с секретами.

## После успешного clone/pull
1. Запустить `powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\OSPanel\home\minimarket.local\scripts\setup-local.ps1"`. Ожидаем `LOCAL_MINIMARKET_INDEX_CREATED` или `INDEX_ALREADY_EXISTS_NOT_MODIFIED`.
2. Перезапустить профиль OSP для `minimarket.local`, включить Apache + PHP 8.5 и проверить `curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/`. Ожидаем `MINIMARKET_BOOTSTRAP_OK` и версию PHP.
3. Подключиться к MySQL 8.0 через phpMyAdmin, создать отдельную БД `minimarket` и локального пользователя.
4. Скачать оригинальный Joomla 6 Full Package и распаковать **непосредственно в `public_html`**, заменив лишь временный `index.php` на оригинальный из Joomla (не затронуть другие проекты).
5. После установки/проверки Joomla разрабатывать `src/com_minimarket` и выпускать штатный ZIP Joomla.

## Ограничения
`main` не трогать. Репозиторий Shop не читать и не менять. Старый `WebProjects` не трогать. Никаких `reset --hard`, force push, удаления локальных файлов без резервной копии или ручных патчей ядра Joomla. Автоматические Windows → GitHub-логи пока не подтверждены.
