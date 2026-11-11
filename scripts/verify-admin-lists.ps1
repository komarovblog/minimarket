param()
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$admin=Join-Path $root 'src\com_minimarket\admin'
$media=Join-Path $root 'src\com_minimarket\media'
$native=@('products','categories','brands','collections','attributes','bundles','groups','bundlegroups','orderoptions','values')
$custom=@('orders','deliveries','reviews','promocodes','seofilters','feeds','integrations')
$bulk=@('orders','deliveries','reviews','promocodes','seofilters')
$errors=New-Object System.Collections.Generic.List[string]

function Check([bool]$condition,[string]$message) {
    if (-not $condition) { $script:errors.Add($message) }
}
foreach($view in $native) {
    $file=Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path $file -PathType Leaf) "Missing Joomla list: $view"
    if (Test-Path $file -PathType Leaf) {
        $text=Get-Content $file -Raw -Encoding UTF8
        Check ($text.Contains('mm-admin-list')) "Joomla list table missing: $view"
        Check ($text.Contains('mm-native-list-viewport')) "Native table viewport missing: $view"
    }
}
foreach($view in $custom) {
    $file=Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path $file -PathType Leaf) "Missing custom list: $view"
    if (-not (Test-Path $file -PathType Leaf)) { continue }
    $text=Get-Content $file -Raw -Encoding UTF8
    Check ($text.Contains('mm-unified-list')) "List wrapper missing: $view"
    Check ($text.Contains('shared/searchtools.php')) "Searchtools missing: $view"
    Check ($text.Contains('table-responsive')) "Overflow protection missing: $view"
    Check ($text.Contains('mm-admin-list')) "Native table class missing: $view"
    Check (-not $text.Contains('<h2')) "Duplicate in-page heading: $view"
}
foreach($view in $custom) {
    $name=$view.Substring(0,1).ToUpper()+$view.Substring(1)
    $file=Join-Path $admin "src\View\$name\HtmlView.php"
    Check (Test-Path $file -PathType Leaf) "Custom view missing: $view"
    if(Test-Path $file -PathType Leaf) {
        $text=Get-Content $file -Raw -Encoding UTF8
        Check (-not $text.Contains('
    $controller=Join-Path $admin "src\Controller\$($view.Substring(0,1).ToUpper()+$view.Substring(1))Controller.php"
    $template=Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path $controller -PathType Leaf) "Missing bulk controller: $view"
    if (Test-Path $template -PathType Leaf) {
        $text=Get-Content $template -Raw -Encoding UTF8
        Check ($text.Contains('grid.checkall')) "Bulk header missing: $view"
        Check ($text.Contains('grid.id')) "Bulk row checkboxes missing: $view"
        Check ($text.Contains('form.token')) "CSRF token missing: $view"
    }
}
$required=@(
    (Join-Path $admin 'tmpl\shared\searchtools.php'),
    (Join-Path $admin 'src\Service\ListBulkActions.php'),
    (Join-Path $media 'js\admin-list-tools.js'),
    (Join-Path $media 'css\admin-ui.css')
)
foreach($file in $required) { Check (Test-Path $file -PathType Leaf) "Missing shared asset: $file" }
if($errors.Count -gt 0) {
    foreach($problem in $errors) { Write-Error "ADMIN_LIST_FAIL: $problem" }
    exit 1
}
Write-Output "ADMIN_LIST_STRUCTURE_PASS: native=$($native.Count) unified=$($custom.Count) bulk=$($bulk.Count)"
# The browser-level acceptance remains separate; never claim this checks pixels.
$php=Get-Command php -ErrorAction SilentlyContinue
if($null -ne $php) {
    $phpFiles=@($required[0],$required[1])
    foreach($view in $custom) {
        $phpFiles+=Join-Path $admin "tmpl\$view\default.php"
        $phpFiles+=Join-Path $admin "src\View\$($view.Substring(0,1).ToUpper()+$view.Substring(1))\HtmlView.php"
    }
    foreach($view in $bulk) {
        $phpFiles+=Join-Path $admin "src\Controller\$($view.Substring(0,1).ToUpper()+$view.Substring(1))Controller.php"
    }
    foreach($file in $phpFiles) {
        & $php.Source -l $file | Out-Null
        if($LASTEXITCODE -ne 0) { throw "ADMIN_LIST_PHP_LINT_FAIL: $file" }
    }
    Write-Output "ADMIN_LIST_PHP_LINT_PASS: files=$($phpFiles.Count)"
} else {
    Write-Output 'ADMIN_LIST_PHP_LINT_SKIPPED: PHP not on PATH'
}
+'{')) "Unexpanded PHP namespace placeholder: $view"
        Check ($text.Contains("Administrator\View\$name;")) "Incorrect PHP namespace: $view"
    }
}
foreach($view in $bulk) {
    $controller=Join-Path $admin "src\Controller\$($view.Substring(0,1).ToUpper()+$view.Substring(1))Controller.php"
    $template=Join-Path $admin "tmpl\$view\default.php"
    Check (Test-Path $controller -PathType Leaf) "Missing bulk controller: $view"
    if (Test-Path $template -PathType Leaf) {
        $text=Get-Content $template -Raw -Encoding UTF8
        Check ($text.Contains('grid.checkall')) "Bulk header missing: $view"
        Check ($text.Contains('grid.id')) "Bulk row checkboxes missing: $view"
        Check ($text.Contains('form.token')) "CSRF token missing: $view"
    }
}
$required=@(
    (Join-Path $admin 'tmpl\shared\searchtools.php'),
    (Join-Path $admin 'src\Service\ListBulkActions.php'),
    (Join-Path $media 'js\admin-list-tools.js'),
    (Join-Path $media 'css\admin-ui.css')
)
foreach($file in $required) { Check (Test-Path $file -PathType Leaf) "Missing shared asset: $file" }
if($errors.Count -gt 0) {
    foreach($problem in $errors) { Write-Error "ADMIN_LIST_FAIL: $problem" }
    exit 1
}
Write-Output "ADMIN_LIST_STRUCTURE_PASS: native=$($native.Count) unified=$($custom.Count) bulk=$($bulk.Count)"
# The browser-level acceptance remains separate; never claim this checks pixels.
$php=Get-Command php -ErrorAction SilentlyContinue
if($null -ne $php) {
    $phpFiles=@($required[0],$required[1])
    foreach($view in $custom) {
        $phpFiles+=Join-Path $admin "tmpl\$view\default.php"
        $phpFiles+=Join-Path $admin "src\View\$($view.Substring(0,1).ToUpper()+$view.Substring(1))\HtmlView.php"
    }
    foreach($view in $bulk) {
        $phpFiles+=Join-Path $admin "src\Controller\$($view.Substring(0,1).ToUpper()+$view.Substring(1))Controller.php"
    }
    foreach($file in $phpFiles) {
        & $php.Source -l $file | Out-Null
        if($LASTEXITCODE -ne 0) { throw "ADMIN_LIST_PHP_LINT_FAIL: $file" }
    }
    Write-Output "ADMIN_LIST_PHP_LINT_PASS: files=$($phpFiles.Count)"
} else {
    Write-Output 'ADMIN_LIST_PHP_LINT_SKIPPED: PHP not on PATH'
}
