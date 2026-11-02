param(
    [string]$OutputPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Собираем только собственный компонент, Joomla Core не затрагиваем.
$projectRoot = Split-Path -Parent $PSScriptRoot
$componentRoot = Join-Path $projectRoot 'src\com_minimarket'
$distribution = Join-Path $projectRoot 'dist'

$manifestPath = Join-Path $componentRoot 'minimarket.xml'
[xml]$manifest = Get-Content -LiteralPath $manifestPath -Raw -Encoding UTF8
$version = [string]$manifest.extension.version
if ($version -notmatch '^\d+\.\d+\.\d+$') {
    throw 'Invalid MiniMarket version in manifest'
}

# Version is the single source for the output filename.
if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path $distribution ("com_minimarket-" + $version + ".zip")
}
$OutputPath = [System.IO.Path]::GetFullPath($OutputPath)
$needed = @(
    'minimarket.xml',
    'admin\config.xml',
    'admin\sql\install.mysql.sql',
    'admin\sql\updates\mysql\0.2.0.sql',
    'admin\sql\updates\mysql\0.4.1.sql',
    'admin\sql\updates\mysql\0.4.2.sql',
    'admin\sql\updates\mysql\0.5.0.sql',
    'admin\sql\updates\mysql\0.5.2.sql',
    'admin\sql\updates\mysql\0.5.3.sql',
    'admin\sql\updates\mysql\0.5.4.sql',
    'admin\sql\updates\mysql\0.5.5.sql',
    'admin\sql\updates\mysql\0.5.7.sql',
    'admin\src\Service\ProductAttributes.php',
    'admin\src\Field\ProductattributesField.php',
    'media\js\product-attributes.js',
    'admin\src\Service\CategoryAttributes.php',
    'admin\src\Field\CategoryattributesField.php',
    'admin\src\Field\AttributecategoriesField.php',
    'admin\src\Service\BulkDeleteGuard.php',
    'admin\src\Controller\CategoriesController.php',
    'admin\src\Controller\ProductsController.php',
    'admin\src\Controller\AttributesController.php',
    'admin\src\Controller\ValuesController.php',
    'admin\forms\attribute.xml',
    'admin\forms\value.xml',
    'admin\src\Table\AttributeTable.php',
    'admin\src\Table\ValueTable.php',
    'admin\tmpl\attributes\default.php',
    'admin\tmpl\values\default.php',
    'admin\forms\product_gallery.xml',
    'admin\src\Service\ProductGallery.php',
    'media\js\gallery.js',
    'media\css\gallery.css',
    'admin\src\Service\MediaPath.php',
    'media\js\clean-media.js',
    'admin\src\Field\ProductcategoryField.php',
    'admin\forms\category.xml',
    'admin\src\Table\CategoryTable.php',
    'admin\src\Table\ProductTable.php',
    'admin\src\Controller\ProductController.php',
    'admin\src\Model\ProductModel.php',
    'admin\src\View\Product\HtmlView.php',
    'admin\tmpl\product\edit.php',
    'admin\forms\product.xml',
    'admin\src\Controller\CategoryController.php',
    'admin\src\Model\CategoryModel.php',
    'admin\src\Model\CategoriesModel.php',
    'admin\tmpl\category\edit.php',
    'admin\tmpl\categories\default.php',
    'admin\services\provider.php',
    'admin\src\Controller\DisplayController.php',
    'admin\src\View\Dashboard\HtmlView.php',
    'admin\tmpl\dashboard\default.php',
    'site\src\Controller\DisplayController.php',
    'site\src\Service\Router.php',
    'site\src\Service\CatalogRepository.php',
    'site\src\Helper\RouteHelper.php',
    'site\src\Helper\SeoHelper.php',
    'site\src\View\Product\HtmlView.php',
    'site\src\View\Category\HtmlView.php',
    'site\tmpl\product\default.php',
    'site\tmpl\category\default.php',
    'admin\src\Extension\MinimarketComponent.php',
    'site\src\View\Catalog\HtmlView.php',
    'site\tmpl\catalog\default.php'
)

foreach ($relative in $needed) {
    if (-not (Test-Path -LiteralPath (Join-Path $componentRoot $relative) -PathType Leaf)) {
        throw "Missing component file: $relative"
    }
}

