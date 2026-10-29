# CURRENT_TASK — MiniMarket
Дата: 2026-10-29. Только `komarovblog/minimarket`, ветка `development`. Shop/Symfony не затрагивать.

## Что уже подтверждено
- Open Server Panel 6.5.1. Домены в `osp status`; `minimarket.local` включён.
- Apache 2.4.66, PHP 8.5.4, MySQL 8.0.45 включены.
- **29.10.2026**, пользователь из CMD (`C:\OSPanel>`) выполнил `curl.exe -4 --noproxy "*" -I --max-time 10 http://minimarket.local/`. Ответ **HTTP/1.1 200 OK**, `Server: Apache`. Следовательно, сетевой HTTP-доступ к домену работает.
- Код 200 на HEAD ещё не гарантирует, что DocumentRoot — `C:\Users\User\Desktop\WebProjects\MiniMarket\public`, и не подтверждает работу PHP. Joomla ещё не установлена.
- В официальных загрузках Joomla последняя стабильная на эту дату — **6.2.0** (Full Package ZIP); https://downloads.joomla.org/latest .

## Текущий шаг — завершить проверку 2.1b, подтвердить DocumentRoot
**Пользователь предпочитает короткие CMD-совместимые команды**, Open Server открывает CMD; не давать PowerShell синтаксис с `$var` без запуска PowerShell.

В CMD из любого каталога (путь MiniMarket абсолютный), только три простых команды:
```cmd
if not exist "C:\Users\User\Desktop\WebProjects\MiniMarket\public\__mm_probe_20261029_1440.txt" echo MINIMARKET_STATIC_OK>"C:\Users\User\Desktop\WebProjects\MiniMarket\public\__mm_probe_20261029_1440.txt"
curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/__mm_probe_20261029_1440.txt
del "C:\Users\User\Desktop\WebProjects\MiniMarket\public\__mm_probe_20261029_1440.txt"
```
Если получено `MINIMARKET_STATIC_OK`, отметить DocumentRoot PASS и переходить к отдельной MySQL БД и чистой Joomla 6.2.0 в `public`. Если вместо строки 404/403/500 — диагностировать по результатам, не менять Joomla (её ещё нет). Команды безопасны для остальных проектов, но не использовать старое имя временного файла для иных целей.

## Далее
После проверки статики — создать отдельную БД MySQL (не использовать базу Shop или Bidder), скачать оригинальный Full Package 6.2.0 с официального Joomla и установить через штатный мастер. Не копировать ядро Joomla в Git; `/.osp/` и `/public/` исключены в `.gitignore`. PHP 8.5 — проверить совместимость установщиком и smoke-test.

## Прочие открытые вопросы
Автоматическая отправка консольных логов Windows → GitHub (этапы 0.3/0.4) по-прежнему не имеет подтверждённого PASS. Не тратить время на переписывание runner наугад. Все изменения — только MiniMarket, без правок CMS ядра.
