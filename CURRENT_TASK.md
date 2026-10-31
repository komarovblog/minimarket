# CURRENT_TASK — MiniMarket
Дата: 2026-10-31. Только `komarovblog/minimarket`, ветка `development`. Не менять main, Shop или другие проекты.

## Реальный статус
- Пользователь прямо подтвердил, что **никакой предыдущей версии MiniMarket не устанавливал**. Считать компонент **неустановленным / установка не подтверждена** до проверки в Joomla Extensions.
- Joomla 6 показывает `Unable to detect manifest file`.
- Даже после успешной команды старого `scripts/build.ps1` инспектор возвращал `PACKAGE_FAIL: MISSING_COMPONENT_FILES`.
- `git status --short` у пользователя показал `M .osp/project.ini`. Этот локальный файл **не трогать** и не сбрасывать: пользовательские настройки Open Server.

## Что исправлено в GitHub
- Опубликован коммит `fce8a37d4d59afa72034c8e519a94ec021203e8c`: `scripts/build.ps1` больше не использует `Compress-Archive`, создаёт ZIP .NET `ZipArchive`/`ZipFileExtensions.CreateEntryFromFile` с явными `/` в путях, проверяет именно сырые имена ZIP entries.
- `scripts/inspect-package.ps1` отличает `NONPORTABLE_ENTRY_PATHS` от отсутствия конкретного файла и проверяет корневой manifest.
- **Причина прошлой ошибки инспектора по коду:** предыдущий builder сравнивал имена записей ZIP после нормализации `\\`→`/`, а инспектор делал точный `GetEntry('admin/services/provider.php')`. Содержимое конкретного старого ZIP пользователя не изучено, так что ошибка Joomla пока не объявлена однозначно устранённой.
- Исходники/состав проверены статически, **реальный Windows PowerShell/Joomla runtime ещё не проверен**.

## Следующий единственный шаг для пользователя
В `C:\OSPanel\home\minimarket.local` (CMD, который пользователь использует) выполнить:
```bat
git pull --ff-only origin development
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1
```
Перед pull проверить `git status --short`. Поскольку `.osp/project.ini` локально модифицирован, не делать reset/clean; `pull --ff-only` безопасно применять только если нет конфликта. При ошибке stop.
Ожидаем `PACKAGE_OK: 0.3.1`. Если успешно, впервые попробовать установить `dist/com_minimarket.zip` в Joomla через Extensions Installer и проверить наличие MiniMarket в Управлении расширениями. Не удалять Joomla/не импортировать SQL вручную. Если FAIL — исправить причину, не повторять бесполезные перезапуски.

## Общая база знаний
`docs/SHARED_ENGINEERING_KB.md` зеркалируется с Shop. Для случая несогласованного ZIP-builder и inspector использовать **PARTIALLY_VERIFIED** до фактического PACKAGE_OK и установки.
