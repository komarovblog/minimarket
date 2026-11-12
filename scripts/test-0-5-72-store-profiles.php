<?php
/** Shop configuration is product-specific: no global physical/digital/food store mode. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$config=simplexml_load_file($root.'admin/config.xml');
if($config===false)throw new RuntimeException('MM72_XML');
if(count($config->xpath('//field[@name="shop_type"]'))!==0)
    throw new RuntimeException('MM72_GLOBAL_MODE_STILL_PRESENT');
$product=simplexml_load_file($root.'admin/forms/product.xml');
if($product===false)throw new RuntimeException('MM72_PRODUCT_XML');
$fields=$product->xpath('//field[@name="delivery_type"]');
if(count($fields)!==1)throw new RuntimeException('MM72_DELIVERY_TYPE_FIELD');
$values=[];
foreach($fields[0]->option as $option)$values[]=(string)$option['value'];
if($values!==['physical','digital'] ||(string)$fields[0]['default']!=='physical')
    throw new RuntimeException('MM72_PRODUCT_TYPES');
foreach(['ru-RU','en-GB'] as $lang){
    $labels=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
    if(!is_array($labels))throw new RuntimeException('MM72_LOCALE');
    foreach(['PHYSICAL','DIGITAL'] as $type)
        if(empty($labels['COM_MINIMARKET_DELIVERY_TYPE_'.$type]))
            throw new RuntimeException('MM72_LOCALE_TYPE_'.$lang.'_'.$type);
}
$table=(string)file_get_contents($root.'admin/src/Table/ProductTable.php');
if(!str_contains($table,"['physical','digital']") ||str_contains($table,"get('shop_type'"))
    throw new RuntimeException('MM72_PRODUCT_VALIDATION');
echo "MM72_SHOP_PROFILES_OK: physical and digital on products, no global mode\n";
