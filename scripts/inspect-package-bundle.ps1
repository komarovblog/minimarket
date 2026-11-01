param([string]$ZipPath = '')

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($ZipPath)) {
    $ZipPath = Join-Path (Split-Path -Parent $PSScriptRoot) 'dist\pkg_minimarket.zip'
}
if (-not (Test-Path -LiteralPath $ZipPath -PathType Leaf)) {
    Write-Output 'BUNDLE_FAIL: FILE_NOT_FOUND'
    exit 1
}
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Read-TextEntry($Zip, [string]$Name) {
    $entry = $Zip.GetEntry($Name)
    if ($null -eq $entry) { throw "MISSING_ENTRY: $Name" }
    $reader = New-Object System.IO.StreamReader($entry.Open())
    try { return $reader.ReadToEnd() }
    finally { $reader.Dispose() }
}

function Assert-Portable($Zip) {
    foreach ($entry in $Zip.Entries) {
        if ($entry.FullName.Contains('\') -or $entry.FullName.StartsWith('/')) {
            throw 'NONPORTABLE_ZIP_ENTRY'
        }
    }
}

$archive = $null
$children = @()
try {
    $archive = [System.IO.Compression.ZipFile]::OpenRead(
        [System.IO.Path]::GetFullPath($ZipPath)
    )
    Assert-Portable $archive
    [xml]$manifest = Read-TextEntry $archive 'pkg_minimarket.xml'
    if ($manifest.extension.type -ne 'package' -or
        $manifest.extension.packagename -ne 'minimarket') {
        throw 'INVALID_PACKAGE_MANIFEST'
    }
    foreach ($childName in @('com_minimarket.zip', 'plg_content_minimarketmenu.zip')) {
        $entry = $archive.GetEntry("packages/$childName")
        if ($null -eq $entry) { throw "MISSING_CHILD: $childName" }
        $memory = New-Object System.IO.MemoryStream
        $stream = $entry.Open()
        try { $stream.CopyTo($memory) }
        finally { $stream.Dispose() }
        $memory.Position = 0
        $zip = [System.IO.Compression.ZipArchive]::new(
            $memory, [System.IO.Compression.ZipArchiveMode]::Read, $false
        )
        $children += $zip
        Assert-Portable $zip

        if ($childName -eq 'com_minimarket.zip') {
            [xml]$inner = Read-TextEntry $zip 'minimarket.xml'
            if ($inner.extension.type -ne 'component') { throw 'INVALID_COMPONENT' }
            foreach ($file in @(
                'site/src/Service/Router.php',
                'site/src/View/Catalog/HtmlView.php',
                'admin/services/provider.php'
            )) {
                if ($null -eq $zip.GetEntry($file)) { throw "MISSING_COMPONENT_FILE: $file" }
            }
        }
        else {
            [xml]$inner = Read-TextEntry $zip 'minimarketmenu.xml'
            if ($inner.extension.type -ne 'plugin' -or
                $inner.extension.group -ne 'content') { throw 'INVALID_PLUGIN' }
            foreach ($file in @(
                'services/provider.php',
                'src/Extension/Minimarketmenu.php',
                'src/Service/PrimaryMenuSync.php'
            )) {
                if ($null -eq $zip.GetEntry($file)) { throw "MISSING_PLUGIN_FILE: $file" }
            }
        }
        if ([string]$inner.extension.version -ne [string]$manifest.extension.version) {
            throw "CHILD_VERSION_MISMATCH: $childName"
        }
    }
    Write-Output ('BUNDLE_OK: ' + [string]$manifest.extension.version)
    exit 0
}
catch {
    Write-Output ('BUNDLE_FAIL: ' + $_.Exception.Message)
    exit 1
}
finally {
    foreach ($child in $children) { $child.Dispose() }
    if ($null -ne $archive) { $archive.Dispose() }
}
