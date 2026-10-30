# MiniMarket — схема расширений, Helix Ultimate и SP Page Builder

> **ИСТОРИЧЕСКИЙ ДОКУМЕНТ (вариант отменён 30.10.2026).** Пользователь выбрал **один самостоятельный компонент `com_minimarket` со своими категориями и товарами**, без каталога на материалах Joomla. Никаких двух пакетов Articles/Catalog. Актуальный план: [docs/COMPONENT_ARCHITECTURE.md](COMPONENT_ARCHITECTURE.md) и [CURRENT_TASK.md](../CURRENT_TASK.md). Материал ниже сохраняется только как история обсуждения.


**Статус:** архитектурное предложение для обсуждения, 30.10.2026. Рабочих компонентов пока нет; ничего в локальной Joomla не изменено. Только репозиторий `komarovblog/minimarket`, ветка `development`; не трогать `main`, Shop/Symfony.

## 1. Архитектурный принцип

**Один общий торговый движок MiniMarket + два взаимозаменяемых источника товаров + один универсальный контракт отображения.** Не делать две корзины, два расчёта цены и два платёжных webhook. Сами товары и их SEF-адреса различаются:
- **Articles:** Joomla `com_content` categories/articles/custom fields; ссылки из штатного роутера Joomla.
- **Catalog:** свои продукты, категории, комплекты, характеристики, фильтры и плоские адреса с ID (ранее согласованные).
- **Core:** Joomla users/guest checkout, серверные цены, корзина, защищённые заказы, доставка, YooKassa, OTP/ID login, уведомления, dataLayer, SEO-метаданные и SEO-лендинги.

Каталог Core видит только через контракт `CatalogProviderInterface`: `getProduct`, `listProducts`, `filter`, `getPriceAndAvailability`, `getAttributes`, `getMedia`, `getUrl`. Идентификаторы отличать namespace `article:{id}` и `catalog:{id}`, чтобы строка заказа и аналитика никогда не путали два источника. Это логический интерфейс, не обещание готовых PHP-классов.

## 2. Компоненты, плагины, модули

### Общие — оба варианта

| Расширение | Тип | Обязательность | Назначение |
|---|---|---|---|
| `com_minimarket` | **Компонент** | Да | Общие настройки, корзина, checkout, заказы (собственные закрытые таблицы), доставка, оплаты, личный кабинет, учетная запись Joomla, серверный API; единый торговый Core |
| `plg_system_minimarket` | **Системный плагин** | Запланирован для общего frontend | Контекстные Schema.org/canonical и общие JS/dataLayer там, где страница открыта через Joomla article либо родные представления; не изменять обычные статьи; не дублировать штатную Joomla Schema |
| `mod_minimarket_cart` | **Модуль** | Опционально (для шапки/позиции Helix/SPPB) | Счётчик и мини-корзина; полный экран корзины — в `com_minimarket` |
| `mod_minimarket_products` | **Модуль** | Опционально | Сетка/список продуктов через интерфейс адаптера, для позиций Helix и блока Joomla Module в SP Page Builder |
| `mod_minimarket_filter` | **Модуль** | Опционально | Панель фильтра для категории/SEO-лендинга; выборка, пагинация, SEO-правила — сервис в компоненте, не в модуле |

Не делать отдельных плагинов «для заказа», «для цены», «для dataLayer», «для каждой платёжной системы» без необходимости. Авторизация по телефону/ID, email/Telegram/MAX-уведомления и YooKassa сначала реализуются как **сервисы одного компонента**, отдельные Joomla плагины только при необходимости штатных событий/расширяемости. Сам `plg_system_minimarket` не обязателен на этапе первого устанавливаемого M0: сначала сделать работающий `com_minimarket`.

### Вариант A — MiniMarket Articles

| Расширение | Тип | Обязательность | Назначение |
|---|---|---|---|
| `plg_content_minimarket_articles` | **Контентный плагин** | Да | Распознать опубликованные материалы выбранных category IDs как товары, добавить безопасные блоки цены/характеристик/кнопки; ограничиться контекстом `com_content` и выбранными товарными категориями |
| `com_minimarket` | **Общий компонент** | Да | Настройка товарных категорий, поля, корзина/заказы, фильтр/SEO-лендинги, checkout; один и тот же, что у Catalog |
| `plg_system_minimarket` | **Общий плагин** | По мере вывода карточки/SEO | Product/Offer Schema.org, canonical, dataLayer на только товарных article URL и страницах Core, не вмешиваться в обычные Joomla статьи |
| `mod_minimarket_cart` | **Модуль** | По желанию | Мини-корзина в Helix-позиции или SPPB |
| `mod_minimarket_filter` | **Модуль** | По желанию | Виджет фильтра товаров Joomla |
| Макеты/оформление | **Layout/опциональный template add-on** | Зависит от дизайна | Полный товарный вид article и category, но не перезаписывать файлы произвольного шаблона |

