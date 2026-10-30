# CURRENT_TASK — MiniMarket
Актуально: 2026-10-30. Только `komarovblog/minimarket`, ветка `development`. Проект Shop/Symfony не читать, не менять.

## Согласованный процесс
1. GitHub содержит каркас собственного расширения, `.osp/project.ini`, документацию, `src`, `scripts` и пустой `public_html`.
2. На Windows в `C:\OSPanel\home\minimarket.local` получаем этот каркас (clone или fast-forward pull).
3. В phpMyAdmin создаём отдельную MySQL БД `minimarket` и пользователя.
4. Скачиваем оригинальный Joomla 6 Full Package и устанавливаем в `public_html` через штатный установщик.
5. Только после запуска админки Joomla пишем `com_minimarket` в `src`, публикуем в GitHub, собираем ZIP и устанавливаем/обновляем в локальной Joomla.
6. GitHub хранит **исходники MiniMarket, не установленную Joomla и не БД**; ZIP Joomla устанавливает файлы расширения в рабочий сайт.

**Прежний `scripts/setup-local.ps1` для временного PHP `index.php` удалён как лишний шаг**, т.к. теперь сразу устанавливаем официальную Joomla. Не запускать скрипт из старого чата/клона. В текущем GitHub `public_html` содержит только `.gitkeep` до установки Joomla. Если на ПК уже есть временный `public_html/index.php` от старого скрипта, не перезаписывать вслепую: сначала проверить, что это не часть уже установленной Joomla.

## Текущий ОДИН шаг — проверить состояние новой локальной папки
Пользователь сообщил, что вручную создал `C:\OSPanel\home\minimarket.local`, но **не подтвердил успешный git clone**. В CMD Open Server проверить:
```cmd
dir /a "C:\OSPanel\home\minimarket.local"
```
- Если папка **пуста**, получить структуру:
```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\OSPanel\home\minimarket.local"
```
- Если папка **уже содержит `.git` именно репозитория MiniMarket**, проверить рабочую ветку, origin и отсутствие конфликтующих изменений, затем:
```cmd
git -C "C:\OSPanel\home\minimarket.local" pull --ff-only origin development
```
- Если папка непустая, но не содержит `.git` — **не клонировать поверх и не удалять ничего без осмотра**.

### После подтверждения clone/pull
Отдельная БД MySQL через phpMyAdmin → оригинальный Joomla 6 Full Package непосредственно в `public_html` → штатная установка Joomla и проверка админки → разработка `src/com_minimarket` с установкой через ZIP.

## Правила безопасности
Не коммитить Joomla core, `public_html` (кроме `.gitkeep`), дампы БД, `configuration.php`, `.env`, пароли. Не менять файлы системной Joomla вручную. `main` не трогать. Не удалять старые локальные файлы без бэкапа. Никаких `git reset --hard`, force push, удаления чужих сайтов/репозиториев. Логи Windows → GitHub пока не проверены.
