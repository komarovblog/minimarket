param(
    [string]$JoomlaConfigPath = '',
    [switch]$Apply
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Retire obsolete SQL *files*, not database rows/columns. No MySQL writes.
$root = Split-Path -Parent $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($JoomlaConfigPath)) {
    $JoomlaConfigPath = Join-Path $root 'public_html\configuration.php'
}
if (-not (Test-Path -LiteralPath $JoomlaConfigPath -PathType Leaf)) {
    throw "MM73_JOOMLA_CONFIGURATION_NOT_FOUND: $JoomlaConfigPath"
}
$siteRoot = Split-Path -Parent ([System.IO.Path]::GetFullPath($JoomlaConfigPath))
$historyDir = Join-Path $siteRoot 'administrator\components\com_minimarket\sql\updates\mysql'
if (-not (Test-Path -LiteralPath $historyDir -PathType Container)) {
    throw "MM73_JOOMLA_HISTORY_NOT_FOUND: $historyDir"
}
$expected = Join-Path $historyDir '0.5.73.sql'
if (-not (Test-Path -LiteralPath $expected -PathType Leaf)) {
    throw 'MM73_SCHEMA_BASELINE_FILE_MISSING: 0.5.73.sql'
}

$phpCandidates = @()
$localPhp = Get-Command php -ErrorAction SilentlyContinue
if ($null -ne $localPhp) { $phpCandidates += $localPhp.Source }
$phpCandidates += @(
    'C:\OSPanel\modules\PHP-8.5\php.exe',
    'C:\OSPanel\modules\PHP-8.4\php.exe',
    'C:\OSPanel\modules\PHP-8.3\php.exe'
)
$phpPath = $null
foreach ($candidate in $phpCandidates) {
    if ($candidate -and (Test-Path -LiteralPath $candidate -PathType Leaf)) {
        $phpPath = $candidate
        break
    }
}
if (-not $phpPath) {
    throw 'MM73_PHP_CLI_NOT_FOUND_FOR_DATABASE_VERIFICATION'
}
$validator = Join-Path $PSScriptRoot 'verify-installed-canonical-073.php'
if (-not (Test-Path -LiteralPath $validator -PathType Leaf)) {
    throw 'MM73_CANONICAL_VERIFIER_NOT_FOUND'
}
& $phpPath $validator $JoomlaConfigPath
if ($LASTEXITCODE -ne 0) {
    throw 'MM73_CANONICAL_DATABASE_VERIFICATION_FAILED: NO FILES CHANGED'
}

$files = @(Get-ChildItem -LiteralPath $historyDir -Filter '*.sql' -File)
if ($files.Count -lt 1) {
    throw 'MM73_NO_INSTALLED_HISTORY_FILES'
}
$future = @($files | Where-Object {
    try { [version]$_.BaseName -gt [version]'0.5.73' }
    catch { $true }
})
if ($future.Count -gt 0) {
    throw ('MM73_FUTURE_OR_UNKNOWN_SQL_FILES: ' + ($future.Name -join ', '))
}

$remaining = @($files | Where-Object { $_.Name -ne '0.5.73.sql' })
$marker = @'
-- MiniMarket 0.5.73 canonical baseline (Joomla Database Maintenance only).
-- Older Joomla installer migrations were backed up outside SQL update scans.
-- Do not use this file to migrate databases older than 0.5.73.
SELECT 1;
'@
if (-not $Apply) {
    Write-Output "MM73_HISTORY_CHECK_OK: $($files.Count) SQL files; $($remaining.Count) old files."
    Write-Output 'To archive installed legacy update files: rerun with -Apply.'
    Write-Output 'No files or database tables changed.'
    exit 0
}

$backupBase = Join-Path $root 'dist\schema-history-backup'
$stamp = (Get-Date).ToUniversalTime().ToString('yyyyMMdd-HHmmss-fff')
$backup = Join-Path $backupBase ("joomla-0.5.73-" + $stamp)
New-Item -ItemType Directory -Path $backup -Force | Out-Null

# First fully back up and verify EVERY installed SQL file.
foreach ($file in $files) {
    $copyPath = Join-Path $backup $file.Name
    Copy-Item -LiteralPath $file.FullName -Destination $copyPath -ErrorAction Stop
    $left = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash
    $right = (Get-FileHash -LiteralPath $copyPath -Algorithm SHA256).Hash
    if ($left -ne $right) {
        throw "MM73_BACKUP_HASH_MISMATCH: $($file.Name). NO FILES REMOVED."
    }
}
$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText(
    (Join-Path $backup 'README.txt'),
    "MiniMarket 0.5.73 installed Joomla historical SQL archive.`r`n" +
    "Original path: $historyDir`r`n" +
    "Original files: $($files.Count)`r`n" +
    "Contains SQL history only. No Joomla database tables were modified.`r`n",
    $utf8
)

# Now keep a single no-op schema marker matching Joomla's 0.5.73 version.
# Backups are outside the path scanned by Joomla's ChangeSet.
[System.IO.File]::WriteAllText($expected, $marker, $utf8)
foreach ($file in $remaining) {
    Remove-Item -LiteralPath $file.FullName -ErrorAction Stop
}
$after = @(Get-ChildItem -LiteralPath $historyDir -Filter '*.sql' -File)
if ($after.Count -ne 1 -or $after[0].Name -ne '0.5.73.sql' -or
    -not ([System.IO.File]::ReadAllText($expected)).Contains('SELECT 1;')) {
    throw "MM73_HISTORY_REPAIR_INCOMPLETE: backup=$backup"
}
Write-Output "MM73_SQL_HISTORY_BACKUP_OK: $backup"
Write-Output "MM73_SQL_HISTORY_RETIRED: $($remaining.Count) outdated migration files"
Write-Output 'MM73_JOOMLA_DATABASE_CHECK_READY: refresh System > Maintenance > Database'
Write-Output 'MM73_NO_DATABASE_CHANGES: all Joomla and MiniMarket data retained'
