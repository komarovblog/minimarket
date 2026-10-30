# CURRENT_TASK — MiniMarket
Обновлено: 2026-10-30. Только `komarovblog/minimarket` (ветка `development`). Shop/Symfony и прочие проекты не затрагивать.

## Последнее решение пользователя — стандартный каталог Open Server
Пользователь **отключил каталог `C:\Users\User\Desktop\WebProjects` в настройках поиска проектов Open Server**. Теперь единственное плановое размещение MiniMarket: **`C:\OSPanel\home\minimarket.local`**. Это стандартная папка OSP 6.5.1. Веб-корень: `C:\OSPanel\home\minimarket.local\public`, полностью аналогичен `public_html` хостинга; Joomla и установленный пакет MiniMarket будут внутри `public`, а исходники `src` и `.git` — уровнем выше.

Уже в GitHub подготовлены:
- `.osp/project.ini`: секция `[minimarket.local]`, Apache, PHP-8.5, `web_root = {base_dir}\public`, HTTP без TLS. **Профиль не зависит от абсолютного пути**, менять его не требуется.
- `public/.gitkeep`: пустая папка для CMS; оригинальная Joomla ещё не установлена.
- `src/com_minimarket/README.md`: только заготовка для исходников расширения; код ещё не создан.
- `scripts/setup-local.ps1`: локальный временный PHP-smoke тест. Скрипт вычисляет корень проекта относительно собственного расположения, поэтому работает в `C:\OSPanel\home` без изменений.
- `docs/STRUCTURE.md`: соглашение `public` = хостинговый `public_html`.

### Текущий ОДИН шаг пользователя
Откройте `C:\OSPanel\home` через Проводник. Если **папки `minimarket.local` нет**, в CMD выполнить одну команду:

```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\OSPanel\home\minimarket.local"
```

Если папка `minimarket.local` **уже существует**, НЕ удалять, НЕ копировать поверх, НЕ выполнять clone; сначала посмотреть её содержимое. Предыдущий клон в `WebProjects` не трогать, поскольку OSP его уже не сканирует. Базы данных MySQL тоже не трогать.

### Следующий шаг после успешного clone
```cmd
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\OSPanel\home\minimarket.local\scripts\setup-local.ps1"
```
После этого перезапуск OSP, включение Apache/PHP 8.5 для `minimarket.local`, `curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/`. Ожидаемый маркер `MINIMARKET_BOOTSTRAP_OK`. **До проверки не говорить, что сайт готов или причина старого HTTP 503 найдена.**

Затем создание отдельной БД `minimarket` и установка оригинальной Joomla 6 Full Package **в `public`**. Пакет расширения MiniMarket — отдельно через менеджер расширений Joomla, без правок ядра. phpMyAdmin работает через локальное исключение сертификата; позже заменить исключение доверием к локальному сертификату.

## Постоянные ограничения
Никогда не трогать репозиторий Shop/Symfony, остальные проекты, настройки Open Server целиком и `main`. Не удалять старый локальный клон без сохранения. Не делать `git reset --hard`, force-push или стирание системных каталогов. Автоматическое логирование GitHub с Windows всё ещё не подтверждено.
