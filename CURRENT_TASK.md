# CURRENT_TASK — MiniMarket
Актуально 2026-10-30. Только `komarovblog/minimarket` `development`; main и Shop/Symfony не трогать.

## Состояние
- Joomla 6 локально установлена и работает, SEF настроен со слов пользователя. Путь `C:\OSPanel\home\minimarket.local\public_html`; исходники нашего расширения — отдельный GitHub `src` (пока README, работающего компонента ещё нет).
- Утверждено пользователем: два варианта товара/каталога (A Joomla articles + fields/categories и штатные URL, B собственный каталог + плоские ID URL); общее оформление заказа, оплаты, доставки, auth, уведомлений, SEO-фильтра, Schema.org и dataLayer.
- Пользователь уточнил, что использует **Helix Ultimate и SP Page Builder**, хочет сменяемость шаблона и просит список необходимых расширений с общим кодом вместо двух независимых магазинов.

## Проектная архитектура для обсуждения
Создан `docs/EXTENSIONS_ARCHITECTURE.md`. Предлагается:
- **Общий `com_minimarket`** для корзины, заказов, checkout, payment, auth, settings.
- A: `plg_content_minimarket_articles` подключает `com_content` категории/поля и добавляет блоки товара; стандартные Joomla article/category URL сохраняются.
- B: собственный `com_minimarket_catalog` для каталога и плоских URL, используя все общие сервисы Core.
- Общий `plg_system_minimarket` для контекстного Schema.org/dataLayer/meta (когда дойдём до SEO/analytical этапа); опциональные Joomla modules `mod_minimarket_cart`, `mod_minimarket_filter`, `mod_minimarket_products`.
- Не требуются отдельные торговые плагины для каждого шаблона. Торговые блоки независимы от темы через Joomla events/modules/layouts. **Полное com_content оформление template-specific**; Helix имеет собственные overrides, SPPB — Dynamic Joomla Articles + Custom Fields/Module addon. Их полное оформление — опциональная интеграция с явным назначением. Не обещать, что полные overrides переживают смену темы без настройки.
- Два будущих ZIP `pkg_minimarket_articles.zip` и `pkg_minimarket_catalog.zip`, каждый со своим адаптером плюс тем же `com_minimarket`; считать их взаимно исключающими на одном сайте до проработки shared-package dependencies. Пока ZIP ещё нет.

## Следующий исполняемый шаг
**M0:** создать в `src/com_minimarket` настоящий минимальный компонент Joomla 6 + XML-манифест и `scripts/build.ps1`; собрать ZIP, установить штатно на локальной Joomla и повторить установку на чистой Joomla. Не пытаться создавать сразу десяток модулей/плагинов или править шаблон Helix вручную.

После M0 — M1 с выбором товарных Joomla категорий, полями и минимальным `plg_content_minimarket_articles`, проверкой реальной страницы в Cassiopeia/Helix. Затем SEO/filter, common cart/orders и B.

## Ограничения
Только GitHub `development`, не изменять Joomla core, `public_html` как первичный код, чужой Shop/Symfony, main. Не считать архитектурный документ уже реализованным кодом. Установленные данные и секреты не отправлять в Git.
