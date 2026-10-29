# CURRENT_TASK — MiniMarket
Дата: 2026-10-29. Только `komarovblog/minimarket`, ветка `development`. Другой проект Shop/Symfony не трогать.

## Что подтверждено по локальным логам пользователя
- Open Server Panel **6.5.1**, проект `minimarket.local` **включён**.
- Apache **2.4.66**, PHP **8.5.4**, MySQL **8.0.45** — включены.
- Причина десятков ошибок прошлой диагностики: пользователь запускал **PowerShell-команды в CMD** из Open Server (видно по `C:\OSPanel>`). Команды `$root`, `Test-Path`, `try`, `Remove-Item` не выполнялись; `curl` ошибался из-за одинарных кавычек в CMD.
- Код `200 OK` получен для другого домена в том же Open Server, **не для MiniMarket**. Реальная доставка статического файла из `MiniMarket\public` ещё не проверена. Joomla ещё не установлена.

## Текущий шаг 2.1b — проверить HTTP/DocumentRoot MiniMarket в ПРАВИЛЬНОЙ оболочке
1. В консоли Open Server сначала выполнить `powershell`. Должно появиться приглашение вида `PS C:\OSPanel>`.
2. Затем в PowerShell выполнить **один** безопасный тест:
```powershell
$public = 'C:\Users\User\Desktop\WebProjects\MiniMarket\public'
if (-not (Test-Path -LiteralPath $public -PathType Container)) { throw 'PUBLIC_DIRECTORY_MISSING' }
$file = Join-Path $public ('__mm_probe_' + [guid]::NewGuid().ToString('N') + '.txt')
[System.IO.File]::WriteAllText($file, 'MINIMARKET_STATIC_OK')
try {
    curl.exe -4 --noproxy "*" -i --max-time 10 ('http://minimarket.local/' + (Split-Path -Leaf $file))
    'PROBE_CURL_EXIT=' + $LASTEXITCODE
    curl.exe -4 --noproxy "*" -sS --max-time 10 -o NUL -w 'ROOT_HTTP=%{http_code}' 'http://minimarket.local/'
    'ROOT_CURL_EXIT=' + $LASTEXITCODE
} finally {
    Remove-Item -LiteralPath $file -Force
}
```
Результат прислать текстом или скрином. Если файл доступен по HTTP 200 с ожидаемым текстом — можно готовить установку оригинальной Joomla 6. Ошибка 404/403/5xx означает необходимость проверки текущего DocumentRoot/журналов проекта по фактам, а не смены PHP наугад.

## Другой открытый этап
0.3/0.4: автоматическая публикация консольных логов Windows → GitHub ранее не подтверждена; проблема запуска PowerShell в CMD частично объясняет ошибки, но **сама по себе не доказывает**, что git push работает.

## Постоянные принципы
- Ни при каких условиях не изменять файлы или таблицы ядра Joomla; только официальные расширения.
- `public` и `.osp` остаются локальными и исключены из Git.
- Только MiniMarket, не обращаться к репозиторию Shop. Никаких reset/force-push или слияния в main.
