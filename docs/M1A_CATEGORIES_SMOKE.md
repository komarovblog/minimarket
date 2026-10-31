# MiniMarket M1a — собственные категории, SQL 0.2.0 и проверка обновления

**Дата:** 2026-10-31. Репозиторий `komarovblog/minimarket`, **`development`**, Joomla 6, PHP 8.5, MySQL 8.0. Штатные Joomla Articles не используются.

## Что действительно реализовано в исходниках

- Манифест компонента `0.2.0`, раздел SQL установки `admin/sql/install.mysql.sql` и последовательность обновления `admin/sql/updates/mysql/0.2.0.sql`. Joomla применяет SQL update на существующий установленный `0.1.0`, для чистой установки берёт install SQL. Скрипты содержат `CREATE TABLE IF NOT EXISTS`, **нет удаления таблиц**.
- `#__minimarket_categories`: ID, parent_id (поле зарезервировано, редактор иерархии ПОКА не готов), title, alias, description, state; уникальный alias.
- `#__minimarket_products`: ID, category_id, title, alias, SKU, decimal(15,2) price, stock, state; связь с категориями. **Редактирование товаров ещё не реализовано.**
- Административные страницы `MiniMarket → Категории` (список, «Создать», редактирование, состояние 0/1) и `MiniMarket → Товары` (список без управления). Категории создаются через штатные Joomla FormController/AdminModel/Table и форму `admin/forms/category.xml`.
- Публичная страница `index.php?option=com_minimarket` отображает **только опубликованные категории** (без ссылок на неподготовленные маршруты).
- `scripts/build.ps1` проверяет наличие SQL, формы и основных файлов перед сборкой `dist/com_minimarket.zip`.

**Стадия:** статическая проверка исходников/наличия SQL и ссылок PASS. **Локальная сборка PowerShell, SQL upgrade, сохранение категории и фронт на установленной Joomla НЕ ПРОВЕРЕНЫ** и ждут ручного smoke-теста. Не писать, что M1a реально работает, до пользовательского результата.

## Перед обновлением

1. Открыть phpMyAdmin и сделать **полный экспорт БД `minimarket`** в приватную локальную папку/архив. Дамп **не загружать в GitHub и чат**. Рабочая Joomla у пользователя уже установлена (M0).
2. В **PowerShell**, из `C:\OSPanel\home\minimarket.local`, проверить `git status --short`. При неожиданных локальных правках остановиться, не делать reset/clean.
3. Выполнить:
   ```powershell
   Set-Location 'C:\OSPanel\home\minimarket.local'
   git pull --ff-only origin development
   powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
   ```
4. Joomla → Система → Установка → Расширения → загрузить `C:\OSPanel\home\minimarket.local\dist\com_minimarket.zip` как **обновление существующего компонента**. Не удалять старую версию; не импортировать SQL руками.

## Проверка обновления после установки

1. В «Система → Управление → Расширения» убедиться, что `MiniMarket` версия `0.2.0`.
2. Joomla admin → «Компоненты → MiniMarket → Категории»: список открывается; нажать «Создать», заполнить **Тестовая категория**, статус **Опубликовано**, сохранить; вернуться к списку и открыть её ещё раз. Проверить что название/статус сохранились.
3. Joomla admin → «Компоненты → MiniMarket → Товары»: открывается пустой список с сообщением «редактор товаров добавим следующим обновлением». Это **ожидаемо**, а не ошибка.
4. Публично открыть `http://minimarket.local/index.php?option=com_minimarket`, проверить, что отображается «Тестовая категория», и что стандартный сайт Joomla /administrator продолжает работать.
5. В phpMyAdmin (только просмотр) проверить наличие таблиц `<prefix>minimarket_categories`, `<prefix>minimarket_products` в БД **minimarket**; префикс зависит от конфигурации Joomla. Не трогать Shop DB.
6. Повторную установку и сценарий сохранности данных при обновлении в тесте выполнить отдельно; нет предположения PASS заранее.

## Если ошибка

Не удалять компонент/БД и не откатывать через `git reset --hard`. Зафиксировать **краткую ошибку** в Joomla и этап сбоя: ZIP build / installer SQL / форма категорий / публичная страница. Дамп/пароли/PII не передавать. Ошибку исправлять в GitHub, а не в установленном `public_html`.

## Что дальше после PASS

**M1b:** полноценный редактор товаров с назначением категории, SKU, decimal price, остатком, публикацией, проверкой формы и формированием собственного route `/{alias}-{id}`. Позднее — иерархические категории, фильтр и SEO-лендинги, фотографии и характеристики, корзина/заказы/оплата.

Справка: https://manual.joomla.org/docs/next/building-extensions/install-update/installation/manifest/ ; https://manual.joomla.org/docs/next/building-extensions/components/component-development-tutorial/step08-higher-level-mvc/ .
