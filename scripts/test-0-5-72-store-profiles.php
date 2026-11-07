<?php
/** Three global MiniMarket modes, default and Joomla translations. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$config=simplexml_load_file($root.'admin/config.xml');
if($config===false)throw new RuntimeException('MM72_XML');
$fields=$config->xpath('/config/fieldset[@name="basic"]/field[@name="shop_type"]');
if(count($fields)!==1)throw new RuntimeException('MM72_FIELD');
$values=[];
foreach($fields[0]->option as $option)$values[]=(string)$option['value'];
if($values!==['physical','digital','food_delivery'] ||
    (string)$fields[0]['default']!=='physical')throw new RuntimeException('MM72_VALUES');
foreach(['ru-RU','en-GB'] as $lang){
    $labels=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
    if(!is_array($labels))throw new RuntimeException('MM72_LOCALE_FILE');
    foreach(['PHYSICAL','DIGITAL','FOOD_DELIVERY'] as $mode){
        if(empty($labels['COM_MINIMARKET_SHOP_TYPE_'.$mode])){
            throw new RuntimeException('MM72_LOCALE_'.$lang.'_'.$mode);
        }
    }
}
$table=file_get_contents($root.'admin/src/Table/ProductTable.php');
if(!str_contains($table,"['physical','digital']")){
    throw new RuntimeException('MM72_KEEP_PRODUCT_DELIVERY_TYPES_UNCHANGED');
}
echo "MM72_SHOP_PROFILES_OK: three global modes, both languages\n";
