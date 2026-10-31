# CURRENT_TASK — MiniMarket
Дата 2026-10-31. Только `komarovblog/minimarket` и `development`. `main`, Shop и Bidder не трогать.

## Подтверждено
- Пользователь сообщил «Гтово» после теста **0.3.0**: фронтенд каталог → категория → товар, плоские alias-ID URL и скрытые товары. Принято как **пользовательское подтверждение** без независимого HTTP readback.
- Один Joomla 6 компонент со своими категориями/товарами/заказами `com_minimarket`. **Никакого** магазина на Joomla Articles/двух вариантов. Open Server: `C:\OSPanel\home\minimarket.local`, `http://minimarket.local/`.

## Новый код опубликован, но ещё НЕ тестирован на локальном сайте
- **0.3.1 SEO canonical**, исходный коммит `dc070d1f3a4848d984c487583d90e93c6ea41901`: `Site/Helper/SeoHelper.php` формирует абсолютный canonical URL через Joomla Route API, проставляет его в `HtmlDocument` для catalogue/category/product, избегает дублей ранее выставленных head canonical; версию изменили в манифесте, `scripts/build.ps1` обновлён.
- **SQL/данные неизменны, новых 301 нет**. Встроенный Joomla System-SEF отвечает за `index.php` и trailing slash. Alias changes и несколько меню — следующие SEO задачи после проверки.
- Исходники/манифест/путь builder проверены статически. **Windows ZIP build, Joomla update и browser head canonical пока не проверены**. Нельзя заявлять PASS до пользователя.

## Единственный следующий шаг — обновление до 0.3.1 и canonical smoke
1. В PowerShell `C:\OSPanel\home\minimarket.local`: `git status --short`, затем при чистом состоянии `git pull --ff-only origin development` и `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1`.
2. Штатно загрузить `dist/com_minimarket.zip` поверх 0.3.0 через Joomla Extension Installer — **не удалять компонент**.
3. Проверить `/catalog`, категорию, товар; в исходнике каждой страницы **ровно один `rel="canonical"`** на правильный абсолютный красивый адрес. Техническая `index.php?option=com_minimarket&view=product&id=...` должна иметь canonical на SEF. Убедиться, что нет ошибок и админка работает.
4. Сценарий: `docs/M1D_CANONICAL_SMOKE.md`. При ошибке сообщить короткое наблюдение, без сырых логов/секретов.

## После PASS
Решить 301 для изменения псевдонимов товара, выбор единственного menu Itemid и проверку Strict System - SEF. Далее Schema.org Product/Offer и GA4 dataLayer, фото/характеристики, фильтры и корзина. Базу знаний Shop ↔ MiniMarket вести по `docs/SHARED_ENGINEERING_KB.md`, синхронизировать **только** этот документ, без чужого кода.

## Безопасность
Разрабатывать только в GitHub `src` → ZIP → Joomla installer; `main`, Joomla core, установленную папку `public_html` и чужие БД не трогать. Реальные данные/пароли/логи в GitHub не отправлять.
