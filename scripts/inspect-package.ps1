param(
    [string]$ZipPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$sourceManifest = Join-Path $root 'src\com_minimarket\minimarket.xml'
[xml]$sourceXml = Get-Content -LiteralPath $sourceManifest -Raw -Encoding UTF8
$expectedVersion = [string]$sourceXml.extension.version
if ($expectedVersion -notmatch '^\d+\.\d+\.\d+$') {
    Write-Output 'PACKAGE_FAIL: INVALID_SOURCE_VERSION'
    exit 1
}

$expectedName = 'com_minimarket-' + $expectedVersion + '.zip'
if ([string]::IsNullOrWhiteSpace($ZipPath)) {
    $ZipPath = Join-Path $root ('dist\' + $expectedName)
}
if ((Split-Path -Leaf $ZipPath) -ne $expectedName) {
    Write-Output ('PACKAGE_FAIL: WRONG_ARCHIVE_NAME_EXPECTED_' + $expectedName)
    exit 1
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

    if ([string]$xml.extension.version -ne $expectedVersion) {
        Write-Output ('PACKAGE_FAIL: VERSION_MISMATCH_EXPECTED_' + $expectedVersion)
        exit 1
    }

    foreach ($file in @(
        'admin/services/provider.php',
        'admin/src/Field/ShopmenuField.php',
        'admin/sql/updates/mysql/0.4.1.sql',
        'admin/sql/updates/mysql/0.4.2.sql',
        'admin/sql/updates/mysql/0.5.0.sql',
        'admin/src/Field/ProductcategoryField.php',
        'admin/forms/product.xml',
        'admin/tmpl/product/edit.php',
        'admin/src/Service/CategoryAdminTree.php',
        'admin/src/Field/CategoryparentField.php',
        'admin/src/Extension/MinimarketComponent.php',
        'site/src/Controller/DisplayController.php'
    )) {
        if ($null -eq $archive.GetEntry($file)) {
            Write-Output ('PACKAGE_FAIL: MISSING_COMPONENT_FILE: ' + $file)
            exit 1
        }
    }

    # Deferred stage-6 attributes must never be included in category releases.
    foreach ($deferred in @(
        'admin/sql/updates/mysql/0.4.0.sql',
        'admin/forms/attribute.xml',
        'admin/tmpl/attributes/default.php',
        'admin/src/Table/AttributeTable.php'
    )) {
        if ($null -ne $archive.GetEntry($deferred)) {
            Write-Output ('PACKAGE_FAIL: PREMATURE_ATTRIBUTES_IN_PACKAGE: ' + $deferred)
            exit 1
        }
    }

    # Inspect exactly what Joomla will install: the five editor tabs and image fields.
    $formEntry = $archive.GetEntry('admin/forms/product.xml')
    $formReader = New-Object System.IO.StreamReader($formEntry.Open())
    try {
        [xml]$productForm = $formReader.ReadToEnd()
    }
    finally {
        $formReader.Dispose()
    }
    foreach ($tab in @('details', 'descriptions', 'images', 'seo', 'publishing')) {
        $xpath = "/form/fieldset[@name='" + $tab + "']"
        if ($null -eq $productForm.SelectSingleNode($xpath)) {
            Write-Output ('PACKAGE_FAIL: PRODUCT_TAB_MISSING_' + $tab)
            exit 1
        }
    }
    if ($null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='image' and @type='media']") -or
        $null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='image_alt']")) {
        Write-Output 'PACKAGE_FAIL: PRODUCT_IMAGE_FIELDS_MISSING'
        exit 1
    }

    $editEntry = $archive.GetEntry('admin/tmpl/product/edit.php')
    $editReader = New-Object System.IO.StreamReader($editEntry.Open())
    try {
        $editSource = $editReader.ReadToEnd()
    }
    finally {
        $editReader.Dispose()
    }
    if (-not $editSource.Contains("'images'") -or -not $editSource.Contains('uitab.addTab')) {
        Write-Output 'PACKAGE_FAIL: PRODUCT_IMAGES_TAB_NOT_RENDERED'
        exit 1
    }

    Write-Output ('PACKAGE_OK: ' + $expectedVersion + ' (' + $expectedName + ')')
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
