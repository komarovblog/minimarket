# Скрипты MiniMarket

Рабочий скрипт: \`build.ps1\` — из собственных исходников \`src/com_minimarket\` собирает архив \`dist/com_minimarket.zip\` для штатного менеджера расширений Joomla 6.

На Windows из корня репозитория:

\`\`\`powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1
\`\`\`

Скрипт не требует Docker и не изменяет Joomla Core, .env, конфигурацию или БД. Результат и локальные временные файлы не коммитятся. Если установка компонента не прошла, нужны экранная ошибка Joomla и отчёт проверки, а не принудительная очистка сайта.


После M1a (0.2.0) builder также проверяет наличие SQL-файлов install/update и формы категорий. Перед обновлением локальной Joomla с SQL-миграцией рекомендуется экспортировать отдельную БД `minimarket` в приватный локальный архив. Инструкция: [M1a smoke](../docs/M1A_CATEGORIES_SMOKE.md).
