# CURRENT_TASK — MiniMarket
Обновлено 2026-10-29. Только `komarovblog/minimarket` (`development`). Проекты Shop (Symfony) и Bidder не затрагивать.

## Принятое решение: пересоздать только локальную папку MiniMarket с нуля, безопасно
Причина: Git-клон ранее содержал только документацию и старые скрипты, а Joomla и правильная структура OSP не были установлены. В браузере была ошибка 503. Это не доказательство неисправности MySQL/Apache, а недостаток установленной локальной среды. Не делать выводов о точной причине 503 до повторной проверки.

## Уже сделано в GitHub
- `.osp/project.ini`: [minimarket.local], Apache, PHP 8.5, HTTP без SSL, `web_root = {base_dir}\public`. Настройки базируются на официальной документации OSP 6.5.1, не на старом `public_dir`.
- `public/.gitkeep`: каталог для Joomla присутствует после `git clone`; всё остальное содержимое `public` исключено из Git.
- `src/README.md`: будущий собственный компонент (не Joomla core).
- `scripts/setup-local.ps1`: создаёт локальный одноразовый `public/index.php`, который выводит `MINIMARKET_BOOTSTRAP_OK`, `PHP_VERSION`, `JOOMLA_NOT_INSTALLED`.
- `README.md`, обновлённые `.gitignore`, `.env.example` и документация. Старые непроверенные скрипты runner/diagnose и INITIAL_STATE-лог удалены из текущей ветки, доступны в истории.

## Текущий шаг пользователя — резервная копия старого MiniMarket и чистый клон
1. В Open Server выключить модули проекта **только minimarket.local**, не отключать всё и не трогать Shop/Bidder; закрыть редактор с файлами MiniMarket.
2. В Проводнике создать `C:\Users\User\Desktop\MiniMarket-backups` (вне поиска проектов `WebProjects`). **Переместить** целиком старую папку `C:\Users\User\Desktop\WebProjects\MiniMarket` в эту резервную папку, переименовав её там, например, `MiniMarket-before-clean-20261029`. Не удалять, не очищать, не потерять `.git` и локальные секреты.
3. В CMD/консоли Open Server выполнить:
```cmd
git clone --branch development --single-branch https://github.com/komarovblog/minimarket.git "C:\Users\User\Desktop\WebProjects\MiniMarket"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\Users\User\Desktop\WebProjects\MiniMarket\scripts\setup-local.ps1"
```
Если `git clone` или скрипт сообщает ошибку — **остановиться**, старый проект восстановим из бэкапа; не продолжать другие команды на неверном каталоге.
4. Перезапустить Open Server Panel 6.5.1, при необходимости включить модули проекта minimarket.local (Apache и PHP-8.5), проверить:
```cmd
curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/
```
Ожидается `MINIMARKET_BOOTSTRAP_OK` и PHP 8.5. Этот тест ещё не выполнялся.

## Далее после подтверждения
- В phpMyAdmin 5.2.3 выбрать MySQL-8.0 и создать отдельно БД `minimarket` / пользователя; её создание ещё не подтверждено.
- Установить оригинальную Joomla 6 Full Package в `MiniMarket/public`: при распаковке заменить временный `index.php` на штатный Joomla; исходники CMS не коммитить. Проверить PHP/DB/admin/SEF, ядро не модифицировать.
- Затем создать `src/com_minimarket`, штатный пакет установки/обновления; версионировать только собственный код.
- GitHub-логирование и локальный push не считать подтверждёнными, старые скрипты не запускать.

## Запреты
Никакого `Remove-Item -Recurse`, `rmdir /s /q`, `git clean -fdx`, `git reset --hard`, force-push. Сначала резервная копия. Никаких действий в Shop/Bidder и никакого изменения main.
