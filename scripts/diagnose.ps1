<#
Аварийная диагностика MiniMarket. Не запускает основной скрипт.
Проверяет только безопасные признаки локального репозитория.
Публикует результат из временного чистого клона GitHub.
Значения .env, исходники и персональные данные в лог НЕ попадают.
#>
param([string]$RepoPath = (Get-Location).Path)
$ErrorActionPreference = 'Stop'
$Report = New-Object 'System.Collections.Generic.List[string]'
$Report.Add('MINIMARKET_DIAGNOSTIC_V1')
$Report.Add('UTC=' + [DateTime]::UtcNow.ToString('o'))
$Report.Add('POWERSHELL=' + $PSVersionTable.PSVersion.ToString())
$TempRepo = $null

function Read-Git {
    param([string]$Directory, [string[]]$GitArgs)
    $old = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $output = @(& git -C $Directory @GitArgs 2>$null)
        $exitCode = $LASTEXITCODE
        return [pscustomobject]@{ OK = ($exitCode -eq 0); Code = $exitCode; Data = (($output | Out-String).Trim()) }
    } catch {
        return [pscustomobject]@{ OK = $false; Code = 999; Data = '' }
    } finally {
        $ErrorActionPreference = $old
    }
}
function Add-Result {
    param([string]$Key, [object]$Value)
    $Report.Add($Key + '=' + [string]$Value)
}
function Get-KeyNames {
    param([string]$File)
    $keys = @()
    if (Test-Path -LiteralPath $File) {
        foreach ($line in (Get-Content -LiteralPath $File -Encoding UTF8)) {
            if ($line -match '^\s*([a-zA-Z_][a-zA-Z_0-9]*)\s*=') { $keys += $Matches[1] }
        }
    }
    return @($keys | Sort-Object -Unique)
}
try {
    $repoCheck = Read-Git -Directory $RepoPath -GitArgs @('rev-parse', '--show-toplevel')
    Add-Result 'LOCAL_REPO_VALID' $repoCheck.OK
    if ($repoCheck.OK) {
        $origin = Read-Git -Directory $RepoPath -GitArgs @('remote', 'get-url', 'origin')
        $goodOrigin = $origin.OK -and ($origin.Data -match '(?i)github\.com[:/]komarovblog/minimarket(?:\.git)?$')
        Add-Result 'ORIGIN_MATCH' $goodOrigin
        $branch = Read-Git -Directory $RepoPath -GitArgs @('branch', '--show-current')
        Add-Result 'BRANCH_IS_DEVELOPMENT' ($branch.OK -and $branch.Data -eq 'development')
        $localHead = Read-Git -Directory $RepoPath -GitArgs @('rev-parse', 'HEAD')
        Add-Result 'LOCAL_SHA' $(if ($localHead.OK -and $localHead.Data -match '^[a-f0-9]{40}$') {$localHead.Data} else {'UNKNOWN'})
        $fetched = Read-Git -Directory $RepoPath -GitArgs @('rev-parse', 'refs/remotes/origin/development')
        Add-Result 'LOCAL_REMOTE_SHA' $(if ($fetched.OK -and $fetched.Data -match '^[a-f0-9]{40}$') {$fetched.Data} else {'UNKNOWN'})
        $status = Read-Git -Directory $RepoPath -GitArgs @('status', '--porcelain')
        Add-Result 'GIT_STATUS_OK' $status.OK
        if ($status.OK) {
            $lines = @($status.Data -split [char]10 | Where-Object { $_.Length -gt 0 })
            Add-Result 'CHANGED_FILE_COUNT' $lines.Count
            $notLog = @($lines | Where-Object {
                $value = $_.Trim()
                $value.Length -gt 0 -and $value -ne 'M logs/LAST_RUN.log'
            })
            Add-Result 'ONLY_LOG_CHANGED' ($lines.Count -gt 0 -and $notLog.Count -eq 0)
        }
        $example = Join-Path $RepoPath '.env.example'
        $envFile = Join-Path $RepoPath '.env'
        Add-Result 'ENV_EXISTS' (Test-Path -LiteralPath $envFile)
        Add-Result 'ENV_EXAMPLE_EXISTS' (Test-Path -LiteralPath $example)
        if ((Test-Path -LiteralPath $envFile) -and (Test-Path -LiteralPath $example)) {
            $expected = @(Get-KeyNames $example)
            $actual = @(Get-KeyNames $envFile)
            Add-Result 'ENV_KEYSET_MATCH' (@(Compare-Object $expected $actual).Count -eq 0)
        }
        $runner = Join-Path $RepoPath 'scripts\run.ps1'
        Add-Result 'RUNNER_EXISTS' (Test-Path -LiteralPath $runner)
        if (Test-Path -LiteralPath $runner) {
            $tokens = $null
            $errors = $null
            [System.Management.Automation.Language.Parser]::ParseFile($runner, [ref]$tokens, [ref]$errors) | Out-Null
            Add-Result 'RUNNER_PARSE_ERROR_COUNT' @($errors).Count
            if (@($errors).Count -gt 0) { Add-Result 'FIRST_PARSE_ERROR_LINE' $errors[0].Extent.StartLineNumber }
        }
    }
} catch {
    Add-Result 'DIAGNOSTIC_ERROR_CLASS' $_.Exception.GetType().Name
}
try {
    $TempRepo = Join-Path ([System.IO.Path]::GetTempPath()) ('mm-diag-' + [guid]::NewGuid().ToString('N'))
    $old = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        & git clone --quiet --depth 1 --single-branch --branch development 'https://github.com/komarovblog/minimarket.git' $TempRepo 2>$null | Out-Null
        $cloneExit = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $old
    }
    if ($cloneExit -ne 0) { throw 'CLONE_FAILED' }
    $remoteSha = Read-Git -Directory $TempRepo -GitArgs @('rev-parse', 'HEAD')
    Add-Result 'REMOTE_SHA' $(if ($remoteSha.OK) {$remoteSha.Data} else {'UNKNOWN'})
    $Report.Add('END=1')
    $target = Join-Path $TempRepo 'logs\LAST_RUN.log'
    [System.IO.File]::WriteAllLines($target, [string[]]$Report.ToArray(), (New-Object System.Text.UTF8Encoding($false)))
    foreach ($args in @(
        @('config','user.name','MiniMarket Diagnostics'),
        @('config','user.email','minimarket-diagnostics@users.noreply.github.com'),
        @('add','--','logs/LAST_RUN.log'),
        @('commit','-m','test: isolated diagnostics'),
        @('push','origin','HEAD:development')
    )) {
        $result = Read-Git -Directory $TempRepo -GitArgs ([string[]]$args)
        if (-not $result.OK) { throw ('UPLOAD_GIT_STEP_FAILED_' + $args[0] + '_' + $result.Code) }
    }
    $uploaded = Read-Git -Directory $TempRepo -GitArgs @('rev-parse','HEAD')
    Write-Host ('MINIMARKET_DIAGNOSTIC_UPLOADED ' + $uploaded.Data)
} catch {
    $fallback = Join-Path ([System.IO.Path]::GetTempPath()) 'minimarket-diagnostic-failed.log'
    $Report.Add('UPLOAD_FAILURE=' + $_.Exception.Message)
    [System.IO.File]::WriteAllLines($fallback, [string[]]$Report.ToArray(), (New-Object System.Text.UTF8Encoding($false)))
    Write-Host ('MINIMARKET_DIAGNOSTIC_NOT_UPLOADED ' + $_.Exception.Message)
    Write-Host 'SAFE_REPORT=minimarket-diagnostic-failed.log in Windows TEMP'
    exit 1
} finally {
    if ($TempRepo -and (Test-Path -LiteralPath $TempRepo)) {
        try { Remove-Item -LiteralPath $TempRepo -Recurse -Force -ErrorAction Stop }
        catch { Write-Host 'TEMP_CLEANUP_FAILED' }
    }
}