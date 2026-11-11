param()
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'
$admin=Join-Path $component 'admin'
$native=@('products','categories','brands','collections','attributes','bundles',
    'groups','bundlegroups','orderoptions','values')
$bridged=@('orders','deliveries','reviews','promocodes','seofilters','feeds',
    'integrations','deliveryzones','deliverymethods','paymentmethods')

function Assert([bool]$condition,[string]$label){
    if(-not $condition){throw "MINIMARKET_090_NATIVE_LIST_FAIL: $label"}
}
function Source([string]$relative){
    $path=Join-Path $component $relative
    Assert (Test-Path -LiteralPath $path -PathType Leaf) "missing $relative"
    return Get-Content -LiteralPath $path -Raw -Encoding UTF8
}
[xml]$manifest=Source 'minimarket.xml'
Assert ([string]$manifest.extension.version -eq '0.5.90') 'version'
$display=Source 'admin\src\Controller\DisplayController.php'
Assert ($display.Contains("useScript('table.columns')")) 'Joomla columns web asset'
Assert ($display.Contains("useScript('multiselect')")) 'Joomla multiselect web asset'
Assert (-not $display.Contains("'com_minimarket.admin-columns'")) 'custom columns loaded'
Assert (-not $display.Contains("'com_minimarket.admin-list-tools'")) 'custom search loaded'
Assert ($display.Contains('NativeListTools::importRequest')) 'native filter POST mapping'
Assert (-not (Test-Path -LiteralPath (Join-Path $component 'media\js\admin-columns.js'))) 'legacy columns source'
Assert (-not (Test-Path -LiteralPath (Join-Path $component 'media\js\admin-list-tools.js'))) 'legacy search source'

$helper=Source 'admin\src\Service\NativeListTools.php'
foreach($item in @('Form::getInstance', 'joomla.searchtools.default', 'new Pagination',
    'filter_', 'fullordering', "'limit'")){
    Assert ($helper.Contains($item)) "native helper missing $item"
}
$shared=Source 'admin\tmpl\shared\searchtools.php'
Assert ($shared.Contains('NativeListTools::render')) 'all custom lists share real searchtools'
Assert (-not $shared.Contains('<form')) 'shared searchtools nests forms'
$css=Source 'media\css\admin-ui.css'
Assert (-not $css.Contains('.mm-columns-picker')) 'bespoke column CSS remains'
Assert (-not $css.Contains('.mm-native-searchtools')) 'bespoke search CSS remains'
Assert (-not $css.Contains('@container (max-width: 900px)')) 'table converted to cards'
Assert ($css.Contains('.mm-native-list-viewport')) 'local horizontal scroll missing'

foreach($view in $native){
    $template=Source "admin\tmpl\$view\default.php"
    Assert ($template.Contains("joomla.searchtools.default")) "$view not using native layout"
    Assert ($template.Contains('mm-native-list-viewport')) "$view missing table scroll"
    Assert ($template.Contains('id="adminForm"')) "$view missing Joomla adminForm"
    Assert (-not $template.Contains('colspan=')) "$view incompatible with native columns"
}
foreach($view in $bridged){
    $template=Source "admin\tmpl\$view\default.php"
    Assert (-not $template.Contains('mm-unified-list')) "$view old wrapper"
    Assert (-not $template.Contains('colspan=')) "$view incompatible empty row"
    Assert (-not $template.Contains('mm-native-pagination')) "$view bespoke pagination"
    if($view -in @('deliverymethods','paymentmethods')){
        Assert ($template.Contains('shared/method-list.php')) "$view method list partial"
    }else{
        Assert ($template.Contains('shared/searchtools.php')) "$view native SearchTools bridge"
        Assert ($template.Contains('mm-native-list-viewport')) "$view viewport"
        Assert ($template.Contains('id="adminForm"')) "$view adminForm"
        Assert ($template.Contains('NativeListTools::pagination')) "$view core pagination"
    }
}
$methods=Source 'admin\tmpl\shared\method-list.php'
Assert ($methods.Contains('NativeListTools::pagination')) 'methods core pagination'
Assert (-not $methods.Contains('colspan=')) 'methods empty row colspan'
Assert ($methods.Contains('id="adminForm"')) 'methods form'
$valuesFilter=Source 'admin\forms\filter_values.xml'
[xml]$null=$valuesFilter
foreach($item in @('name="search"', 'name="published"', 'name="attribute_id"',
    'name="fullordering"', 'name="limit"')){
    Assert ($valuesFilter.Contains($item)) "values filter missing $item"
}
$valuesModel=Source 'admin\src\Model\ValuesModel.php'
Assert ($valuesModel.Contains("getState('filter.search'")) 'values search backend'
Assert ($valuesModel.Contains("getState('filter.published'")) 'values state backend'
Assert ($valuesModel.Contains("getState('filter.attribute_id'")) 'values attribute backend'
$feeds=Source 'admin\tmpl\feeds\default.php'
Assert ($feeds.Contains('form="mm-feed-delete-')) 'feed delete form external'
Assert ($feeds.Contains('id="mm-feed-delete-')) 'feed delete form target'
$php=Get-Command php -ErrorAction SilentlyContinue
if($null -ne $php){
    foreach($relative in @(
        'admin\src\Service\NativeListTools.php',
        'admin\src\Service\MethodListRows.php',
        'admin\src\Model\ValuesModel.php',
        'admin\tmpl\shared\searchtools.php',
        'admin\tmpl\shared\method-list.php',
        'admin\src\Controller\DisplayController.php'
    )){
        $file=Join-Path $component $relative
        $result=& $php.Source -l $file 2>&1
        if($LASTEXITCODE -ne 0){
            Write-Output $result
            throw "MINIMARKET_090_PHP_LINT_FAIL: $relative"
        }
    }
    Write-Output 'MINIMARKET_090_PHP_LINT_PASS'
}else{
    Write-Output 'MINIMARKET_090_PHP_LINT_SKIPPED: php executable unavailable'
}
Write-Output "MINIMARKET_090_NATIVE_LIST_STRUCTURE_PASS: total=$($native.Count + $bridged.Count)"
