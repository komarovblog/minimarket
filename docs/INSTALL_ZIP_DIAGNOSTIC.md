# MiniMarket: диагностика «Unable to detect manifest file»

Дата: 2026-10-31. **Статус: ошибка воспроизведена на скриншоте пользователя, причина ещё не подтверждена.** До диагностики запрещено утверждать, что предыдущая версия компонента была установлена; пользователь прямо сообщил, что её не устанавливал.

## Факты

- Joomla 6 выводит `Unable to detect manifest file.` при загрузке архива.
- В GitHub имеется `src/com_minimarket/minimarket.xml` с корневым тегом `<extension type="component" method="upgrade">`.
- Код `scripts/build.ps1` собирает `dist/com_minimarket.zip`, но без доступа к **реальному локальному ZIP пользователя** нельзя утверждать, что файл совпадает со свежими исходниками.
- Встроенный Joomla 6 `InstallerHelper::detectType` ищет XML в распакованном каталоге и проверяет `<extension>`. Наличие правильного manifest в GitHub **не доказывает**, что он есть в загруженном архиве в правильном месте.

## Диагностика — без переустановки Joomla

Из корня репозитория, в PowerShell (после безопасного `git pull --ff-only origin development` при чистом `git status --short`):
```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1
```

Скрипт изучает `dist/com_minimarket.zip`, не меняет его и выводит один код:
- `PACKAGE_OK: 0.3.1` — в ZIP есть валидный корневой `minimarket.xml` и ожидаемые файлы; **это ещё не Joomla install PASS**.
- `PACKAGE_FAIL: FILE_NOT_FOUND` — архива нет по ожидаемому пути.
- `PACKAGE_FAIL: MANIFEST_NOT_AT_ROOT` — XML находится внутри дополнительной папки. Нужно исправить структуру ZIP/выбор файла.
- `PACKAGE_FAIL: MANIFEST_MISSING` — манифест не найден.
- `PACKAGE_FAIL: INVALID_MANIFEST` — XML не описывает компонент.
- `PACKAGE_FAIL: MISSING_COMPONENT_FILES` — ZIP неполный.
- `PACKAGE_FAIL: ZIP_OR_XML_UNREADABLE` — ZIP повреждён или содержимое XML не читается.

**При `PACKAGE_OK` и продолжении ошибки Joomla:** независимо изучить тот ZIP, который был фактически выбран при загрузке (имя, местоположение, содержимое), затем проверять права/пути временных файлов Joomla. Не исправлять Joomla core или конфигурацию наугад.

**Не выводить сырые логи и не просить пользователя удалять рабочую Joomla.**

Источники Joomla 6: `libraries/src/Installer/InstallerHelper.php::detectType`, `libraries/src/Installer/Installer.php::findManifest`.