Товарные поля Joomla и их группы создаёт идемпотентный установщик/настройщик пакета, не пользователь вручную. Не создавать новую копию Joomla `com_content`; наш плагин **расширяет** штатный компонент. Для первой версии SEO-лендинги — отдельно контролируемое представление `com_minimarket`, а обычные article/category URL — штатные Joomla.

### Вариант B — MiniMarket Catalog

| Расширение | Тип | Обязательность | Назначение |
|---|---|---|---|
| `com_minimarket_catalog` | **Дополнительный компонент каталога** | Да для B | CRUD собственных категорий, товаров, комплектов, характеристики/остатки/галереи, каталог/карточки, собственный плоский Joomla router с alias-ID, товары через NativeCatalogAdapter |
| `com_minimarket` | **Тот же общий компонент** | Да | Заказы, корзина, checkout, оплата, учётные записи, уведомления и все общие торговые сервисы |
| `plg_system_minimarket` | **Общий системный плагин** | По мере SEO/frontend | Контекстный Product/Offer, dataLayer и canonical для собственных товарных URL |
| `mod_minimarket_cart`, `mod_minimarket_filter`, `mod_minimarket_products` | **Общие модули** | Опционально | Те же виджеты, но данные из собственного каталога через другой provider |

**В B `plg_content_minimarket_articles` не нужен.** Штатные материалы Joomla остаются статьями. Полные HTML views собственного `com_minimarket_catalog` разрабатываем мы, поэтому визуально ими управлять существенно проще без переопределений `com_content`.

### Опциональная интеграция SP Page Builder/Helix

**Не делать плагин под каждый Joomla template.** Для Helix и большинства тем базовые торговые блоки — через Joomla content events/свои layouts/модули и свой CSS с ограниченными классами, аккуратно наследующий типографику.

SP Page Builder уже имеет Joomla Articles Dynamic Index/Detail pages с Custom Fields. Для встраивания блока MiniMarket сначала использовать штатный **Joomla Module addon** SP Page Builder, подключая `mod_minimarket_cart`, `mod_minimarket_products`, будущий точечный товарный блок. Лишь если этого недостаточно, разработать **отдельный опциональный интеграционный addon SP Page Builder** с собственной поддерживаемой установкой, проверив API на установленной версии; не объявлять его обязательной частью MiniMarket. Работа SPPB Dynamic Pages и их собственных URL/Itemid должна пройти отдельный SEO-регрессионный тест.

Источники: https://www.joomshaper.com/documentation/sp-page-builder/dynamic-content/joomla-native-articles-integration/creating-an-article-index-page ; https://www.joomshaper.com/documentation/sp-page-builder/dynamic-content/joomla-native-articles-integration/create-a-dynamic-article-details-page ; https://www.joomshaper.com/documentation/sp-page-builder/ .

## 3. Главный вопрос: как сделать смену шаблона безопасной

Joomla `com_content` override — это **файл конкретного шаблона** (например `templates/shaper_helixultimate/html/com_content/article/default.php`). У Helix Ultimate дополнительно есть системные overrides, например `plugins/system/helixultimate/overrides/com_content/article`, и свой способ их приоритетной замены: `templates/{template}/overrides/com_content/article`. Следовательно, нельзя обещать, что полная замена внешнего вида всех `com_content` карточек одинаково работает с любым шаблоном. **Это проблема оформления, не торговой логики.**

Три уровня совместимости:
1. **Универсальный базовый вывод:** цена, SKU, характеристики, кнопка купить, мини-корзина, Schema и dataLayer через свои plugin events/layouts/modules там, где реальное представление Joomla вызывает штатные события. Article URL остаётся штатным. Проверяем Helix и Cassiopeia. В чужих шаблонах может потребоваться одна настройка размещения, но никогда переписывание платежей.
2. **Helix-полное оформление:** опциональный пакет layouts/override-проекта или отдельный безопасно устанавливаемый Joomla template/child-style, распространяемый и обновляемый нашим исходным ZIP, **с явным выбором администратора**, без перезаписи чужих override файлов. Возможности Helix override проверить на локальной версии, включая plugin Helix framework. После смены Helix на другую тему оформление требует нового стиля/адаптации, но Core/каталог/заказы продолжают работать.
3. **SP Page Builder-оформление:** визуально строим динамическую карточку/список из Joomla Custom Fields плюс модули/блоки MiniMarket. Не гарантировать автоматически сохранение канонического com_content Itemid/маршрута при SPPB Dynamic Article Pages — проверить по конечному URL и canonical.

