param(
    [string]$DesktopPath = '',
    [string]$JoomlaConfigPath = ''
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $root

# A single command fetches the current development sources and builds
# two Joomla packages plus one prefix-correct, clean demonstration catalog SQL.
& git pull --ff-only origin development
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_GIT_PULL_FAILED' }

if ([string]::IsNullOrWhiteSpace($JoomlaConfigPath)) {
    $candidates = @(
        (Join-Path $root 'public_html\configuration.php'),
        (Join-Path $root 'configuration.php')
    )
    foreach ($candidate in $candidates) {
        if (Test-Path -LiteralPath $candidate -PathType Leaf) {
            $JoomlaConfigPath = $candidate
            break
        }
    }
}
if ([string]::IsNullOrWhiteSpace($JoomlaConfigPath) -or
    -not (Test-Path -LiteralPath $JoomlaConfigPath -PathType Leaf)) {
    throw 'JOOMLA_CONFIG_NOT_FOUND: expected public_html\configuration.php'
}
$config = [System.IO.File]::ReadAllText($JoomlaConfigPath)
$prefixPattern = 'public\s+\$dbprefix\s*=\s*[''"](?<v>[A-Za-z0-9_]{2,64})[''"]\s*;'
$databasePattern = 'public\s+\$db\s*=\s*[''"](?<v>[A-Za-z0-9_]{1,64})[''"]\s*;'
$prefixMatch = [regex]::Match($config, $prefixPattern)
$databaseMatch = [regex]::Match($config, $databasePattern)
if (-not $prefixMatch.Success -or -not $databaseMatch.Success) {
    throw 'JOOMLA_DATABASE_OR_PREFIX_NOT_FOUND_IN_CONFIG'
}
$dbPrefix = $prefixMatch.Groups['v'].Value
$dbName = $databaseMatch.Groups['v'].Value
if ($dbPrefix -notmatch '^[A-Za-z0-9_]{2,64}$' -or
    $dbName -notmatch '^[A-Za-z0-9_]{1,64}$') {
    throw 'JOOMLA_UNSAFE_DATABASE_CONFIGURATION'
}

& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'build.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_BUILD_FAILED' }
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'inspect-package.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_PACKAGE_INSPECTION_FAILED' }

[xml]$manifest = Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
$version = [string]$manifest.extension.version
if ($version -ne '0.5.73') {
    throw "CLEAN_SQL_GENERATOR_VERSION_MISMATCH: $version"
}
$sqlName = "minimarket_clean_{0}_{1}.sql" -f $version, $dbPrefix.TrimEnd('_')
$sqlPath = Join-Path (Join-Path $root 'dist') $sqlName
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'build-clean-catalog.ps1') -DbPrefix $dbPrefix -DatabaseName $dbName -OutputPath $sqlPath
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_CLEAN_SQL_BUILD_FAILED' }

if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = [Environment]::GetFolderPath('Desktop')
}
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
    throw "DESKTOP_NOT_FOUND: $DesktopPath"
}
$files = @(
    "com_minimarket-$version.zip",
    "mod_minimarket_toolbar-$version.zip",
    $sqlName
)
foreach ($file in $files) {
    $from = Join-Path (Join-Path $root 'dist') $file
    $to = Join-Path $DesktopPath $file
    if (-not (Test-Path -LiteralPath $from -PathType Leaf)) {
        throw "MINIMARKET_RELEASE_FILE_MISSING: $file"
    }
    Copy-Item -LiteralPath $from -Destination $to -Force
    if ((Get-FileHash -LiteralPath $from -Algorithm SHA256).Hash -ne
        (Get-FileHash -LiteralPath $to -Algorithm SHA256).Hash) {
        throw "MINIMARKET_DESKTOP_COPY_MISMATCH: $file"
    }
    Write-Output "DESKTOP_FILE_OK: $to"
}
Write-Output "MINIMARKET_CLEAN_RELEASE_READY: $version database=$dbName prefix=$dbPrefix"
Write-Output 'IMPORTANT: The generated SQL DOES NOT run automatically. Backup the full database before manual phpMyAdmin import.'
