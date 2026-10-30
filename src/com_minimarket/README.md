# MiniMarket / com_minimarket — M0

Этот каталог — **исходники отдельного компонента Joomla 6**, а не содержимое установленной CMS. В M0 нет таблиц, товаров, заказов или интеграции с материалами Joomla. Версия манифеста: 0.1.0.

## Состав

- \`minimarket.xml\` — установочный манифест Joomla.
- \`admin/\` — административная страница, настройки и переводы.
- \`site/\` — представление каталога-заглушки и тип пункта меню Joomla.
- \`scripts/build.ps1\` в корне репозитория создаёт готовый ZIP.

## Сборка в Windows PowerShell

Из \`C:\\OSPanel\\home\\minimarket.local\`:

\`\`\`powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
\`\`\`

Архив: \`dist/com_minimarket.zip\`. Сборка не трогает файлы ядра Joomla или БД. \`dist/\` игнорируется Git.

## Установка

Joomla → Система → Установка → Расширения → Загрузка файла пакета → \`dist/com_minimarket.zip\`.

Проверка:
- Компоненты → MiniMarket — видна админ-страница.
- В её панели «Настройки» можно сохранить заголовок каталога.
- \`http://minimarket.local/index.php?option=com_minimarket\` — публичная страница с установленным заголовком.
- На сайте нет созданных товаров/категорий и не меняются Joomla Articles.

**Реальная установка/обновление на Joomla пока не проверены** — это следующий локальный smoke-test после публикации кода.
