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

# This step creates only missing demonstration pictures and overwrites
# the 10 dedicated color-test pictures. Never overwrites user-uploaded media.
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'create-demo-images.ps1') -SiteRoot $siteRoot
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_DEMO_IMAGE_GENERATION_FAILED' }

$mediaRoot = Join-Path $siteRoot 'images\minimarket'
$required = @(
    'sample-11.png','sample-12.png','sample-13.png','sample-14.png',
    'sample-15.png','sample-16.png','sample-17.png','sample-18.png',
    'sample-19.png','sample-20.png',
    'demo-tee-blue-front.png','demo-tee-blue-detail.png',
    'demo-tee-white-front.png','demo-tee-white-detail.png',
    'demo-tee-black-front.png','demo-tee-black-detail.png',
    'demo-mug-white-front.png','demo-mug-white-detail.png',
    'demo-mug-red-front.png','demo-mug-red-detail.png'
)
foreach ($name in $required) {
    $img = Join-Path $mediaRoot $name
    if (-not (Test-Path -LiteralPath $img -PathType Leaf)) {
        throw "MINIMARKET_DEMO_IMAGE_MISSING: $img"
    }
    $data = [System.IO.File]::ReadAllBytes($img)
    if ($data.Length -lt 5000 -or
        $data[0] -ne 137 -or $data[1] -ne 80 -or
        $data[2] -ne 78 -or $data[3] -ne 71) {
        throw "MINIMARKET_DEMO_IMAGE_INVALID_PNG: $img"
    }
}

$source = Join-Path $root 'sample-data\minimarket_demo_color_switch_0_5_73.sql'
if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
    throw 'MINIMARKET_DEMO_SQL_SOURCE_MISSING'
}
$sql = [System.IO.File]::ReadAllText($source,[System.Text.Encoding]::UTF8)
if (-not $sql.Contains('nnop3_minimarket_product_image_variants') -or
    -not $sql.Contains('demo-tee-blue-front.png') -or
    -not $sql.Contains('demo-mug-red-front.png')) {
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
Write-Output "MINIMARKET_DEMO_IMAGES_READY: $mediaRoot"
Write-Output 'MINIMARKET_DEMO_VARIANTS_READY: 12 SKUs, 5 colors, 10 photos'
Write-Output 'MINIMARKET_DEMO_MEDIA_RELEASE_OK: 0.5.73'