if ($manifest.extension.type -ne 'component' -or $manifest.extension.element -ne 'com_minimarket') {
    throw 'Invalid MiniMarket component manifest.'
}

$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("minimarket-stage-" + [guid]::NewGuid().ToString('N'))
$temporaryZip = $null

try {
    New-Item -ItemType Directory -Path $stage -Force | Out-Null
    Copy-Item -LiteralPath $manifestPath -Destination $stage
    foreach ($folder in @('admin', 'site', 'media')) {
        Copy-Item -LiteralPath (Join-Path $componentRoot $folder) -Destination $stage -Recurse
    }

    $zipDirectory = Split-Path -Parent $OutputPath
    New-Item -ItemType Directory -Path $zipDirectory -Force | Out-Null
    $temporaryZip = Join-Path $zipDirectory ("minimarket-building-" + [guid]::NewGuid().ToString('N') + '.zip')

    # Joomla/PHP need forward-slash ZIP member names on Windows.
    # Do not let Compress-Archive choose platform-dependent entry paths.
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $writer = [System.IO.Compression.ZipFile]::Open($temporaryZip, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        $files = @(Get-ChildItem -LiteralPath $stage -Recurse -File | Sort-Object FullName)
        if ($files.Count -eq 0) {
            throw 'ZIP_STAGE_EMPTY'
        }
        foreach ($file in $files) {
            $entryName = $file.FullName.Substring($stage.Length + 1).Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $writer,
                $file.FullName,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
        }
    }
    finally {
        $writer.Dispose()
    }

    $archive = [System.IO.Compression.ZipFile]::OpenRead($temporaryZip)

    try {
        # Validate raw ZIP names, not normalized names hiding incompatibility.
        $members = @($archive.Entries | ForEach-Object { $_.FullName })
        if (@($members | Where-Object { $_.Contains('\') -or $_.StartsWith('/') }).Count -gt 0) {
            throw 'ZIP_NONPORTABLE_ENTRY_PATHS'
        }
        # The Joomla installer reads a top-level <extension> XML document.
        # Presence of an .xml filename alone is not enough.
        $manifestEntry = $archive.GetEntry('minimarket.xml')
        if ($null -eq $manifestEntry) {
            throw 'ZIP_MANIFEST_NOT_AT_ROOT'
        }
        $manifestReader = New-Object System.IO.StreamReader($manifestEntry.Open())
        try {
            [xml]$zipManifest = $manifestReader.ReadToEnd()
        }
        finally {
            $manifestReader.Dispose()
        }
        if (
            $null -eq $zipManifest.DocumentElement -or
            $zipManifest.DocumentElement.LocalName -ne 'extension' -or
            $zipManifest.DocumentElement.GetAttribute('type') -ne 'component' -or
            $zipManifest.extension.element -ne 'com_minimarket'
        ) {
            throw 'ZIP_INVALID_JOOMLA_MANIFEST'
        }

        foreach ($entry in @('minimarket.xml', 'admin/services/provider.php', 'admin/sql/install.mysql.sql', 'admin/sql/updates/mysql/0.2.0.sql', 'admin/forms/category.xml', 'admin/forms/product.xml', 'admin/tmpl/product/edit.php', 'admin/sql/updates/mysql/0.5.2.sql', 'media/js/clean-media.js', 'admin/tmpl/categories/default.php', 'site/tmpl/catalog/default.php', 'site/tmpl/category/default.php', 'site/tmpl/product/default.php', 'site/src/Service/Router.php', 'site/src/Helper/SeoHelper.php', 'admin/src/Extension/MinimarketComponent.php')) {
            if ($members -notcontains $entry) {
                throw "Invalid ZIP: missing $entry"
            }
        }
    }
    finally {
        $archive.Dispose()
    }

    Move-Item -LiteralPath $temporaryZip -Destination $OutputPath -Force
    Write-Output "MiniMarket ZIP $version : $OutputPath"
}
finally {
    if (Test-Path -LiteralPath $stage) {
        Remove-Item -LiteralPath $stage -Recurse -Force
    }
    if ($temporaryZip -and (Test-Path -LiteralPath $temporaryZip)) {
        Remove-Item -LiteralPath $temporaryZip -Force
    }
}
