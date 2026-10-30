# Структура MiniMarket: Open Server, Joomla и хостинг

Актуально: 2026-10-30. Только `komarovblog/minimarket`, ветка `development`. Shop/Symfony — другой проект.

## Одно имя веб-каталога везде: `public_html`

Локальный корень проекта: `C:\OSPanel\home\minimarket.local`.

Open Server Panel 6.5.1:
```ini
[minimarket.local]
http_engine = Apache
php_engine = PHP-8.5
web_root = {base_dir}\public_html
tls_enabled = off
base_url = http://minimarket.local
```

Структура после установки оригинальной Joomla 6 и **после** реализации/установки MiniMarket:

```text
C:\OSPanel\home\minimarket.local\
├── .git\                                история исходников MiniMarket
├── .osp\project.ini                    локальный профиль Apache/PHP
├── src\com_minimarket\                 исходники нашего компонента (Git)
├── scripts\                             скрипты сборки/проверки (Git)
├── docs\                                документация (Git)
└── public_html\                         ВСЕ файлы работающего Joomla-сайта
    ├── index.php                        оригинальный Joomla
    ├── administrator\
    │   └── components\com_minimarket\   админка установленного компонента
    ├── components\com_minimarket\       публичная часть установленного компонента
    ├── modules\                         модули Joomla и MiniMarket
    ├── plugins\                         установленные плагины Joomla
    ├── media\                           статические ресурсы и медиа
    ├── templates\
    ├── libraries\
    ├── includes\
    └── configuration.php                настройки Joomla, секреты; НЕ в Git
```

Это **целевая иллюстрация**, а не утверждение, что файлы CMS уже существуют: до установки в `public_html` находится лишь `.gitkeep` и, если пользователь запускал `setup-local.ps1`, временный тестовый `index.php`. Joomla и наш компонент пока не установлены.

На обычном хостинге работающий сайт развёртывается в `public_html`, то есть файлы Joomla находятся **непосредственно в этой папке**. Не создавать вложенную `public_html/public_html`, не переносить туда `src`, `.git`, `.osp`, `docs`, `scripts`.

## Что отслеживает Git

- `public_html/.gitkeep` — создание пустой директории при клонировании.
- `.osp/project.ini` — только безопасный шаблон OSP без секретов.
- `src`, `scripts`, `docs`, `NEW_AGENT.md`, `CURRENT_TASK.md`, `README.md`.

**Всё остальное в `public_html` исключено из Git**. В частности Joomla Core, пользовательские данные/медиа, `configuration.php`, установленные копии `com_minimarket`. Старый каталог `public` также находится в `.gitignore`, чтобы случайно не отправить прежние локальные файлы на GitHub.

## Разработка собственного расширения

`src/com_minimarket` — первичные редактируемые исходники. После сборки официального ZIP менеджер расширений Joomla сам размещает нужные файлы внутри `public_html/components`, `public_html/administrator/components` и других стандартных мест.

Правки выполняются в `src`, фиксируются в `development`, затем устанавливаются/обновляются Joomla-пакетом. Ядро Joomla не меняем, не поддерживаем две вручную редактируемые копии компонента.

## Безопасный перенос с ранней структуры

Прежде использовался каталог `public`; с 30.10.2026 он **отменён**, новый веб-корень — `public_html`. Если старый `public` уже существует локально, его **не удалять автоматически**. Проверить содержимое и сохранить резервную копию до ручного удаления. Если был выполнен прежний локальный скрипт, старый `index.php` игнорируем и не переносим в новый `public_html`.

Новое окружение в `C:\OSPanel\home\minimarket.local` не должно зависеть от старого `WebProjects`. Если в `home` уже клонирован предыдущий `development`, выполнить безопасный fast-forward pull, а не повторное клонирование поверх непустого каталога. Проверить `CURRENT_TASK.md`.

## Очередность

1. Получить новую версию `development` в `C:\OSPanel\home\minimarket.local`.
2. Временно проверить HTTP/PHP, создав проверочный `public_html/index.php` через скрипт.
3. Создать БД `minimarket` в MySQL 8.0 через phpMyAdmin и отдельного пользователя.
4. Установить оригинальную Joomla 6 непосредственно в `public_html`; временный индекс должен быть **заменён** оригинальным.
5. Разрабатывать MiniMarket в `src`, собирать ZIP и устанавливать в Joomla. Переносить файлы Joomla и данные БД отдельно от GitHub.

## Утверждённая очередность установки и разработки (30.10.2026)
Наличие каталога `public_html/.gitkeep` **не означает, что Joomla установлена**. Установка оригинальной Joomla Full Package выполняется только после получения структуры GitHub и создания MySQL БД. До Joomla нет ни действующего `com_minimarket`, ни необходимости подменять `index.php` временными тестовыми заглушками.

**Тестовый `scripts/setup-local.ps1` отменён и удалён из текущего Git-дерева.** Он был нужен для отдельной проверки Apache/PHP, но мешает простой установке. Если такой временный индекс уже есть в локальной старой папке, сначала определить его назначение; не делать автоматических перезаписей файла настоящей Joomla.

После установки Joomla код пишется только в `src/com_minimarket` → фиксируется в GitHub `development` → собирается Joomla ZIP → устанавливается штатным менеджером расширений в текущую Joomla внутри `public_html` → тестируется. Не выполняется «обратная синхронизация всей Joomla» в Git: это не исходники нашего расширения. Установщик Joomla по XML-манифесту копирует исполняемые файлы в соответствующие каталоги и применяет SQL-миграции собственного компонента. <https://manual.joomla.org/docs/next/building-extensions/install-update/installation/manifest/>

Уточнение по пути: проект `C:\OSPanel\home\minimarket.local`, веб-корень `public_html`, PHP 8.5 в OSP 6.5.1 — фактически проверяем, но Joomla 6 рекомендует PHP 8.4. Никаких новых каталогов и отдельного Git-клона для Joomla не создаём.
