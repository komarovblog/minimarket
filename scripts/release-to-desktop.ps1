param(
    [string]$DesktopPath = ''
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $root
& git pull --ff-only origin development
if ($LASTEXITCODE -ne 0) { throw 'GIT_PULL_FAILED' }
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'build.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_BUILD_FAILED' }
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'inspect-package.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_INSPECT_FAILED' }
[xml]$manifest=Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
$version=[string]$manifest.extension.version
$versionTag=$version.Replace('.', '_')
$names=@("com_minimarket-$version.zip","minimarket_mysql84_nnop3_update_$versionTag.sql","mod_minimarket_toolbar-$version.zip")
if ([string]::IsNullOrWhiteSpace($DesktopPath)) { $DesktopPath=[Environment]::GetFolderPath('Desktop') }
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) { throw "DESKTOP_NOT_FOUND: $DesktopPath" }
foreach ($name in $names) {
    $src=Join-Path (Join-Path $root 'dist') $name
    if (-not (Test-Path -LiteralPath $src -PathType Leaf)) { throw "OUTPUT_NOT_FOUND: $src" }
    $dst=Join-Path $DesktopPath $name
    Copy-Item -LiteralPath $src -Destination $dst -Force
    if ((Get-FileHash -LiteralPath $src -Algorithm SHA256).Hash -ne
        (Get-FileHash -LiteralPath $dst -Algorithm SHA256).Hash) { throw "DESKTOP_HASH_MISMATCH: $name" }
    Write-Output "DESKTOP_FILE_OK: $dst"
}
# The one-time, versioned SQL migration is optional: use only when
# importing SQL before installing the component instead of Joomla auto-upgrade.
$manualSqlName="minimarket_mysql84_nnop3_upgrade_$versionTag.sql"
$manualSqlPath=Join-Path (Join-Path $root 'releases') $manualSqlName
if (Test-Path -LiteralPath $manualSqlPath -PathType Leaf) {
    $targetSql=Join-Path $DesktopPath $manualSqlName
    Copy-Item -LiteralPath $manualSqlPath -Destination $targetSql -Force
    if ((Get-FileHash -LiteralPath $manualSqlPath -Algorithm SHA256).Hash -ne
        (Get-FileHash -LiteralPath $targetSql -Algorithm SHA256).Hash) {
        throw "DESKTOP_HASH_MISMATCH: $manualSqlName"
    }
    Write-Output "DESKTOP_SQL_UPGRADE_OK: $targetSql"
}
$extraDemo=Join-Path $root 'sample-data\minimarket_demo_gallery_filters_0_5_75.sql'
if ($version -eq '0.5.75' -and (Test-Path -LiteralPath $extraDemo -PathType Leaf)) {
    $name='minimarket_demo_gallery_filters_0_5_75.sql'
    $destination=Join-Path $DesktopPath $name
    Copy-Item -LiteralPath $extraDemo -Destination $destination -Force
    if ((Get-FileHash -LiteralPath $extraDemo -Algorithm SHA256).Hash -ne
        (Get-FileHash -LiteralPath $destination -Algorithm SHA256).Hash) {
        throw 'MINIMARKET_DEMO75_DESKTOP_HASH_MISMATCH'
    }
    Write-Output "MINIMARKET_DEMO_SQL_DESKTOP_OK: $destination"
}
Write-Output "MINIMARKET_DESKTOP_RELEASE_OK: $version"
