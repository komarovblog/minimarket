# CURRENT_TASK — MiniMarket
Дата: 2026-10-30. Только `komarovblog/minimarket`, ветка `development`, не `main` и не Shop/Symfony.

## Главное — изменение проекта по решению пользователя
Пользователь отказался от MiniMarket Articles на штатных материалах Joomla и от **двух разных вариантов** магазина. Выбрана **ОДНА самостоятельная версия `com_minimarket` со своими категориями, товарами, комплектами, характеристиками и плоскими URL с ID**. Это оптимальный по общей сложности вариант для полноценного интернет-магазина, хотя первые CRUD/таблицы делать дольше, чем примитивную кнопку купить в статье.

**УСТАРЕЛИ:** `docs/VARIANTS_PROPOSAL.md` и `docs/EXTENSIONS_ARCHITECTURE.md` (история). **АКТУАЛЬНО:** `docs/COMPONENT_ARCHITECTURE.md`. Не создавать `plg_content_minimarket_articles` или `com_minimarket_catalog`, не дублировать торговые сервисы. Не нужно никаких template-specific торговых плагинов для Helix Ultimate; у нашего компонента свои Joomla MVC views/layouts. Дополнительные модули/интеграции SP Page Builder — только по необходимости.

## Условия
- Локальная Joomla 6 установлена и работает, SEF настроен пользователем, адрес `http://minimarket.local/`, каталог `C:\OSPanel\home\minimarket.local\public_html`.
- Исходный код только в GitHub `src/`, собранный ZIP устанавливаем стандартным Joomla Installer; работающую Joomla, БД/PII и configuration.php не коммитить и не редактировать ядро. Хостинг Apache/PHP/MySQL, без Docker.
- Сохраняются все согласованные правила: SEO-фильтр/ручные лендинги, Product/Offer Schema.org, dataLayer/ecommerce, Joomla auth с phone/ID, checkout, server-side price validation, заказы в своих таблицах, доставка, YooKassa, уведомления и минимальные отчёты.

## Единственный следующий шаг — M0
1. Создать **реально устанавливаемый Joomla 6 `com_minimarket`** с XML-манифестом, admin меню и простой страницей/параметрами, site view.
2. Реализовать `scripts/build.ps1` для ZIP в локальном `dist/` (игнорировать в Git), проверить сборку и установку на локальной Joomla, затем повторную установку/обновление без потери данных. Пока **НЕТ рабочего компонента/ZIP**, только `src/com_minimarket/README.md`.
3. M1: собственные таблицы категорий и товаров, админка в штатном стиле Joomla и frontend views/flat SEO routing. Не начинать с ненужных плагинов.
4. Далее безопасные вертикальные срезы SEO/filter/cart/orders/payment, тест на чистом Joomla экземпляре, Helix и другом шаблоне.

## Безопасность
`main` не трогать, Shop/Bidder не трогать. Не менять работающую Joomla вручную как источник правок. Не создавать миграции с удалением живых заказов/данных. Все логи/отчеты в GitHub по утверждённым правилам, без секретов.
