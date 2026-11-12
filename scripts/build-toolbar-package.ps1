param(
    [string]$OutputPath = '',
    [switch]$DistOnly
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Independent ONE-TIME administrator quicklink package, never built by
# release-to-desktop.ps1 when the MiniMarket component changes versions.
$root = Split-Path -Parent $PSScriptRoot
$source = Join-Path $root 'src\mod_minimarket_toolbar'
[xml]$manifest = Get-Content -LiteralPath (Join-Path $source 'mod_minimarket_toolbar.xml') -Raw -Encoding UTF8
$version = [string]$manifest.extension.version
if ($version -notmatch '^\d+\.\d+\.\d+$') { throw 'MM_TOOLBAR_VERSION_INVALID' }
if ([string]$manifest.extension.client -ne 'administrator') { throw 'MM_TOOLBAR_CLIENT_INVALID' }
if (-not $OutputPath) {
    $OutputPath = Join-Path (Join-Path $root 'dist') ('mod_minimarket_toolbar-' + $version + '.zip')
}
$targetDir = Split-Path -Parent $OutputPath
New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$stage=Join-Path ([System.IO.Path]::GetTempPath()) ('mm-toolbar-build-' + [guid]::NewGuid().ToString('N'))
$temporaryZip=$OutputPath+'.building'
try {
    New-Item -ItemType Directory -Path $stage -Force | Out-Null
    foreach($entry in @('mod_minimarket_toolbar.xml','mod_minimarket_toolbar.php','script.php')) {
        Copy-Item -LiteralPath (Join-Path $source $entry) -Destination (Join-Path $stage $entry)
    }
    foreach($folder in @('tmpl','language')) {
        Copy-Item -LiteralPath (Join-Path $source $folder) -Destination (Join-Path $stage $folder) -Recurse
    }
    if(Test-Path -LiteralPath $temporaryZip){Remove-Item -LiteralPath $temporaryZip -Force}
    $zip=[System.IO.Compression.ZipFile]::Open($temporaryZip,[System.IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach($file in Get-ChildItem -LiteralPath $stage -Recurse -File){
            $rel=$file.FullName.Substring($stage.Length+1).Replace('\','/')
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip,$file.FullName,$rel,[System.IO.Compression.CompressionLevel]::Optimal)
        }
    }
    finally {$zip.Dispose()}
    $check=[System.IO.Compression.ZipFile]::OpenRead($temporaryZip)
    try {
        $entries=@($check.Entries | ForEach-Object {$_.FullName})
        foreach($required in @(
            'mod_minimarket_toolbar.xml','mod_minimarket_toolbar.php','script.php',
            'tmpl/default.php',
            'language/ru-RU/mod_minimarket_toolbar.ini',
            'language/ru-RU/mod_minimarket_toolbar.sys.ini',
            'language/en-GB/mod_minimarket_toolbar.ini',
            'language/en-GB/mod_minimarket_toolbar.sys.ini')) {
            if($entries -notcontains $required){throw "MM_TOOLBAR_PACKAGE_REQUIRED_FILE_MISSING: $required"}
        }
        if($entries | Where-Object {$_ -match '\\|^\/'}) {throw 'MM_TOOLBAR_INVALID_ZIP_PATH'}
    }
    finally {$check.Dispose()}
    Move-Item -LiteralPath $temporaryZip -Destination $OutputPath -Force
    Write-Output "MM_TOOLBAR_PACKAGE_OK: $version ($OutputPath)"
    if(-not $DistOnly) {
        $desktop=[Environment]::GetFolderPath('Desktop')
        if(-not $desktop) {throw 'MM_TOOLBAR_DESKTOP_NOT_FOUND'}
        $dest=Join-Path $desktop (Split-Path -Leaf $OutputPath)
        if([IO.Path]::GetFullPath($dest) -ne [IO.Path]::GetFullPath($OutputPath)) {
            Copy-Item -LiteralPath $OutputPath -Destination $dest -Force
        }
        Write-Output "MM_TOOLBAR_DESKTOP_PACKAGE_OK: $dest"
    }
}
finally {
    if(Test-Path -LiteralPath $stage){Remove-Item -LiteralPath $stage -Recurse -Force}
    if(Test-Path -LiteralPath $temporaryZip){Remove-Item -LiteralPath $temporaryZip -Force}
}
