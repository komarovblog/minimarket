# CURRENT_TASK — единственная активная задача

Актуально: 2026-10-29.
Текущий этап: **0.4 — первый локальный запуск preflight**.
Статус: **ожидается выполнение на Windows; проверок PowerShell ещё не было**.

## Что уже готово
- `development` на GitHub, `main` не менялась.
- Корневой `NEW_AGENT.md`, `docs/CONTEXT.md`, `docs/PLAN.md`.
- Шаблон `.env.example`, `.gitignore`, предварительный `scripts/run.ps1`.
- Механизм лога в фиксированном `logs/LAST_RUN.log`. Пока нет подтверждённого локального выполнения.

## Следующее действие (одно)
На Windows в PowerShell: клонировать **только `development`** в удобный каталог, создать `.env` из `.env.example` и запустить:
```powershell
git clone --branch development https://github.com/komarovblog/minimarket.git
Set-Location .\minimarket
Copy-Item .env.example .env
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\run.ps1 -Action preflight
```
Если проект уже клонирован: не клонировать поверх него. Проверить удалённый URL и состояние Git, затем переключиться на `development` безопасно. Не выполнять `reset --hard`.

## Критерии готовности
1. Локальная ветка: `development`; удалённая — `origin/development`; SHA совпадают после запуска (лог создаёт собственный коммит).
2. `logs/LAST_RUN.log` присутствует в GitHub, содержит результат `preflight`, код завершения, исходный SHA и диагностический вывод без секретов.
3. `.env` отсутствует в Git, ключи совпадают с `.env.example`.
4. После ответа «Готово» ассистент сам читает `logs/LAST_RUN.log` и последние коммиты, а затем переводит план на следующий проверенный подшаг.

## Известные риски
- Скрипт создан, но ещё не тестировался в Windows PowerShell.
- Пустой до начала работы репозиторий не содержит Joomla и торгового кода: `preflight` не является функциональным тестом магазина.
- Если GitHub или push недоступен, лог остаётся локально и скрипт сообщает об ошибке; автоматического гарантированного облачного сохранения без связи не существует.
