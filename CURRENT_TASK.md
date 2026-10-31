# CURRENT_TASK — MiniMarket

Дата: 2026-10-31. Только `komarovblog/minimarket`, `development`. `main`, ядро Joomla, установленный сайт и пользовательский `.osp/project.ini` не изменять.

## Подтверждённый статус
- Первая установка `com_minimarket 0.3.1`: Windows `build.ps1` PASS; `inspect-package.ps1` → `PACKAGE_OK: 0.3.1`; Joomla Installer → `Installation of the component was successful.`.
- Панель `Компоненты → MiniMarket` открылась (пользователь подтвердил «Готово»).
- **Категории: CRUD FAIL**. Пользователь показал форму в Joomla 6.2.0 с заполненными Title/Description/Published; Save и Save & Close визуально не реагируют, Cancel работает; категория не создана.
- В Category/Product HtmlView отсутствовало подключение `form.validate`, хотя формы имеют `class="form-validate"` и Joomla Toolbar Save/Apply выполняет клиентскую валидацию. Это **доказанный недостающий ресурс в коде**, но без browser runtime теста нельзя гарантировать, что он единственная причина симптома.

## Исходники исправлены до 0.3.2
- `admin/src/View/Category/HtmlView.php` и `admin/src/View/Product/HtmlView.php` теперь подключают штатный `form.validate` через Joomla WebAssetManager. Без пользовательских JS, отключения валидации и правок ядра Joomla.
- Версия манифеста `0.3.2` (`method="upgrade"`), новых миграций SQL **нет**; данные не должны удаляться.
- **Ещё NOT RUN:** Windows build/inspect пакета 0.3.2, Joomla upgrade 0.3.1→0.3.2 и фактическое сохранение категории.

## Единственный следующий шаг пользователя
В CMD из `C:\OSPanel\home\minimarket.local`:
```bat
git status --short
git pull --ff-only origin development
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1
```
При ошибке pull/build/inspect — остановиться; никаких reset/clean. Сохранить `.osp/project.ini`. Ожидаем `PACKAGE_OK: 0.3.2`.

Если OK, штатно загрузить `dist/com_minimarket.zip` в Joomla `Система → Установка → Расширения` как **обновление 0.3.1**, не удаляя компонент. Перезагрузить категорию/создать `Тестовая категория`, алиас пустой, описание `Проверка каталога MiniMarket`, статус `Опубликовано`. Проверить `Save & Close`, появление записи и повторное открытие. При ошибке — короткий симптом/скриншот, без сырых логов или секретов; диагностика JS/Network будет следующим шагом.

Товары, frontend и SEO тестировать **после** подтверждения категории, не смешивать несколько проблем. Код в GitHub ≠ runtime PASS.

## Источники
Joomla Client-side Validation: https://manual.joomla.org/docs/5.4/general-concepts/forms/client-side-validation/ ; общая инженерная KB-0010 в `docs/SHARED_ENGINEERING_KB.md`.
