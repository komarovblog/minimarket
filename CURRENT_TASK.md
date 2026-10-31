# CURRENT_TASK — MiniMarket

Дата: 2026-10-31. Репозиторий `komarovblog/minimarket`, ветка `development`. `main`, ядро Joomla, SQL/БД и конфигурацию Open Server не менять.

## Подтверждённый статус 31.10.2026
- **Первая установка `com_minimarket 0.3.1` прошла через штатный Joomla Installer.** Пользователь предъявил зелёное сообщение `Installation of the component was successful.`
- Локально в Windows CMD: `scripts/build.ps1` → `MiniMarket ZIP: C:\OSPanel\home\minimarket.local\dist\com_minimarket.zip`; `scripts/inspect-package.ps1` → **`PACKAGE_OK: 0.3.1`**.
- Исправление portable ZIP entries из коммита `fce8a37d` подтверждено цепочкой `build → inspect → Joomla install`. Происхождение каждого имени старого сломанного ZIP не исследовано, но новый путь работает.
- **Ещё не проверены:** наличие и работа страницы MiniMarket в меню «Компоненты», категории, товары, формы сохранения, таблицы в БД, frontend, SEF и canonical. Не приписывать их успех установке.
- Модифицированный локально `.osp/project.ini` — пользовательская настройка Open Server; не сбрасывать и не перезаписывать.

## Единственный следующий шаг пользователя
В админке Joomla 6 открыть **Компоненты → MiniMarket** (или прямую страницу `/administrator/index.php?option=com_minimarket`), проверить, что открылась панель MiniMarket с кнопками категорий/товаров без HTTP 500 и ошибок. Сообщить коротко результат или скриншот. Пока **не требуется** новая сборка ZIP, git pull, обновление Joomla, переустановка или ручное изменение SQL.

После подтверждения панели: по одному тесту для категории и товара (сохранение/отображение), затем frontend и canonical по документации `docs/M1A_CATEGORIES_SMOKE.md`, `docs/M1B_PRODUCT_EDITOR_SMOKE.md`, `docs/M1C_FRONTEND_ROUTING_SMOKE.md`, `docs/M1D_CANONICAL_SMOKE.md`. Старые инструкции «обновить поверх 0.3.0» считать историческими: это **первая установка 0.3.1**.

## Документация
- `docs/INSTALL_ZIP_DIAGNOSTIC.md` — диагностика и новый PASS.
- `docs/SHARED_ENGINEERING_KB.md`, карточка KB-0008 — статус VERIFIED только для ZIP/первой установки; зеркало в `komarovblog/shop` обновляется исключительно этим документом.
- Дальнейшая разработка — один автономный `com_minimarket`, никаких товаров на Joomla Articles или второго магазина.
