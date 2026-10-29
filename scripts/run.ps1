<#
Единая точка входа для локальных проверок minimarket.
Перед действием строго синхронизирует development с GitHub.
Безопасный результат сохраняет в logs/LAST_RUN.log и публикует в GitHub.
Запуск: powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\run.ps1 -Action preflight
Для будущих тестов: -Action test -TestFile scripts\test-shop.ps1
ВАЖНО: тестовые сценарии не должны печатать секреты. Известные значения .env дополнительно маскируются.
#>
param(
    [ValidateSet('preflight', 'test')]
    [string]$Action = 'preflight',
    [string]$TestFile = ''
)
$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$LogPath = Join-Path $Root 'logs\LAST_RUN.log'
$Started = (Get-Date).ToString('o')
$SourceSha = 'NOT_SYNCED'
$ExitCode = 1
$Status = 'FAIL'
$Details = New-Object System.Collections.Generic.List[string]
$Synced = $false

function Invoke-RepoGit {
    param([string[]]$GitArgs)
    # При ошибке Git запрещено продолжать на старых данных.
    $result = & git -C $Root @GitArgs 2>&1
    $code = $LASTEXITCODE
    if ($code -ne 0) {
        throw ('GIT_ERROR: git ' + ($GitArgs -join ' ') + '; exit=' + $code + '; ' + (($result | Out-String).Trim()))
    }
    return (($result | Out-String).Trim())
}

function Get-EnvKeys {
    param([string]$Path)
    $keys = @()
    foreach ($line in (Get-Content -LiteralPath $Path -Encoding UTF8)) {
        if ($line -match '^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=') { $keys += $Matches[1] }
    }
    return @($keys | Sort-Object -Unique)
}

function Protect-Log {
    param([string]$Value)
    # Удаляем из вывода все непустые значения .env длиннее пяти символов.
    # Это дополнительная страховка, а НЕ разрешение печатать токены в тестах.
    $protected = $Value
    $envFile = Join-Path $Root '.env'
    if (Test-Path -LiteralPath $envFile) {
        foreach ($line in (Get-Content -LiteralPath $envFile -Encoding UTF8)) {
            if ($line -match '^\s*[A-Za-z_][A-Za-z0-9_]*\s*=\s*(.*)$') {
                $v = $Matches[1].Trim().Trim('"').Trim("'")
                if ($v.Length -ge 6) {
                    $protected = [regex]::Replace($protected, [regex]::Escape($v), '[REDACTED]')
                }
            }
        }
    }
    return $protected
}

