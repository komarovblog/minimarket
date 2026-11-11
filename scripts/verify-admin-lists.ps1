param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$admin = Join-Path $root 'src\com_minimarket\admin'
$media = Join-Path $root 'src\com_minimarket\media'
$native = @('products','categories','brands','collections','attributes','bundles',
    'groups','bundlegroups','orderoptions','values')
$custom = @('orders','deliveries','reviews','promocodes','seofilters','feeds','integrations')
$bulk = @('orders','deliveries','reviews','promocodes','seofilters')
$errors = New-Object System.Collections.Generic.List[string]

function Check([bool]$condition, [string]$message) {
    if (-not $condition) { $script:errors.Add($message) }
}
foreach ($view in $native) {
    $path = Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path -LiteralPath $path -PathType Leaf) "Missing native list: $view"
    if (Test-Path -LiteralPath $path -PathType Leaf) {
        $source = Get-Content -LiteralPath $path -Raw -Encoding UTF8
        Check ($source.Contains('mm-admin-list')) "Missing table: $view"
        Check ($source.Contains('mm-native-list-viewport')) "Missing scroll container: $view"
    }
}
foreach ($view in $custom) {
    $name = $view.Substring(0,1).ToUpper() + $view.Substring(1)
    $template = Join-Path $admin "tmpl\$view\default.php"
    $viewFile = Join-Path $admin ('src\View\' + $name + '\HtmlView.php')
    Check (Test-Path -LiteralPath $template -PathType Leaf) "Missing template: $view"
    Check (Test-Path -LiteralPath $viewFile -PathType Leaf) "Missing view: $view"
    if (Test-Path -LiteralPath $template -PathType Leaf) {
        $source = Get-Content -LiteralPath $template -Raw -Encoding UTF8
        Check ($source.Contains('mm-unified-list')) "Missing list wrapper: $view"
        Check ($source.Contains('shared/searchtools.php')) "Missing shared searchtools: $view"
        Check ($source.Contains('table-responsive')) "Missing table viewport: $view"
        Check ($source.Contains('mm-admin-list')) "Missing native table class: $view"
        Check (-not $source.Contains('<h2')) "Duplicate title: $view"
    }
    if (Test-Path -LiteralPath $viewFile -PathType Leaf) {
        $source = Get-Content -LiteralPath $viewFile -Raw -Encoding UTF8
        $expectedNamespace = 'namespace MiniMarket\Component\Minimarket\Administrator\View\' + $name + ';'
        Check ($source.Contains($expectedNamespace)) "Invalid view namespace: $view"
    }
}
foreach ($view in $bulk) {
    $name = $view.Substring(0,1).ToUpper() + $view.Substring(1)
    $controller = Join-Path $admin ('src\Controller\' + $name + 'Controller.php')
    $template = Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path -LiteralPath $controller -PathType Leaf) "Missing bulk controller: $view"
    if (Test-Path -LiteralPath $template -PathType Leaf) {
        $source = Get-Content -LiteralPath $template -Raw -Encoding UTF8
        Check ($source.Contains('grid.checkall')) "Missing select-all: $view"
        Check ($source.Contains('grid.id')) "Missing row checkboxes: $view"
        Check ($source.Contains('form.token')) "Missing CSRF token: $view"
    }
}
$required = @(
    (Join-Path $admin 'tmpl\shared\searchtools.php'),
    (Join-Path $admin 'src\Service\ListBulkActions.php'),
    (Join-Path $media 'js\admin-list-tools.js'),
    (Join-Path $media 'css\admin-ui.css')
)
foreach ($path in $required) {
    Check (Test-Path -LiteralPath $path -PathType Leaf) "Missing shared asset: $path"
}
if ($errors.Count -gt 0) {
    foreach ($problem in $errors) { Write-Output "ADMIN_LIST_FAIL: $problem" }
    throw "ADMIN_LIST_STRUCTURE_FAILED: $($errors.Count) problems"
}
Write-Output "ADMIN_LIST_STRUCTURE_PASS: native=$($native.Count) unified=$($custom.Count) bulk=$($bulk.Count)"

$php = Get-Command php -ErrorAction SilentlyContinue
if ($null -ne $php) {
    $phpFiles = @($required[0],$required[1])
    foreach ($view in $custom) {
        $name = $view.Substring(0,1).ToUpper() + $view.Substring(1)
        $phpFiles += Join-Path $admin "tmpl\$view\default.php"
        $phpFiles += Join-Path $admin ('src\View\' + $name + '\HtmlView.php')
    }
    foreach ($view in $bulk) {
        $name = $view.Substring(0,1).ToUpper() + $view.Substring(1)
        $phpFiles += Join-Path $admin ('src\Controller\' + $name + 'Controller.php')
    }
    foreach ($file in $phpFiles) {
        $result = & $php.Source -l $file
        if ($LASTEXITCODE -ne 0) {
            Write-Output $result
            throw "ADMIN_LIST_PHP_LINT_FAIL: $file"
        }
    }
    Write-Output "ADMIN_LIST_PHP_LINT_PASS: files=$($phpFiles.Count)"
} else {
    Write-Output 'ADMIN_LIST_PHP_LINT_SKIPPED: PHP not on PATH'
}
