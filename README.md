# MiniMarket — компонент магазина для Joomla 6

Репозиторий: `komarovblog/minimarket`. Рабочая ветка: **`development`**. Проект Shop на Symfony — отдельный репозиторий; не читать и не изменять.

## Структура

```text
C:\OSPanel\home\minimarket.local\
├── .git\                     GitHub, только разработка
├── .osp\project.ini          Конфигурация Open Server Panel 6.5.1
├── public_html\             ВЕСЬ РАБОТАЮЩИЙ САЙТ Joomla 6
│   ├── index.php             Файл Joomla после её установки
│   ├── administrator\        Админка Joomla и установленный MiniMarket
│   ├── components\           Публичная часть установленного MiniMarket
│   ├── modules\
│   ├── plugins\
│   ├── media\
│   └── templates\
├── src\com_minimarket\       Исходники расширения, храним в GitHub
├── scripts\                 Локальная сборка / проверки
└── docs\                    Документация
```

На обычном хостинге веб-корень также называется **`public_html`**. На нём должны находиться файлы Joomla и **установленные** расширения, но не `.git`, `src`, `docs`, `scripts`, `.osp`.

В Git отслеживается пустой каталог `public_html/.gitkeep`, но сама Joomla, её файлы, медиа/данные и `configuration.php` в Git **не добавляются**. Разрабатываем исходники `src`, собираем Joomla-совместимый ZIP, устанавливаем и обновляем через штатный менеджер расширений. **Ядро Joomla не модифицируем**.

Подробная схема: [docs/STRUCTURE.md](docs/STRUCTURE.md).

## Локальный запуск (Open Server Panel 6.5.1, Windows 11)

Каталог `WebProjects` больше не используется. Правильный путь: `C:\OSPanel\home\minimarket.local`. В `.osp/project.ini` задано `web_root = {base_dir}\public_html`, Apache и PHP 8.5, локальный HTTP.

Если папка проекта ещё не является Git-клоном и **полностью пустая**, выполнить в CMD:
```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\OSPanel\home\minimarket.local"
```

Если репозиторий **уже клонирован**, вместо clone безопасно обновить `development` (при чистой рабочей папке):
```cmd
git -C "C:\OSPanel\home\minimarket.local" pull --ff-only origin development
```

Если папка существует, но она не пуста и не является Git-клоном, **остановиться** — ничего не перезаписывать. Старый проект `WebProjects` не удалять.

Для первоначальной проверки Open Server до установки Joomla можно запустить скрипт, который создаёт **только при отсутствии** временный `public_html/index.php` с текстом `MINIMARKET_BOOTSTRAP_OK`:
```cmd
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\OSPanel\home\minimarket.local\scripts\setup-local.ps1"
```

Затем перезапустить профиль Open Server и открыть `http://minimarket.local/`. После проверки **вместо тестового `index.php` должен быть установлен оригинальный `index.php` Joomla 6** из официального Full Package; не хранить или редактировать копии ядра в Git.

После чистой установки Joomla понадобится отдельная MySQL-база `minimarket`; компонент использует ту же БД через штатные API Joomla. Следующий шаг: [CURRENT_TASK.md](CURRENT_TASK.md).