try {
    # Проверяем источник: нельзя незаметно подключиться к другому репозиторию.
    $remoteUrl = Invoke-RepoGit -GitArgs @('remote', 'get-url', 'origin')
    if ($remoteUrl -notmatch '(?i)github\.com[:/]komarovblog/minimarket(?:\.git)?$') {
        throw 'WRONG_ORIGIN: ожидался komarovblog/minimarket'
    }
    # Ранее не опубликованный LAST_RUN.log не должен навсегда блокировать следующую попытку.
    # Сохраняем его единственную резервную копию внутри .git, затем возвращаем
    # ТОЛЬКО служебный файл лога к HEAD, чтобы безопасно выполнить pull --ff-only.
    # Любые изменения кода, настроек или другие неотслеживаемые файлы — стоп.
    $dirtyText = Invoke-RepoGit -GitArgs @('status', '--porcelain', '--untracked-files=normal')
    # Invoke-RepoGit убирает начальные пробелы результата git, поэтому первая строка
    # porcelain status может начинаться с "M", а последующие — с " M".
    $dirtyLines = @(($dirtyText -split "`n") | ForEach-Object { $_.TrimEnd("`r") } | Where-Object { $_.Length -gt 0 })
    $otherDirty = @($dirtyLines | Where-Object { $_ -notin @('M logs/LAST_RUN.log', ' M logs/LAST_RUN.log') })
    if ($otherDirty.Count -gt 0) {
        throw ('WORKTREE_NOT_CLEAN: есть изменения вне служебного лога; количество=' + $otherDirty.Count)
    }
    if ($dirtyLines.Count -eq 1) {
        $backupDir = Invoke-RepoGit -GitArgs @('rev-parse', '--absolute-git-dir')
        $backupFile = Join-Path $backupDir 'MINIMARKET_LAST_UNPUBLISHED.log'
        Copy-Item -LiteralPath $LogPath -Destination $backupFile -Force -ErrorAction Stop
        # После гарантированного копирования разрешаем restore только для LAST_RUN.log.
        [void](Invoke-RepoGit -GitArgs @('restore', '--worktree', '--', 'logs/LAST_RUN.log'))
        $Details.Add('OLD_LOCAL_LOG_BACKUP: .git/MINIMARKET_LAST_UNPUBLISHED.log')
    }
    [void](Invoke-RepoGit -GitArgs @('fetch', '--prune', 'origin'))
    [void](Invoke-RepoGit -GitArgs @('show-ref', '--verify', 'refs/remotes/origin/development'))
    $branch = Invoke-RepoGit -GitArgs @('branch', '--show-current')
    if ($branch -ne 'development') {
        $exists = & git -C $Root show-ref --verify --quiet refs/heads/development
        if ($LASTEXITCODE -eq 0) {
            [void](Invoke-RepoGit -GitArgs @('switch', 'development'))
        } else {
            [void](Invoke-RepoGit -GitArgs @('switch', '-c', 'development', '--track', 'origin/development'))
        }
    }
    [void](Invoke-RepoGit -GitArgs @('pull', '--ff-only', 'origin', 'development'))
    $SourceSha = Invoke-RepoGit -GitArgs @('rev-parse', 'HEAD')
    $remoteSha = Invoke-RepoGit -GitArgs @('rev-parse', 'refs/remotes/origin/development')
    if ($SourceSha -ne $remoteSha) { throw 'SHA_MISMATCH: локальная и удалённая ветки расходятся' }
    $Synced = $true
    $Details.Add('SYNC_PASS: origin/development=' + $SourceSha)

    if ($Action -eq 'preflight') {
        # Проверяем наличие обязательных документов передачи контекста.
        foreach ($required in @('NEW_AGENT.md', 'CURRENT_TASK.md', 'docs/CONTEXT.md', 'docs/PLAN.md')) {
            if (-not (Test-Path -LiteralPath (Join-Path $Root $required))) { throw ('REQUIRED_DOC_MISSING: ' + $required) }
        }
        $Details.Add('DOC_STRUCTURE_PASS')
        $example = Join-Path $Root '.env.example'
        $envFile = Join-Path $Root '.env'
        if (-not (Test-Path -LiteralPath $example)) { throw 'ENV_EXAMPLE_MISSING' }
        if (-not (Test-Path -LiteralPath $envFile)) { throw 'ENV_MISSING: скопируйте .env.example в .env' }
        $expected = @(Get-EnvKeys $example)
        $actual = @(Get-EnvKeys $envFile)
        $difference = @(Compare-Object -ReferenceObject $expected -DifferenceObject $actual)
        if ($difference.Count -gt 0) {
            throw ('ENV_KEYS_MISMATCH: ' + (($difference | ForEach-Object { $_.InputObject + '(' + $_.SideIndicator + ')' }) -join ', '))
        }
        $Details.Add('ENV_KEYS_MATCH: ' + $expected.Count + ' ключей; значения не выводятся')
        $Details.Add('PREFLIGHT_PASS: синхронизация и структура конфигурации')
    } else {
        # Для публикации разрешены только тестовые .ps1 из каталога scripts.
        if ($TestFile -notmatch '^scripts[\\/](?:test-[A-Za-z0-9_.-]+|tests[\\/]test-[A-Za-z0-9_.-]+)\.ps1$') {
            throw 'INVALID_TEST_PATH: ожидается scripts/test-*.ps1'
        }
        $safeRelPath = $TestFile.Replace('/', '\')
        [void](Invoke-RepoGit -GitArgs @('ls-files', '--error-unmatch', '--', $safeRelPath.Replace('\', '/')))
        $fullTestFile = Join-Path $Root $safeRelPath
        if (-not (Test-Path -LiteralPath $fullTestFile)) { throw 'TEST_FILE_MISSING' }
        $Details.Add('TEST_FILE: ' + $safeRelPath)
        $output = & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $fullTestFile 2>&1
        $testExit = $LASTEXITCODE
        foreach ($item in $output) { $Details.Add($item.ToString()) }
        if ($testExit -ne 0) { throw ('TEST_FAILED: exit=' + $testExit) }
        $Details.Add('TEST_PASS')
    }
    $Status = 'PASS'
    $ExitCode = 0
} catch {
    $safeError = Protect-Log ([string]$_.Exception.Message)
    $Details.Add('ERROR: ' + $safeError)
    # Отображаем краткую причину на экране, даже если GitHub недоступен.
    Write-Host ('RUN_ERROR: ' + $safeError)
    $ExitCode = 1
} finally {
    # Лог должен быть записан в любом случае, включая ошибку проверки.
    $lines = @(
        'MINIMARKET_RUN',
        'Started: ' + $Started,
        'Finished: ' + (Get-Date).ToString('o'),
        'Action: ' + $Action,
        'Source_SHA: ' + $SourceSha,
        'Status: ' + $Status,
        'Exit_code: ' + $ExitCode,
        'Output:'
    ) + @($Details)
    $safeLog = Protect-Log ($lines -join [Environment]::NewLine)
    $logDir = Split-Path -Parent $LogPath
    if (-not (Test-Path -LiteralPath $logDir)) { New-Item -Path $logDir -ItemType Directory -Force | Out-Null }
    [System.IO.File]::WriteAllText($LogPath, $safeLog + [Environment]::NewLine, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host ('MINIMARKET_' + $Action.ToUpperInvariant() + '_' + $Status)
    if ($Synced) {
        # Единственный автоматически добавляемый в индекс файл — санитизированный лог.
        try {
            [void](Invoke-RepoGit -GitArgs @('add', '--', 'logs/LAST_RUN.log'))
            [void](Invoke-RepoGit -GitArgs @('commit', '-m', ('test: record ' + $Action + ' ' + $Status)))
            [void](Invoke-RepoGit -GitArgs @('push', 'origin', 'development'))
            [void](Invoke-RepoGit -GitArgs @('fetch', 'origin', 'development'))
            $localAfter = Invoke-RepoGit -GitArgs @('rev-parse', 'HEAD')
            $remoteAfter = Invoke-RepoGit -GitArgs @('rev-parse', 'refs/remotes/origin/development')
            if ($localAfter -ne $remoteAfter) { throw 'LOG_PUSH_SHA_MISMATCH' }
            Write-Host ('LOG_PUSH_PASS ' + $localAfter)
        } catch {
            Write-Host ('LOG_NOT_PUBLISHED: ' + $_.Exception.Message)
            $ExitCode = 1
        }
    } else {
        Write-Host 'LOG_LOCAL_ONLY: синхронизация не прошла; отправка в GitHub запрещена'
    }
}
exit $ExitCode
