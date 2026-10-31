# CURRENT_TASK — MiniMarket

Дата: 2026-11-01. Репозиторий `komarovblog/minimarket`, рабочая ветка `development`. `main`, Joomla core, данные сайта, Shop и локальную конфигурацию Open Server не менять.

## Подтверждено пользователем
- Первая установка MiniMarket 0.3.1 через Joomla Installer PASS; Windows ZIP `PACKAGE_OK: 0.3.1`.
- После исправления `form.validate` и установки 0.3.2 подтверждена работа сохранения/редактирования тестовой категории и товара `TEST-001`.
- Пользователь подтвердил, что публичные страницы каталога/категории/товара открываются; сообщил реальные маршруты `http://minimarket.local/catalog/testovaa-kategoria-1` и `http://minimarket.local/catalog/testovyj-tovar-1`. У них **плоский `alias-ID`**, префикс `catalog` принадлежит menu item Joomla, отсутствие `index.php` соответствует включённым SEF + URL Rewriting. Совпадение ID 1 у категории/товара допустимо из-за разных таблиц. **Canonical/404/SEO strict/301 НЕ проверены**.
- При выборе типа пункта меню MiniMarket в Joomla 6.2.0 вместо подписи показываются сырые `COM_MINIMARKET_CATALOG_MENU_TITLE` и `COM_MINIMARKET_CATALOG_MENU_DESCRIPTION` (скриншот). Это реальный i18n FAIL на установленной 0.3.2.

## Исправлено в GitHub: исходники MiniMarket 0.3.3
- Строки `COM_MINIMARKET_CATALOG_MENU_TITLE` и `...DESCRIPTION` уже были в `site/language/{ru-RU,en-GB}/com_minimarket.ini`, но Joomla меню использует **административные** `admin/language/{ru-RU,en-GB}/com_minimarket.sys.ini`; этих ключей там не было.
- В обе административные `.sys.ini` добавлены тексты, версия манифеста 0.3.3. Другой код, маршрутизатор, структура БД, алиасы и SQL **не менялись**.
- Документация Joomla [Step 2 Defining a Menuitem](https://manual.joomla.org/docs/next/building-extensions/components/component-development-tutorial/step02-menuitem/) подтверждает необходимость `.sys.ini` для метаданных site menu layout.
- **RUNTIME_NOT_VERIFIED:** после публикации 0.3.3 Windows ZIP и установка обновления / отображение подписей ещё не проверены. Не объявлять исправление VERIFIED заранее.

## Единственный следующий шаг пользователя
В CMD из `C:\OSPanel\home\minimarket.local` при отсутствии неожиданных локальных правок:
```bat
git status --short
git pull --ff-only origin development
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1
```
При ошибке остановиться, без `reset/clean`; пользовательский `.osp/project.ini` не трогать. Ожидаем `PACKAGE_OK: 0.3.3`. Штатно установить `dist/com_minimarket.zip` **поверх 0.3.2**, без удаления, через Joomla Extensions. Затем открыть `Меню → Главное меню → Создать → Выбор типа пункта меню → MiniMarket`, проверить человекочитаемые название и описание (на английском, если язык администратора en-GB, на русском — при ru-RU). Существующий пункт «Каталог» не создавать повторно. При PASS — сообщить «Готово», затем отдельным шагом проверить canonical.

## Правила
- URL с /catalog/ сейчас корректны; если позже изменять alias, возможны изменившиеся адреса. Автоматического 301 редиректа для старого slug нет — не переименовывать опубликованные сущности без отдельного плана.
- Никаких SQL/htaccess/SEO переключений в рамках этого исправления.
