param(
    [string]$OutputPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Собираем только собственный компонент, Joomla Core не затрагиваем.
$projectRoot = Split-Path -Parent $PSScriptRoot
$componentRoot = Join-Path $projectRoot 'src\com_minimarket'
$distribution = Join-Path $projectRoot 'dist'

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path $distribution 'com_minimarket.zip'
}

$OutputPath = [System.IO.Path]::GetFullPath($OutputPath)
$manifestPath = Join-Path $componentRoot 'minimarket.xml'
$needed = @(
    'minimarket.xml',
    'admin\config.xml',
    'admin\sql\install.mysql.sql',
    'admin\sql\updates\mysql\0.2.0.sql',
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

[xml]$manifest = Get-Content -LiteralPath $manifestPath -Raw -Encoding UTF8
if ($manifest.extension.type -ne 'component' -or $manifest.extension.element -ne 'com_minimarket') {
    throw 'Invalid MiniMarket component manifest.'
}

$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("minimarket-stage-" + [guid]::NewGuid().ToString('N'))
$temporaryZip = $null

try {
    New-Item -ItemType Directory -Path $stage -Force | Out-Null
    Copy-Item -LiteralPath $manifestPath -Destination $stage
    foreach ($folder in @('admin', 'site')) {
        Copy-Item -LiteralPath (Join-Path $componentRoot $folder) -Destination $stage -Recurse
    }

    $zipDirectory = Split-Path -Parent $OutputPath
    New-Item -ItemType Directory -Path $zipDirectory -Force | Out-Null
    $temporaryZip = Join-Path $zipDirectory ("minimarket-building-" + [guid]::NewGuid().ToString('N') + '.zip')

    Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $temporaryZip -CompressionLevel Optimal

    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [System.IO.Compression.ZipFile]::OpenRead($temporaryZip)

    try {
        $members = @($archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
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

        foreach ($entry in @('minimarket.xml', 'admin/services/provider.php', 'admin/sql/install.mysql.sql', 'admin/sql/updates/mysql/0.2.0.sql', 'admin/forms/category.xml', 'admin/forms/product.xml', 'admin/tmpl/product/edit.php', 'admin/tmpl/categories/default.php', 'site/tmpl/catalog/default.php', 'site/tmpl/category/default.php', 'site/tmpl/product/default.php', 'site/src/Service/Router.php', 'site/src/Helper/SeoHelper.php', 'admin/src/Extension/MinimarketComponent.php')) {
            if ($members -notcontains $entry) {
                throw "Invalid ZIP: missing $entry"
            }
        }
    }
    finally {
        $archive.Dispose()
    }

    Move-Item -LiteralPath $temporaryZip -Destination $OutputPath -Force
    Write-Output "MiniMarket ZIP: $OutputPath"
}
finally {
    if (Test-Path -LiteralPath $stage) {
        Remove-Item -LiteralPath $stage -Recurse -Force
    }
    if ($temporaryZip -and (Test-Path -LiteralPath $temporaryZip)) {
        Remove-Item -LiteralPath $temporaryZip -Force
    }
}
