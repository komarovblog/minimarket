param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'
$admin=Join-Path $component 'admin'
$native=@('products','categories','brands','collections','attributes','bundles',
    'groups','bundlegroups','orderoptions','values')
$unified=@('orders','deliveries','reviews','promocodes','seofilters','feeds',
    'registrationmethods','deliveryzones','deliverymethods','paymentmethods')
$bulk=@('orders','deliveries','reviews','promocodes','seofilters')
$errors=New-Object System.Collections.Generic.List[string]

function Check([bool]$condition,[string]$message) {
    if(-not $condition){$script:errors.Add($message)}
}

function ReadFile([string]$path) {
    Check (Test-Path -LiteralPath $path -PathType Leaf) "Missing $path"
    if(Test-Path -LiteralPath $path -PathType Leaf){
        return Get-Content -LiteralPath $path -Raw -Encoding UTF8
    }
    return ''
}
$phpFiles=New-Object System.Collections.Generic.List[string]
foreach($view in $native){
    $template=Join-Path $admin "tmpl\$view\default.php"
    $source=ReadFile $template
    Check ($source.Contains("joomla.searchtools.default")) "Not native Joomla SearchTools: $view"
    Check ($source.Contains('mm-native-list-viewport')) "Missing local table scroll: $view"
    Check ($source.Contains('mm-admin-list')) "Missing native table class: $view"
    Check ($source.Contains('id="adminForm"')) "Missing standard POST adminForm: $view"
    Check (-not $source.Contains('mm-unified-list')) "Legacy list container: $view"
    $phpFiles.Add($template)
}
foreach($view in $unified){
    $template=Join-Path $admin "tmpl\$view\default.php"
    $source=ReadFile $template
    $name=$view.Substring(0,1).ToUpper()+$view.Substring(1)
    $viewFile=Join-Path $admin ('src\View\'+$name+'\HtmlView.php')
    $viewSource=ReadFile $viewFile
    $expectedNamespace='namespace MiniMarket\Component\Minimarket\Administrator\View\'+$name+';'
    Check ($viewSource.Contains($expectedNamespace)) "Incorrect namespace: $view"
    Check (-not $source.Contains('mm-unified-list')) "Legacy page wrapper: $view"
    Check (-not $source.Contains('mm-native-pagination')) "Handmade pager: $view"
    if($view -in @('deliverymethods','paymentmethods','registrationmethods')){
        Check ($source.Contains("shared/method-list.php")) "Shared method list missing: $view"
        Check ($viewSource.Contains('MethodListRows::collect')) "Method list filtering missing: $view"
    }else{
        Check ($source.Contains('shared/searchtools.php')) "Joomla SearchTools bridge missing: $view"
        Check ($source.Contains('mm-native-list-viewport')) "Missing local table viewport: $view"
        Check ($source.Contains('id="adminForm"')) "Missing adminForm: $view"
        Check ($source.Contains('NativeListTools::pagination')) "Core pagination missing: $view"
    }
    $phpFiles.Add($template)
    $phpFiles.Add($viewFile)
}
foreach($view in $bulk){
    $name=$view.Substring(0,1).ToUpper()+$view.Substring(1)
    $controller=Join-Path $admin ('src\Controller\'+$name+'Controller.php')
    $source=ReadFile (Join-Path $admin "tmpl\$view\default.php")
    Check (Test-Path -LiteralPath $controller -PathType Leaf) "Missing bulk controller: $view"
    Check ($source.Contains('grid.checkall')) "Missing select-all: $view"
    Check ($source.Contains('grid.id')) "Missing row checkboxes: $view"
    Check ($source.Contains('form.token')) "Missing CSRF token: $view"
    $phpFiles.Add($controller)
}
$required=@(
    (Join-Path $admin 'src\Service\NativeListTools.php'),
    (Join-Path $admin 'src\Service\MethodListRows.php'),
    (Join-Path $admin 'src\Service\ListBulkActions.php'),
    (Join-Path $admin 'src\Model\ValuesModel.php'),
    (Join-Path $admin 'tmpl\shared\searchtools.php'),
    (Join-Path $admin 'tmpl\shared\method-list.php')
)
foreach($path in $required){
    $source=ReadFile $path
    Check ($source.StartsWith('<?php')) "Invalid PHP shared file: $path"
    $phpFiles.Add($path)
}
$filter=ReadFile (Join-Path $admin 'forms\filter_values.xml')
Check ($filter.Contains('name="attribute_id"')) 'Native attribute filter missing'
$controller=ReadFile (Join-Path $admin 'src\Controller\DisplayController.php')
Check ($controller.Contains("useScript('table.columns')")) 'Joomla core column switcher missing'
Check (-not $controller.Contains("'com_minimarket.admin-columns'")) 'Legacy custom columns still loaded'
Check (-not $controller.Contains("'com_minimarket.admin-list-tools'")) 'Legacy custom search script still loaded'
Check ($controller.Contains('NativeListTools::importRequest')) 'Native filters not received by custom views'
$css=ReadFile (Join-Path $component 'media\css\admin-ui.css')
Check (-not $css.Contains('@container (max-width: 900px)')) 'Non-native card mode remains'
Check (-not $css.Contains('.mm-columns-picker')) 'Custom column CSS remains'

if($errors.Count -gt 0){
    foreach($item in $errors){Write-Output "ADMIN_LIST_FAIL: $item"}
    throw "ADMIN_LIST_STRUCTURE_FAILED: $($errors.Count) issue(s)"
}
Write-Output "ADMIN_LIST_STRUCTURE_PASS: native=$($native.Count) unified=$($unified.Count) bulk=$($bulk.Count)"

$php=Get-Command php -ErrorAction SilentlyContinue
if($null -ne $php){
    $unique=@($phpFiles | Sort-Object -Unique)
    foreach($path in $unique){
        $result=& $php.Source -l $path 2>&1
        if($LASTEXITCODE -ne 0){
            Write-Output $result
            throw "ADMIN_LIST_PHP_LINT_FAIL: $path"
        }
    }
    Write-Output "ADMIN_LIST_PHP_LINT_PASS: files=$($unique.Count)"
}else{
    Write-Output 'ADMIN_LIST_PHP_LINT_SKIPPED: PHP not on PATH'
}
