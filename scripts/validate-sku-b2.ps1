# MiniMarket SKU B2+ source/package validation on Open Server Windows.
# Pure source checks: no Joomla database, no schema changes, no real orders.
# Publish allowlisted PASS/FAIL statuses to GitHub development, no raw logs.
[CmdletBinding()]
param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php = 'C:\OSPanel\modules\PHP-8.5\php.exe'
$nodeInfo = Get-Command node -ErrorAction SilentlyContinue
$runtime = Join-Path $root 'runtime'
[void](New-Item -ItemType Directory -Force -Path $runtime)
$rawLog = Join-Path $runtime 'sku-b2-validation-local.log'
$null = [IO.File]::WriteAllText($rawLog, '', [Text.UTF8Encoding]::new($false))
$branch = ((& git -C $root branch --show-current) | Out-String).Trim()
if ($branch -ne 'development') { throw 'MINIMARKET_VALIDATION_DEVELOPMENT_ONLY' }
$sourceSha = ((& git -C $root rev-parse HEAD) | Out-String).Trim()
$manifestPath = Join-Path $root 'src\com_minimarket\minimarket.xml'
[xml]$manifest = Get-Content -LiteralPath $manifestPath -Raw -Encoding UTF8
$releaseVersion = [string]$manifest.extension.version
try { $parsedVersion = [version]::Parse($releaseVersion) }
catch { throw 'MINIMARKET_VALIDATION_INVALID_RELEASE' }
if ($parsedVersion -lt [version]'0.5.24') {
    throw 'MINIMARKET_VALIDATION_UNSUPPORTED_RELEASE'
}
$checks = [ordered]@{
    sku_position_source = 'NOT_RUN'
    existing_sku_regressions = 'NOT_RUN'
    native_joomla_ui = 'NOT_RUN'
    php_field_syntax = 'NOT_RUN'
    js_syntax = 'NOT_RUN'
    package_build = 'NOT_RUN'
    package_inspection = 'NOT_RUN'
}
function Run-Check([string]$label,[string]$exe,[string[]]$arguments) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $lines = @(& $exe @arguments 2>&1 | ForEach-Object { [string]$_ })
        $code = [int]$LASTEXITCODE
    } catch {
        $lines = @('LOCAL_CHECK_EXCEPTION')
        $code = 1
    } finally {
        $ErrorActionPreference = $previous
    }
    [IO.File]::AppendAllText($rawLog,
        ('--- ' + $label + ' ---' + [Environment]::NewLine +
         ($lines -join [Environment]::NewLine) + [Environment]::NewLine),
        [Text.UTF8Encoding]::new($false))
    if ($code -eq 0) { return 'PASS' }
    return 'FAIL'
}
if (Test-Path -LiteralPath $php -PathType Leaf) {
    $checks.sku_position_source = Run-Check 'sku_position_source' $php @(
        (Join-Path $root 'scripts\test-sku-position-editor.php'))
    $checks.existing_sku_regressions = Run-Check 'existing_sku_regressions' $php @(
        (Join-Path $root 'scripts\test-product-variants.php'))
    $checks.native_joomla_ui = Run-Check 'native_joomla_ui' $php @(
        (Join-Path $root 'scripts\test-native-ui-consistency.php'))
    $checks.php_field_syntax = Run-Check 'php_field_syntax' $php @(
        '-l', (Join-Path $root 'src\com_minimarket\admin\src\Field\VariantbuilderField.php'))
}
if ($null -ne $nodeInfo) {
    $checks.js_syntax = Run-Check 'js_syntax' ([string]$nodeInfo.Source) @(
        '--check', (Join-Path $root 'src\com_minimarket\media\js\variants-local.js'))
}
$ps = (Get-Command powershell.exe -ErrorAction SilentlyContinue)
if ($null -ne $ps) {
    $checks.package_build = Run-Check 'package_build' ([string]$ps.Source) @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', (Join-Path $root 'scripts\build.ps1'))
    if ($checks.package_build -eq 'PASS') {
        $checks.package_inspection = Run-Check 'package_inspection' ([string]$ps.Source) @(
            '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', (Join-Path $root 'scripts\inspect-package.ps1'))
    }
}
$overall = if (@($checks.Values | Where-Object { $_ -ne 'PASS' }).Count -eq 0) {
    'PASS'
} else { 'FAIL' }
$report = [ordered]@{
    schema_version = 1
    project = 'MiniMarket'
    version = $releaseVersion
    source_commit = $sourceSha
    timestamp_utc = [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ')
    result = $overall
    checks = $checks
    joomla_browser_smoke = 'NOT_RUN'
    mysql_schema_changed = $false
    raw_log_uploaded = $false
}
$json = (ConvertTo-Json -InputObject $report -Depth 6) + [Environment]::NewLine
$work = Join-Path ([IO.Path]::GetTempPath()) ('mm-b2-report-' + [guid]::NewGuid().ToString('N'))
$attached = $false
try {
    & git -C $root fetch --no-tags origin development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_FETCH_FAILED' }
    & git -C $root worktree add --detach $work origin/development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_WORKTREE_FAILED' }
    $attached = $true
    $logs = Join-Path $work 'logs'
    [void](New-Item -ItemType Directory -Path $logs -Force)
    $latest = Join-Path $logs 'sku-b2-latest.json'
    [IO.File]::WriteAllText($latest,$json,[Text.UTF8Encoding]::new($false))
    & git -C $work add -- logs/sku-b2-latest.json *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_STAGE_FAILED' }
    & git -C $work -c user.name=MiniMarket-Diagnostics -c user.email=diagnostics@local.invalid commit -m 'test(minimarket): publish sanitized SKU B2.1 validation status' *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_COMMIT_FAILED' }
    & git -C $work push origin HEAD:refs/heads/development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_PUSH_FAILED' }
} finally {
    if ($attached) {
        & git -C $root worktree remove --force $work *> $null
    }
    if (Test-Path -LiteralPath $work) {
        Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
    }
}
Write-Host ('MINIMARKET_SKU_B2_REPORTED|' + $overall)
if ($overall -ne 'PASS') { exit 1 }
 -or
    [version]$releaseVersion -lt [version]'0.5.24') {
    throw 'MINIMARKET_VALIDATION_UNSUPPORTED_RELEASE'
}
$checks = [ordered]@{
    sku_position_source = 'NOT_RUN'
    existing_sku_regressions = 'NOT_RUN'
    native_joomla_ui = 'NOT_RUN'
    php_field_syntax = 'NOT_RUN'
    js_syntax = 'NOT_RUN'
    package_build = 'NOT_RUN'
    package_inspection = 'NOT_RUN'
}
function Run-Check([string]$label,[string]$exe,[string[]]$arguments) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $lines = @(& $exe @arguments 2>&1 | ForEach-Object { [string]$_ })
        $code = [int]$LASTEXITCODE
    } catch {
        $lines = @('LOCAL_CHECK_EXCEPTION')
        $code = 1
    } finally {
        $ErrorActionPreference = $previous
    }
    [IO.File]::AppendAllText($rawLog,
        ('--- ' + $label + ' ---' + [Environment]::NewLine +
         ($lines -join [Environment]::NewLine) + [Environment]::NewLine),
        [Text.UTF8Encoding]::new($false))
    if ($code -eq 0) { return 'PASS' }
    return 'FAIL'
}
if (Test-Path -LiteralPath $php -PathType Leaf) {
    $checks.sku_position_source = Run-Check 'sku_position_source' $php @(
        (Join-Path $root 'scripts\test-sku-position-editor.php'))
    $checks.existing_sku_regressions = Run-Check 'existing_sku_regressions' $php @(
        (Join-Path $root 'scripts\test-product-variants.php'))
    $checks.native_joomla_ui = Run-Check 'native_joomla_ui' $php @(
        (Join-Path $root 'scripts\test-native-ui-consistency.php'))
    $checks.php_field_syntax = Run-Check 'php_field_syntax' $php @(
        '-l', (Join-Path $root 'src\com_minimarket\admin\src\Field\VariantbuilderField.php'))
}
if ($null -ne $nodeInfo) {
    $checks.js_syntax = Run-Check 'js_syntax' ([string]$nodeInfo.Source) @(
        '--check', (Join-Path $root 'src\com_minimarket\media\js\variants-local.js'))
}
$ps = (Get-Command powershell.exe -ErrorAction SilentlyContinue)
if ($null -ne $ps) {
    $checks.package_build = Run-Check 'package_build' ([string]$ps.Source) @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', (Join-Path $root 'scripts\build.ps1'))
    if ($checks.package_build -eq 'PASS') {
        $checks.package_inspection = Run-Check 'package_inspection' ([string]$ps.Source) @(
            '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', (Join-Path $root 'scripts\inspect-package.ps1'))
    }
}
$overall = if (@($checks.Values | Where-Object { $_ -ne 'PASS' }).Count -eq 0) {
    'PASS'
} else { 'FAIL' }
$report = [ordered]@{
    schema_version = 1
    project = 'MiniMarket'
    version = '0.5.24'
    source_commit = $sourceSha
    timestamp_utc = [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ')
    result = $overall
    checks = $checks
    joomla_browser_smoke = 'NOT_RUN'
    mysql_schema_changed = $false
    raw_log_uploaded = $false
}
$json = (ConvertTo-Json -InputObject $report -Depth 6) + [Environment]::NewLine
$work = Join-Path ([IO.Path]::GetTempPath()) ('mm-b2-report-' + [guid]::NewGuid().ToString('N'))
$attached = $false
try {
    & git -C $root fetch --no-tags origin development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_FETCH_FAILED' }
    & git -C $root worktree add --detach $work origin/development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_WORKTREE_FAILED' }
    $attached = $true
    $logs = Join-Path $work 'logs'
    [void](New-Item -ItemType Directory -Path $logs -Force)
    $latest = Join-Path $logs 'sku-b2-latest.json'
    [IO.File]::WriteAllText($latest,$json,[Text.UTF8Encoding]::new($false))
    & git -C $work add -- logs/sku-b2-latest.json *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_STAGE_FAILED' }
    & git -C $work -c user.name=MiniMarket-Diagnostics -c user.email=diagnostics@local.invalid commit -m 'test(minimarket): publish sanitized SKU B2.1 validation status' *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_COMMIT_FAILED' }
    & git -C $work push origin HEAD:refs/heads/development *> $null
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_REPORT_PUSH_FAILED' }
} finally {
    if ($attached) {
        & git -C $root worktree remove --force $work *> $null
    }
    if (Test-Path -LiteralPath $work) {
        Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
    }
}
Write-Host ('MINIMARKET_SKU_B2_REPORTED|' + $overall)
if ($overall -ne 'PASS') { exit 1 }
