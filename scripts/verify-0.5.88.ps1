param()
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'
[xml]$manifest=Get-Content -LiteralPath (Join-Path $component 'minimarket.xml') -Raw -Encoding UTF8
if([string]$manifest.extension.version -ne '0.5.88') {
    throw 'MINIMARKET_088_VERSION_MISMATCH'
}
$source=@{
 'product'='admin\tmpl\product\edit.php'
 'manual_order'='admin\tmpl\order\create.php'
 'manual_api'='admin\src\Controller\ProductpickerController.php'
 'manual_js'='media\js\manual-order.js'
 'columns_js'='media\js\admin-columns.js'
 'promo'='site\src\Service\PromotionService.php'
 'shipping'='site\src\Service\DeliveryZones.php'
 'checkout'='site\src\Controller\CheckoutController.php'
 'seo'='admin\tmpl\seofilter\create.php'
}
$text=@{}
foreach($name in $source.Keys) {
    $file=Join-Path $component $source[$name]
    if(-not (Test-Path -LiteralPath $file -PathType Leaf)) {
        throw "MINIMARKET_088_FILE_MISSING: $file"
    }
    $text[$name]=Get-Content -LiteralPath $file -Raw -Encoding UTF8
}
foreach($key in @('shipping_weight_grams','shipping_volume_cm3')) {
    if(-not $text['product'].Contains("renderField('$key')")) {
        throw "MINIMARKET_088_MISSING_PRODUCT_METRIC: $key"
    }
}
foreach($key in @('data-mm-manual-picker-url','data-mm-manual-category','data-mm-manual-results')) {
    if(-not $text['manual_order'].Contains($key)) {
        throw "MINIMARKET_088_INVALID_SKU_PICKER: $key"
    }
}
if($text['manual_order'].Contains('echo $this- data-mm-manual-picker')) {
    throw 'MINIMARKET_088_MALFORMED_SKU_PICKER'
}
if(-not $text['manual_api'].Contains('category_id') -or
   -not $text['manual_js'].Contains("root.searchParams.set('category_id'"))){
    throw 'MINIMARKET_088_CATEGORY_LOOKUP_MISSING'
}
if(-not $text['columns_js'].Contains("minimarket.admin.columns.v3.") -or
   -not $text['columns_js'].Contains("view==='bundles'")){
    throw 'MINIMARKET_088_RESPONSIVE_COLUMN_DEFAULT_MISSING'
}
foreach($key in @('usage_limit','per_customer_limit','FOR UPDATE','discount_cents')) {
    if(-not $text['promo'].Contains($key)) {
        throw "MINIMARKET_088_PROMO_CHECK_MISSING: $key"
    }
}
foreach($key in @('per_kg','per_liter','free_from_cents','postcode_prefixes')) {
    if(-not $text['shipping'].Contains($key)){
        throw "MINIMARKET_088_SHIPPING_CHECK_MISSING: $key"
    }
}
foreach($key in @('data-mm-seo-facet','data-mm-seo-stock','og_image','mm-seo-price-min')) {
    if(-not $text['seo'].Contains($key)){
        throw "MINIMARKET_088_SEO_CHECK_MISSING: $key"
    }
}
$forms=Get-ChildItem -LiteralPath (Join-Path $component 'admin\forms') -Filter 'filter_*.xml' -File
foreach($file in $forms){
    $body=Get-Content -LiteralPath $file.FullName -Raw -Encoding UTF8
    if($body.Contains('JOPTION_SELECT_ALL')) {
        throw "MINIMARKET_088_RAW_JOPTION_TRANSLATION: $($file.Name)"
    }
}
foreach($locale in @('ru-RU','en-GB')) {
    $file=Join-Path $component "admin\language\$locale\com_minimarket.ini"
    $contents=Get-Content -LiteralPath $file -Raw -Encoding UTF8
    if(-not $contents.Contains('COM_MINIMARKET_PRODUCT_ALL_TYPES=')) {
        throw "MINIMARKET_088_UNTRANSLATED_FILTER: $locale"
    }
}
Write-Output 'MINIMARKET_088_STATIC_REGRESSION_PASS'
$php=Get-Command php -ErrorAction SilentlyContinue
if($null -eq $php){
    Write-Output 'MINIMARKET_088_PHP_TEST_SKIPPED: php executable not on PATH'
}else{
    foreach($key in @('product','manual_order','manual_api','promo','shipping','checkout','seo')){
        $file=Join-Path $component $source[$key]
        $result=& $php.Source -l $file 2>&1
        if($LASTEXITCODE -ne 0){Write-Output $result;throw "MINIMARKET_088_PHP_LINT_FAIL: $file"}
    }
    & $php.Source (Join-Path $PSScriptRoot 'verify-commerce-logic.php')
    if($LASTEXITCODE -ne 0){throw 'MINIMARKET_088_COMMERCE_LOGIC_FAIL'}
    Write-Output 'MINIMARKET_088_PHP_LINT_AND_COMMERCE_PASS'
}
