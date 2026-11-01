param(
    [string]$OutputPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dist = Join-Path $root 'dist'
$componentZip = Join-Path $dist 'com_minimarket.zip'
$pluginZip = Join-Path $dist 'plg_content_minimarketmenu.zip'
$plugin = Join-Path $root 'src\plg_content_minimarketmenu'
$package = Join-Path $root 'src\pkg_minimarket\pkg_minimarket.xml'

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path $dist 'pkg_minimarket.zip'
}
$OutputPath = [System.IO.Path]::GetFullPath($OutputPath)

# Keep component ZIP buildable/installable in isolation for diagnostics.
& (Join-Path $PSScriptRoot 'build.ps1') -OutputPath $componentZip
if (-not (Test-Path -LiteralPath $componentZip -PathType Leaf)) { throw 'COMPONENT_BUILD_FAILED' }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Write-PortableZip([string]$Source, [string]$Destination) {
    $temp = $Destination + '.building-' + [guid]::NewGuid().ToString('N')
    try {
        $zip = [System.IO.Compression.ZipFile]::Open(
            $temp, [System.IO.Compression.ZipArchiveMode]::Create
        )
        try {
            $entries = @(Get-ChildItem -LiteralPath $Source -Recurse -File | Sort-Object FullName)
            if ($entries.Count -eq 0) { throw 'ZIP_STAGE_EMPTY' }
            foreach ($entry in $entries) {
                $zipEntry = $entry.FullName.Substring($Source.Length + 1).Replace('\', '/')
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $zip, $entry.FullName, $zipEntry,
                    [System.IO.Compression.CompressionLevel]::Optimal
                ) | Out-Null
            }
        }
        finally { $zip.Dispose() }
        Move-Item -LiteralPath $temp -Destination $Destination -Force
    }
    finally {
        if (Test-Path -LiteralPath $temp) {
            Remove-Item -LiteralPath $temp -Force
        }
    }
}

if (-not (Test-Path -LiteralPath $package -PathType Leaf)) {
    throw 'PACKAGE_MANIFEST_MISSING'
}
foreach ($file in @(
    'minimarketmenu.xml',
    'services\provider.php',
    'src\Extension\Minimarketmenu.php',
    'src\Service\PrimaryMenuSync.php',
    'language\ru-RU\plg_content_minimarketmenu.sys.ini',
    'language\en-GB\plg_content_minimarketmenu.sys.ini'
)) {
    if (-not (Test-Path -LiteralPath (Join-Path $plugin $file) -PathType Leaf)) {
        throw "PLUGIN_FILE_MISSING: $file"
    }
}
[xml]$manifest = Get-Content -LiteralPath $package -Raw -Encoding UTF8
[xml]$componentManifest = Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
[xml]$pluginManifest = Get-Content -LiteralPath (Join-Path $plugin 'minimarketmenu.xml') -Raw -Encoding UTF8
if ($manifest.extension.type -ne 'package' -or
    $manifest.extension.packagename -ne 'minimarket' -or
    $manifest.extension.version -ne $componentManifest.extension.version -or
    $manifest.extension.version -ne $pluginManifest.extension.version) {
    throw 'PACKAGE_VERSION_OR_MANIFEST_MISMATCH'
}

$stage = Join-Path ([System.IO.Path]::GetTempPath()) ('minimarket-bundle-' + [guid]::NewGuid().ToString('N'))
$pluginStage = Join-Path $stage 'plugin'
$packageStage = Join-Path $stage 'package'
try {
    New-Item -ItemType Directory -Path $pluginStage -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $plugin 'minimarketmenu.xml') -Destination $pluginStage
    foreach ($folder in @('services', 'src', 'language')) {
        Copy-Item -LiteralPath (Join-Path $plugin $folder) -Destination $pluginStage -Recurse
    }
    Write-PortableZip $pluginStage $pluginZip

    New-Item -ItemType Directory -Path (Join-Path $packageStage 'packages') -Force | Out-Null
    Copy-Item -LiteralPath $package -Destination $packageStage
    Copy-Item -LiteralPath $componentZip -Destination (Join-Path $packageStage 'packages')
    Copy-Item -LiteralPath $pluginZip -Destination (Join-Path $packageStage 'packages')
    Write-PortableZip $packageStage $OutputPath
    Write-Output "MiniMarket PACKAGE ZIP: $OutputPath"
}
finally {
    if (Test-Path -LiteralPath $stage) {
        Remove-Item -LiteralPath $stage -Recurse -Force
    }
}
