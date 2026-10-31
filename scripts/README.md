# Скрипты MiniMarket

Рабочий скрипт: \`build.ps1\` — из собственных исходников \`src/com_minimarket\` собирает архив \`dist/com_minimarket.zip\` для штатного менеджера расширений Joomla 6.

На Windows из корня репозитория:

\`\`\`powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
\`\`\`

Скрипт не требует Docker и не изменяет Joomla Core, .env, конфигурацию или БД. Результат и локальные временные файлы не коммитятся. Если установка компонента не прошла, нужны экранная ошибка Joomla и отчёт проверки, а не принудительная очистка сайта.


После M1a (0.2.0) builder также проверяет наличие SQL-файлов install/update и формы категорий. Перед обновлением локальной Joomla с SQL-миграцией рекомендуется экспортировать отдельную БД `minimarket` в приватный локальный архив. Инструкция: [M1a smoke](../docs/M1A_CATEGORIES_SMOKE.md).


В 0.2.1 добавлена проверка product.xml и MVC файлов редактора товаров. SQL обновлений не менялся, каталог и товары используют уже созданные таблицы из 0.2.0. Проверка: [M1b smoke](../docs/M1B_PRODUCT_EDITOR_SMOKE.md).


Версия 0.3.0 добавляет site-router, helper, repository и публичные представления категорий/товаров. `build.ps1` проверяет их наличие при сборке. [Чек-лист M1c](../docs/M1C_FRONTEND_ROUTING_SMOKE.md).


## Диагностика ZIP при ошибке Joomla
Запустить `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\inspect-package.ps1`. Проверяется **существующий** `dist/com_minimarket.zip`: верхний `minimarket.xml` (`<extension type="component">`), версия и обязательные файлы. Выдаёт один короткий `PACKAGE_OK` или `PACKAGE_FAIL`. Это не проверка установки Joomla. Подробности: [docs/INSTALL_ZIP_DIAGNOSTIC.md](../docs/INSTALL_ZIP_DIAGNOSTIC.md).
