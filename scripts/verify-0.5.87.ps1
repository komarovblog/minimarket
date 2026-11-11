param()
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'
$files=@(
 'admin\sql\updates\mysql\0.5.87.sql',
 'admin\sql\install.mysql.sql',
 'admin\src\Service\DeliveryZoneData.php',
 'admin\src\Controller\DeliveryzoneController.php',
 'admin\src\Controller\DeliveryzonesController.php',
 'admin\src\View\Deliveryzone\HtmlView.php',
 'admin\src\View\Deliveryzones\HtmlView.php',
 'admin\tmpl\deliveryzone\edit.php',
 'admin\tmpl\deliveryzones\default.php',
 'admin\tmpl\shared\reference-picker.php',
 'admin\forms\seofilter_media.xml',
 'admin\tmpl\seofilter\create.php',
 'admin\src\View\Seofilter\HtmlView.php',
 'admin\src\Service\SeoFilterData.php',
 'admin\tmpl\promocode\create.php',
 'admin\src\Controller\PromocodeController.php',
 'admin\tmpl\review\create.php',
 'admin\tmpl\order\create.php',
 'admin\tmpl\delivery\create.php',
 'admin\src\Controller\ProductpickerController.php',
 'site\src\Service\DeliveryZones.php',
 'site\src\Service\PromotionService.php',
 'site\src\Service\CheckoutDelivery.php',
 'site\src\Service\OrderStorage.php',
 'site\src\Controller\CheckoutController.php',
 'site\src\Controller\CartController.php',
 'site\src\Service\CartCatalog.php',
 'site\tmpl\checkout\default.php',
 'site\tmpl\cart\default.php',
 'media\js\admin-reference-picker.js',
 'media\js\manual-order.js',
 'media\js\checkout.js'
)
foreach($file in $files){
 if(-not (Test-Path -LiteralPath (Join-Path $component $file) -PathType Leaf)){
  throw "MINIMARKET_087_MISSING: $file"
 }
}
[xml]$xml=Get-Content -LiteralPath (Join-Path $component 'minimarket.xml') -Raw -Encoding UTF8
if([string]$xml.extension.version -ne '0.5.87'){throw 'MINIMARKET_087_VERSION_FAIL'}
$sql=Get-Content -LiteralPath (Join-Path $component 'admin\sql\updates\mysql\0.5.87.sql') -Raw -Encoding UTF8
$install=Get-Content -LiteralPath (Join-Path $component 'admin\sql\install.mysql.sql') -Raw -Encoding UTF8
foreach($name in @('delivery_zones','promo_redemptions','shipping_weight_grams',
 'shipping_volume_cm3','promo_code_id','discount_cents','delivery_postcode','og_image')){
 if(-not $sql.Contains($name) -or -not $install.Contains($name)){
  throw "MINIMARKET_087_SCHEMA_MISMATCH: $name"
 }
}
$locales=@(
 @('admin\language\ru-RU\com_minimarket.ini','admin\language\en-GB\com_minimarket.ini'),
 @('site\language\ru-RU\com_minimarket.ini','site\language\en-GB\com_minimarket.ini'),
 @('admin\language\ru-RU\com_minimarket.sys.ini','admin\language\en-GB\com_minimarket.sys.ini')
)
foreach($pair in $locales){
 $sets=@()
 foreach($name in $pair){
  $txt=Get-Content -LiteralPath (Join-Path $component $name) -Raw -Encoding UTF8
  $keys=@([regex]::Matches($txt,'(?m)^[A-Z][A-Z0-9_]*(?==)') |
    ForEach-Object {$_.Value})
  if($keys.Count -ne @($keys | Sort-Object -Unique).Count){
   throw "MINIMARKET_087_DUPLICATE_LANGUAGE_KEY: $name"
  }
  $sets+=,@($keys | Sort-Object)
 }
 if(($sets[0] -join '|') -ne ($sets[1] -join '|')){
  throw "MINIMARKET_087_LANGUAGE_PARITY_FAIL: $($pair[0]) / $($pair[1])"
 }
}
Write-Output 'MINIMARKET_087_SCHEMA_AND_LANGUAGE_PARITY_PASS'
$php=Get-Command php -ErrorAction SilentlyContinue
if($null -eq $php){
 Write-Output 'MINIMARKET_087_PHP_LINT_SKIPPED: PHP not in PATH'
}else{
 $phpFiles=@($files | Where-Object {$_ -like '*.php'})
 foreach($file in $phpFiles){
  $path=Join-Path $component $file
  $output=& $php.Source -l $path 2>&1
  if($LASTEXITCODE -ne 0){
   Write-Output $output
   throw "MINIMARKET_087_PHP_LINT_FAIL: $file"
  }
 }
 Write-Output "MINIMARKET_087_PHP_LINT_PASS: files=$($phpFiles.Count)"
}
