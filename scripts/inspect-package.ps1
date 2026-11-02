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
        'admin/sql/updates/mysql/0.5.2.sql',
        'admin/sql/updates/mysql/0.5.3.sql',
        'admin/sql/updates/mysql/0.5.4.sql',
        'admin/sql/updates/mysql/0.5.5.sql',
        'admin/sql/updates/mysql/0.5.7.sql',
        'admin/sql/updates/mysql/0.5.11.sql',
        'admin/sql/updates/mysql/0.5.12.sql',
        'admin/sql/updates/mysql/0.5.14.sql',
        'admin/sql/updates/mysql/0.5.15.sql',
        'admin/sql/updates/mysql/0.5.16.sql',
        'admin/sql/updates/mysql/0.5.17.sql',
        'admin/forms/product_variant.xml',
        'admin/src/Service/ProductVariants.php',
        'admin/src/Field/VariantinfoField.php',
        'site/src/Service/VariantCatalog.php',
        'media/js/variants-admin.js',
        'media/js/variants.js',
        'media/css/variants.css',
        'site/src/Service/ProductAttributeCatalog.php',
        'media/css/attributes.css',
        'admin/src/Service/TextPresetRows.php',
        'admin/src/Service/ProductVideos.php',
        'admin/forms/attribute_text_preset_row.xml',
        'admin/forms/attribute_color_row.xml',
        'admin/forms/product_video.xml',
        'admin/src/Service/AttributeSettings.php',
        'media/js/attribute-settings.js',
        'admin/src/Service/ProductAttributes.php',
        'admin/src/Field/ProductattributesField.php',
        'media/js/product-attributes.js',
        'admin/src/Service/CategoryAttributes.php',
        'admin/src/Field/CategoryattributesField.php',
        'admin/src/Field/AttributecategoriesField.php',
        'admin/src/Service/BulkDeleteGuard.php',
        'admin/src/Controller/CategoriesController.php',
        'admin/src/Controller/ProductsController.php',
        'admin/src/Controller/AttributesController.php',
        'admin/src/Controller/ValuesController.php',
        'admin/forms/attribute.xml',
        'admin/src/Service/AttributeValueRows.php',
        'admin/forms/attribute_value_row.xml',
        'admin/forms/attribute_number_preset_row.xml',
        'admin/forms/attribute_text_preset_row.xml',
        'admin/src/Service/TextPresetRows.php',
        'admin/sql/updates/mysql/0.5.14.sql',
        'admin/src/Service/NumericPresetRows.php',
        'admin/forms/filter_products.xml',
        'admin/forms/filter_attributes.xml',
        'admin/forms/filter_categories.xml',
        'admin/src/Service/AttributeValueOrdering.php',
        'media/js/attribute-values-sort.js',
        'admin/forms/value.xml',
        'admin/src/Table/AttributeTable.php',
        'admin/src/Table/ValueTable.php',
        'admin/tmpl/attributes/default.php',
        'admin/tmpl/values/default.php',
        'admin/forms/product_gallery.xml',
        'admin/src/Service/ProductGallery.php',
        'media/js/gallery.js',
        'media/css/gallery.css',
        'admin/src/Service/MediaPath.php',
        'media/js/clean-media.js',
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

    # Only the abandoned pre-0.5.4 migration remains prohibited.
    # Attribute editors themselves are required by release 0.5.4.
    foreach ($deferred in @(
        'admin/sql/updates/mysql/0.4.0.sql'
    )) {
        if ($null -ne $archive.GetEntry($deferred)) {
            Write-Output ('PACKAGE_FAIL: PREMATURE_ATTRIBUTES_IN_PACKAGE: ' + $deferred)
            exit 1
        }
    }

    # Ensure image cleanup UI asset will be installed by the component.
    if ($null -eq $xml.SelectSingleNode("/extension/media[@destination='com_minimarket']/folder[text()='js']")) {
        Write-Output 'PACKAGE_FAIL: CLEAN_MEDIA_ASSET_NOT_DECLARED'
        exit 1
    }

    if ($null -eq $xml.SelectSingleNode("/extension/media[@destination='com_minimarket']/folder[text()='css']")) {
        Write-Output 'PACKAGE_FAIL: GALLERY_CSS_NOT_DECLARED'
        exit 1
    }

    # New cardinality is a real schema field, distinct from the SKU flag.
    $schemaUpdateEntry = $archive.GetEntry('admin/sql/updates/mysql/0.5.11.sql')
    if ($null -eq $schemaUpdateEntry) {
        Write-Output 'PACKAGE_FAIL: MULTI_VALUE_MIGRATION_MISSING'
        exit 1
    }
    $attributeFormEntry = $archive.GetEntry('admin/forms/attribute.xml')
    $attrReader = New-Object System.IO.StreamReader($attributeFormEntry.Open())
    try {
        [xml]$attrXml = $attrReader.ReadToEnd()
    }
    finally {
        $attrReader.Dispose()
    }
    foreach ($fieldName in @('input_mode','filter_mode','filter_multiple','number_options','number_unit')) {
        if ($null -eq $attrXml.SelectSingleNode("/form/fieldset[@name='details']/field[@name='" + $fieldName + "']")) {
            Write-Output ('PACKAGE_FAIL: ATTRIBUTE_PRESENTATION_FIELD_MISSING: ' + $fieldName)
            exit 1
        }
    }
    if ($null -eq $attrXml.SelectSingleNode("/form/fieldset[@name='details']/field[@name='is_multiple' and @type='list' and @showon='input_type:list,color,text']")) {
        Write-Output 'PACKAGE_FAIL: VALUE_CARDINALITY_FORM_MISSING'
        exit 1
    }
    if ($null -eq $attrXml.SelectSingleNode("/form/fieldset[@name='details']/field[@name='values' and @type='subform' and @multiple='true' and @buttons='add,remove,move']") -or
        $null -eq $attrXml.SelectSingleNode("/form/field[@name='values_present']")) {
        Write-Output 'PACKAGE_FAIL: ATTRIBUTE_SUBFORM_FIELDS_MISSING'
        exit 1
    }
    if ($null -eq $attrXml.SelectSingleNode("/form/fieldset[@name='details']/field[@name='number_preset_rows' and @type='subform' and @multiple='true' and @buttons='add,remove,move']")) {
        Write-Output 'PACKAGE_FAIL: NUMERIC_PRESET_SUBFORM_MISSING'
        exit 1
    }
    foreach ($name in @('products','attributes','categories')) {
        if ($null -eq $archive.GetEntry('admin/forms/filter_' + $name + '.xml')) {
            Write-Output ('PACKAGE_FAIL: NATIVE_FILTER_FORM_MISSING_' + $name)
            exit 1
        }
        $listEntry = $archive.GetEntry('admin/tmpl/' + $name + '/default.php')
        $listReader = New-Object System.IO.StreamReader($listEntry.Open())
        try { $listSource = $listReader.ReadToEnd() }
        finally { $listReader.Dispose() }
        if (-not $listSource.Contains('joomla.searchtools.default')) {
            Write-Output ('PACKAGE_FAIL: NATIVE_SEARCHTOOLS_MISSING_' + $name)
            exit 1
        }
    }

    $valueRowFormEntry = $archive.GetEntry('admin/forms/attribute_value_row.xml')
    $valueRowReader = New-Object System.IO.StreamReader($valueRowFormEntry.Open())
    try {
        [xml]$valueRowXml = $valueRowReader.ReadToEnd()
    }
    finally {
        $valueRowReader.Dispose()
    }
    if ($null -eq $valueRowXml.SelectSingleNode("/form/field[@name='id' and @type='hidden']") -or
        $null -eq $valueRowXml.SelectSingleNode("/form/field[@name='title']")) {
        Write-Output 'PACKAGE_FAIL: ATTRIBUTE_SUBFORM_ROW_MISSING'
        exit 1
    }

    if ($null -eq $attrXml.SelectSingleNode("/form/fieldset[@name='categories']/field[@name='category_rules' and @type='Attributecategories']")) {
        Write-Output 'PACKAGE_FAIL: REVERSE_CATEGORY_FIELD_MISSING'
        exit 1
    }

    $attributeEditEntry = $archive.GetEntry('admin/tmpl/attribute/edit.php')
    $attributeEditReader = New-Object System.IO.StreamReader($attributeEditEntry.Open())
    try {
        $attributeEditSource = $attributeEditReader.ReadToEnd()
    }
    finally {
        $attributeEditReader.Dispose()
    }
    if (-not $attributeEditSource.Contains("renderFieldset(") -or
        $attributeEditSource.Contains("'minimarket-attribute', 'values'") -or
        $attributeEditSource.Contains('COM_MINIMARKET_EDIT_VALUES')) {
        Write-Output 'PACKAGE_FAIL: ATTRIBUTE_SUBFORM_NOT_INLINE'
        exit 1
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
    foreach ($tab in @('details', 'descriptions', 'images', 'videos', 'variants', 'seo', 'publishing')) {
        $xpath = "/form/fieldset[@name='" + $tab + "']"
        if ($null -eq $productForm.SelectSingleNode($xpath)) {
            Write-Output ('PACKAGE_FAIL: PRODUCT_TAB_MISSING_' + $tab)
            exit 1
        }
    }
    if ($null -eq $productForm.SelectSingleNode("/form/fieldset[@name='variants']/field[@name='variants' and @type='subform']") -or
        $null -eq $productForm.SelectSingleNode("/form/fieldset[@name='variants']/field[@name='variant_info' and @type='Variantinfo']")) {
        Write-Output 'PACKAGE_FAIL: VARIANT_SKU_FIELDS_MISSING'
        exit 1
    }
    if ($null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='image' and @type='media']") -or
        $null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='image_alt']")) {
        Write-Output 'PACKAGE_FAIL: PRODUCT_IMAGE_FIELDS_MISSING'
        exit 1
    }

    if ($null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='gallery' and @type='subform']") -or
        $null -eq $productForm.SelectSingleNode("/form/fieldset[@name='images']/field[@name='gallery_present']")) {
        Write-Output 'PACKAGE_FAIL: REPEATABLE_GALLERY_FIELDS_MISSING'
        exit 1
    }

    if ($null -eq $productForm.SelectSingleNode("/form/fieldset[@name='attributes']/field[@name='attribute_values' and @type='Productattributes']") -or
        $null -eq $productForm.SelectSingleNode("/form/fieldset[@name='attributes']/field[@name='attributes_present']")) {
        Write-Output 'PACKAGE_FAIL: PRODUCT_ATTRIBUTES_TAB_MISSING'
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
