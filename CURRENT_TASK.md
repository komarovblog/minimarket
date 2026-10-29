# CURRENT_TASK — MiniMarket
Дата: 2026-10-29. Только `komarovblog/minimarket`, ветка `development`. Shop/Symfony не трогать.

## Завершено
Этап 2.1a — по подтверждению пользователя проект `minimarket.local` появился в Open Server Panel 6. Windows 11, Apache 2.4, PHP 8.5, MySQL 8.0, исходная папка `C:\Users\User\Desktop\WebProjects\MiniMarket`.

## Текущий шаг: 2.1b — диагностика HTTP без Joomla
При открытии `minimarket.local` Яндекс Браузер показывает «Страница недоступна. Сайт пока не может обработать этот запрос». На скриншоте HTTP-код не виден. Joomla в `public` ещё не установлена. **Не утверждать 500 без измерения.**

**Одно действие:** безопасная временная статическая проба `__mm_static_probe_20261029.txt` внутри `public`, HTTP-запрос `curl.exe` к файлу и к корню, затем удалить только тестовый файл, вывести код; попросить пользователя прислать точный результат (автоматические GitHub-логи пока не доказаны). Никаких изменений конфигурации OSP или ядра Joomla до данных.

PowerShell (выполнять целиком):
```powershell
$root = 'C:\Users\User\Desktop\WebProjects\MiniMarket'
$public = Join-Path $root 'public'
if (-not (Test-Path -LiteralPath $public -PathType Container)) { throw 'PUBLIC_DIRECTORY_MISSING' }
$probe = Join-Path $public '__mm_static_probe_20261029.txt'
if (Test-Path -LiteralPath $probe) { throw 'PROBE_ALREADY_EXISTS' }
[System.IO.File]::WriteAllText($probe, 'MINIMARKET_STATIC_OK', (New-Object System.Text.UTF8Encoding($false)))
try {
    curl.exe --noproxy '*' -sS --max-time 15 -i 'http://minimarket.local/__mm_static_probe_20261029.txt'
    'FILE_CURL_EXIT=' + $LASTEXITCODE
    curl.exe --noproxy '*' -sS --max-time 15 -o NUL -w 'ROOT_HTTP=%{http_code}' 'http://minimarket.local/'
    'ROOT_CURL_EXIT=' + $LASTEXITCODE
} finally {
    Remove-Item -LiteralPath $probe -Force
}
```
Ожидаемый файл при правильной настройке: `HTTP/1.1 200 OK` и `MINIMARKET_STATIC_OK`. Если он возвращает 404/403/500, посмотреть на фактическую версию Open Server и её параметр `public_dir` или `web_root` и журналы. Устанавливать Joomla только после подтверждения работы HTTP.

## Другой открытый блокер
Этап 0.3/0.4: публикация локальных тестовых логов в GitHub ещё не доказана. Не путать с текущей ошибкой локального веб-сайта.

## Обязательные принципы
Ядро Joomla не править, только штатные API. `public` и `.osp` не коммитить, содержимое Joomla держать отдельно. Репозиторий Shop на Symfony полностью изолирован. Без reset/force-push и без merge в main.
