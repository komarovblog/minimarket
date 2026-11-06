# Публичный E2E MiniMarket (реальная Joomla)

Это **первый** (пока не полный) браузерный тест, выполняемый локально после установки Joomla. Он не меняет базу, не требует admin-login и намеренно проверяет URL демонстрационного товара `1012`.

## Предусловия

- Joomla 6 на `http://minimarket.local/` или настройте `MM_E2E_URL`.
- Установлен MiniMarket >=0.5.60, применён накопительный SQL >=0.5.59.
- Распакованы медиафайлы по `images/minimarket/sample-01.png`…`sample-20.png` и `demo-promo.mp4` в корень сайта Joomla.
- Есть Node.js, npm и доступ в интернет для первой установки Playwright/Chromium.

## Команды в PowerShell

```powershell
Set-Location 'C:\OSPanel\home\minimarket.local'
npm --prefix .\scripts\e2e install
npx --prefix .\scripts\e2e playwright install chromium
$env:MM_E2E_URL='http://minimarket.local/'
node .\scripts\e2e\storefront.mjs
```

Скрипт проверяет две оси SKU, смену артикула, недоступный цвет, загрузку изображений через HTTP, раскрытие промовидео кликом, наличие MP4, отключённую пока корзину и закрытие плеера. Любой сбой возвращает ненулевой код выхода; подробности печатает в консоль. Не публикует пароли и не изменяет каталог.

**Статус сейчас:** `NOT_RUN`, потому что нет прямого доступа к локальной Joomla в OpenServer. В CI проверяется синтаксис файла. После того как E2E будет выполнен локально, можно расширить до авторизованных административных форм (в изолированной тестовой БД и без сохранения секретов в Git) и настоящей Playwright установки Joomla в CI.

Не путать с `scripts/test-promo-video-interactions.js` — тот имитирует DOM в Node и выполняется в CI, но не заменяет браузерную приёмку.
