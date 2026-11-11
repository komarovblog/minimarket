param(
    [string]$DesktopPath = '',
    [switch]$SkipPull
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# One command => EXACTLY three deliverables on the user's Windows Desktop:
# 1) component ZIP, 2) standalone toolbar module ZIP,
# 3) additive demo-gallery/filters SQL. Never modifies the database.
$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -PathType Leaf)) {
    throw "MINIMARKET_PROJECT_ROOT_NOT_FOUND: $root"
}
Set-Location -LiteralPath $root

if (-not $SkipPull) {
    & git -C $root pull --ff-only origin development
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_GIT_PULL_FAILED' }
}

# Avoid hardcoded OSPanel/PHP paths. Build and inspect within the current
# PowerShell process so Windows does not need to locate a second interpreter.
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-admin-lists.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_ADMIN_LIST_CHECK_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'build.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_BUILD_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'inspect-package.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_PACKAGE_INSPECTION_FAILED' }

[xml]$manifest = Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
$version = [string]$manifest.extension.version
if ($version -notmatch '^\d+\.\d+\.\d+$') {
    throw "MINIMARKET_INVALID_VERSION: $version"
}
$demoName = "minimarket_mysql84_nnop3_update_$($version.Replace('.', '_')).sql"
$demo = Join-Path (Join-Path $root 'sample-data') $demoName
if (-not (Test-Path -LiteralPath $demo -PathType Leaf)) {
    throw "MINIMARKET_DEMO_SQL_NOT_READY_FOR_VERSION: $version"
}
# Desktop may be redirected to OneDrive. Windows supplies the actual location.
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = [Environment]::GetFolderPath('Desktop')
}
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = Join-Path $env:USERPROFILE 'Desktop'
}
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
    throw "MINIMARKET_DESKTOP_NOT_FOUND: $DesktopPath"
}

$deliverables = @(
    @{ Source = Join-Path $root "dist\com_minimarket-$version.zip"; Name = "com_minimarket-$version.zip" },
    @{ Source = Join-Path $root "dist\mod_minimarket_toolbar-$version.zip"; Name = "mod_minimarket_toolbar-$version.zip" },
    @{ Source = $demo; Name = $demoName }
)
foreach ($item in $deliverables) {
    if (-not (Test-Path -LiteralPath $item.Source -PathType Leaf)) {
        throw "MINIMARKET_REQUIRED_FILE_MISSING: $($item.Source)"
    }
}
foreach ($item in $deliverables) {
    $dst = Join-Path $DesktopPath $item.Name
    Copy-Item -LiteralPath $item.Source -Destination $dst -Force
    $fromHash = (Get-FileHash -LiteralPath $item.Source -Algorithm SHA256).Hash
    $toHash = (Get-FileHash -LiteralPath $dst -Algorithm SHA256).Hash
    if ($fromHash -ne $toHash) {
        throw "MINIMARKET_DESKTOP_HASH_MISMATCH: $dst"
    }
    Write-Output "DESKTOP_FILE_OK: $dst"
}
Write-Output "MINIMARKET_DESKTOP_3_FILES_OK: version=$version"
Write-Output 'SQL_IS_OPTIONAL: import ONLY to refresh optional demo catalog data; do not import cumulative or clean SQL.'
