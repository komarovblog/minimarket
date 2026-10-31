param(
    [string]$ZipPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($ZipPath)) {
    $root = Split-Path -Parent $PSScriptRoot
    $ZipPath = Join-Path $root 'dist\com_minimarket.zip'
}

if (-not (Test-Path -LiteralPath $ZipPath -PathType Leaf)) {
    Write-Output 'PACKAGE_FAIL: FILE_NOT_FOUND'
    exit 1
}

$archive = $null
$reader = $null
try {
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [System.IO.Compression.ZipFile]::OpenRead(
        [System.IO.Path]::GetFullPath($ZipPath)
    )
    $members = @($archive.Entries | ForEach-Object { $_.FullName })

    if (@($members | Where-Object { $_.Contains('\') -or $_.StartsWith('/') }).Count -gt 0) {
        Write-Output 'PACKAGE_FAIL: NONPORTABLE_ENTRY_PATHS'
        exit 1
    }

    $entry = $archive.GetEntry('minimarket.xml')

    if ($null -eq $entry) {
        $nested = @($members | Where-Object { $_ -match '(^|/)minimarket\.xml$' })
        if ($nested.Count -gt 0) {
            Write-Output 'PACKAGE_FAIL: MANIFEST_NOT_AT_ROOT'
        }
        else {
            Write-Output 'PACKAGE_FAIL: MANIFEST_MISSING'
        }
        exit 1
    }

    $reader = New-Object System.IO.StreamReader($entry.Open())
    [xml]$xml = $reader.ReadToEnd()
    $rootNode = $xml.DocumentElement

    if (
        $null -eq $rootNode -or
        $rootNode.LocalName -ne 'extension' -or
        $rootNode.GetAttribute('type') -ne 'component' -or
        $xml.extension.element -ne 'com_minimarket'
    ) {
        Write-Output 'PACKAGE_FAIL: INVALID_MANIFEST'
        exit 1
    }

    foreach ($file in @(
        'admin/services/provider.php',
        'admin/src/Extension/MinimarketComponent.php',
        'site/src/Controller/DisplayController.php'
    )) {
        if ($null -eq $archive.GetEntry($file)) {
            Write-Output ('PACKAGE_FAIL: MISSING_COMPONENT_FILE: ' + $file)
            exit 1
        }
    }

    Write-Output ('PACKAGE_OK: ' + [string]$xml.extension.version)
    exit 0
}
catch {
    Write-Output 'PACKAGE_FAIL: ZIP_OR_XML_UNREADABLE'
    exit 1
}
finally {
    if ($null -ne $reader) {
        $reader.Dispose()
    }
    if ($null -ne $archive) {
        $archive.Dispose()
    }
}
