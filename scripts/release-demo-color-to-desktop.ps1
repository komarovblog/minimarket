param(
    [string]$DesktopPath = '',
    [string]$JoomlaConfigPath = ''
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $root

& git pull --ff-only origin development
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_DEMO_GIT_PULL_FAILED' }

if ([string]::IsNullOrWhiteSpace($JoomlaConfigPath)) {
    foreach ($candidate in @(
        (Join-Path $root 'public_html\configuration.php'),
        (Join-Path $root 'configuration.php')
    )) {
        if (Test-Path -LiteralPath $candidate -PathType Leaf) {
            $JoomlaConfigPath = $candidate
            break
        }
    }
}
if (-not (Test-Path -LiteralPath $JoomlaConfigPath -PathType Leaf)) {
    throw 'MINIMARKET_DEMO_JOOMLA_CONFIG_NOT_FOUND'
}
$siteRoot = Split-Path -Parent $JoomlaConfigPath
$cfg = [System.IO.File]::ReadAllText($JoomlaConfigPath)
$prefixMatch = [regex]::Match($cfg, 'public\s+\$dbprefix\s*=\s*[''"](?<v>[a-zA-Z0-9_]{2,64})[''"]\s*;')
$dbMatch = [regex]::Match($cfg, 'public\s+\$db\s*=\s*[''"](?<v>[a-zA-Z0-9_]{1,64})[''"]\s*;')
if (-not $prefixMatch.Success -or -not $dbMatch.Success) {
    throw 'MINIMARKET_DEMO_CONFIG_PREFIX_OR_DATABASE_MISSING'
}
$prefix = $prefixMatch.Groups['v'].Value
$database = $dbMatch.Groups['v'].Value

# The user already has sample-01.png ... sample-10.png in Joomla.
# Never create, replace, or copy any media. Fail if a required file is absent.
$mediaRoot = Join-Path $siteRoot 'images\minimarket'
$required = 1..10 | ForEach-Object { 'sample-{0:D2}.png' -f $_ }
foreach ($name in $required) {
    $img = Join-Path $mediaRoot $name
    if (-not (Test-Path -LiteralPath $img -PathType Leaf)) {
        throw "MINIMARKET_DEMO_EXISTING_IMAGE_MISSING: $img"
    }
}
Write-Output "MINIMARKET_DEMO_EXISTING_IMAGES_OK: 10 files in $mediaRoot"

$source = Join-Path $root 'sample-data\minimarket_demo_color_switch_0_5_73.sql'
if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
    throw 'MINIMARKET_DEMO_SQL_SOURCE_MISSING'
}
$sql = [System.IO.File]::ReadAllText($source,[System.Text.Encoding]::UTF8)
if (-not $sql.Contains('nnop3_minimarket_product_image_variants') -or
    -not $sql.Contains('images/minimarket/sample-01.png') -or
    -not $sql.Contains('images/minimarket/sample-10.png') -or
    $sql.Contains('images/minimarket/demo-tee-') -or
    $sql.Contains('images/minimarket/demo-mug-')) {
    throw 'MINIMARKET_DEMO_SQL_SOURCE_INVALID'
}
$sql = $sql.Replace('nnop3_',$prefix)
$sql = $sql.Replace('USE `minimarket`;',('USE `' + $database + '`;'))
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = [Environment]::GetFolderPath('Desktop')
}
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
    throw 'MINIMARKET_DEMO_DESKTOP_NOT_FOUND'
}
$dest = Join-Path $DesktopPath ("minimarket_demo_color_switch_0.5.73_" + $prefix.TrimEnd('_') + ".sql")
[System.IO.File]::WriteAllText($dest,$sql,[System.Text.UTF8Encoding]::new($false))
Write-Output "MINIMARKET_DEMO_SQL_DESKTOP_OK: $dest"
Write-Output "MINIMARKET_DEMO_EXISTING_IMAGES_REUSED: $mediaRoot"
Write-Output 'MINIMARKET_DEMO_VARIANTS_READY: 12 SKUs, 5 colors, 10 existing sample photos'
Write-Output 'MINIMARKET_DEMO_MEDIA_RELEASE_OK: 0.5.73'