**Технический компромисс:** если требовать одновременно 100% независимую от темы полную карточку + гарантированно штатный `com_content` article URL, один универсальный plugin override без сотрудничества шаблона этого не обещает. На старте приоритет — **функциональность + штатные URL**, полное оформление — переносимые отдельно назначаемые layout/theme интеграции. Если приоритет станет «точно единое оформление в любом шаблоне», карточка может стать view нашего компонента (с отдельным маршрутом), но пользователь пока потребовал обычные Joomla URLs в A.

Источники: https://guide.joomla.org/user-guide/templates/templates-template-layouts ; https://www.joomshaper.com/documentation/helix-framework/helix-ultimate-2/template-views-override ; https://manual.joomla.org/docs/next/building-extensions/plugins/plugin-events/content/ .

## 4. Варианты ZIP и ограничения совместной установки

```text
pkg_minimarket_articles.zip
  ├─ com_minimarket.zip              ОБЩИЙ торговый компонент
  ├─ plg_content_minimarket_articles.zip
  ├─ plg_system_minimarket.zip       по мере необходимости
  ├─ mod_minimarket_cart.zip         по мере необходимости
  └─ mod_minimarket_filter.zip       по мере необходимости

pkg_minimarket_catalog.zip
  ├─ com_minimarket.zip              ТОТ ЖЕ исходный компонент
  ├─ com_minimarket_catalog.zip
  ├─ plg_system_minimarket.zip       по мере необходимости
  ├─ mod_minimarket_cart.zip         по мере необходимости
  └─ mod_minimarket_filter.zip       по мере необходимости
```

Joomla поддерживает `package` как комплект отдельных ZIP-компонентов, плагинов, модулей и шаблонов. В приведённой схеме **оба ZIP содержат один и тот же общий `com_minimarket`**, поэтому это именно два ВЗАИМОИСКЛЮЧАЮЩИХ дистрибутива — нельзя без проектирования управления shared dependencies устанавливать оба на один сайт или удалять один, ожидая, что второй сохранит общие компоненты. Если в будущем нужна установка A+B одновременно, тогда выделяем независимый `pkg_minimarket_core` и отдельные adapters с явными зависимостями/проверками; пока это ненужная сложность.

Официальная документация: https://manual.joomla.org/docs/next/building-extensions/install-update/installation/package/ .

## 5. Порядок разработки, чтобы ничего не пришлось переписывать

| Этап | Результат | Как проверяем |
|---|---|---|
| M0 | Реальный `com_minimarket`, manifest, базовые настройки, собранный ZIP | Установка/повторное обновление/удаление на Joomla 6, чистая установка |
| M1 | `plg_content_minimarket_articles`, выбор товарных категорий, автоматические Joomla Custom Fields, SKU/price/stock | Обычные статьи не меняются; товарный article отображает цену и кнопку в Cassiopeia/Helix |
| M2 | Универсальные layouts товара и категории, `mod_minimarket_cart`, базовый фильтр и SEO-страницы Articles | Штатные com_content URL, pagination, canonical, один источник Product Schema |
| M3 | Общий Core cart/checkout/заказы, серверные цены, доступы; dataLayer GA4 и schema | Нет лишних PII; purchase один раз; защита от подмены; не затрагивать живые платежи |
| M4 | Доставка, YooKassa (тестовая), Joomla login via phone/MAX/SMS/OAuth ID, email/TG/MAX; refunds | Тестовые сценарии, идемпотентные webhooks, возврат без риска реальных денег |
| M5 | `com_minimarket_catalog`: свои товары/комплекты, плоский alias-ID URL, SEO-фильтр | Те же общие cart/checkout/orders/dataLayer; без копирования логики |
| M6 | Опциональные SP Page Builder addons / Helix presentation kit и окончательные ZIP A/B | Один ZIP на чужой чистой Joomla, другая тема, обновление и корректный uninstall |

Это дорожная карта, а не обещание, что все части уже существуют. **Не разрабатывать полный функционал/плагины до проверки M0.** При этом не откладывать минимальный безопасный сквозной тест корзины до конца: M2/M3 могут выполняться короткими вертикальными срезами.

## 6. Нерушимые требования

- `Articles`: товары только в отмеченных категориях Joomla, остальные articles не меняются; стандартные Joomla SEF URL; группы полей создаёт пакет; шаблон не меняется самовольно.
- `Catalog`: собственный каталог; плоский URL с ID, без вложенной иерархии.
- Оба: SEO-фильтр с ручными индексируемыми лендингами и noindex/canonical для обычных сочетаний; один серверный расчёт цены, одни защищённые заказы в `#__minimarket_*`, общая auth/checkout/YooKassa/доставка/уведомления/dataLayer, JSON-LD Product/Offer без дублирования Article.
- Никакого редактирования/публикации Joomla core, живых БД или секретов; только GitHub `development` для своих исходников, воспроизводимая ZIP-сборка, тест на чистой Joomla.
