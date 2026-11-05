<?php
/** Deterministic unit and Joomla admin contract checks for bundle products. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\BundleSmoke {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/BundleItems.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/BundleStock.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\BundleItems as Items;
    use MiniMarket\Component\Minimarket\Administrator\Service\BundleStock as Stock;
    $n=0;
    function ok(bool $value,string $description):void {
        global $n; ++$n;
        if (!$value)throw new \RuntimeException('BUNDLE_SMOKE_FAIL: '.$description);
    }
    $valid=Items::normalize([
        ['component'=>'product:1002','quantity'=>'2'],
        ['component'=>'variant:7004','quantity'=>1],
        ['component'=>'product:1090','quantity'=>'1'],
    ]);
    ok(count($valid)===3,'product, exact SKU and nested bundle component accepted');
    ok($valid[1]===['kind'=>'variant','id'=>7004,'quantity'=>1],
        'one exact SKU carries its requested quantity');
    ok(Items::normalize([])===[],'draft bundle may start without components');
    foreach ([
        [['component'=>'product:0','quantity'=>1]],
        [['component'=>'product:-1','quantity'=>1]],
        [['component'=>'variant:1','quantity'=>0]],
        [['component'=>'product:1','quantity'=>1001]],
        [['component'=>'product:1','quantity'=>'1.5']],
        [['component'=>'product:abc','quantity'=>1]],
        [['component'=>'product:1','quantity'=>1],['component'=>'product:1','quantity'=>1]],
        [['component'=>'variant:7004','quantity'=>1],['component'=>'variant:7004','quantity'=>3]],
    ] as $bad) {
        try { Items::normalize($bad); ok(false,'invalid component admitted'); }
        catch (\InvalidArgumentException $e) {ok(true,'invalid component rejected');}
    }
    $snapshot=[
        'products'=>[
            1090=>['category_id'=>1,'product_type'=>'bundle','state'=>1,'stock'=>0,'option_stock_mode'=>0],
            1091=>['category_id'=>1,'product_type'=>'bundle','state'=>1,'stock'=>0,'option_stock_mode'=>0],
            1002=>['category_id'=>1,'product_type'=>'simple','state'=>1,'stock'=>14,'option_stock_mode'=>0],
            1003=>['category_id'=>1,'product_type'=>'simple','state'=>1,'stock'=>28,'option_stock_mode'=>0],
            1012=>['category_id'=>1,'product_type'=>'simple','state'=>1,'stock'=>0,'option_stock_mode'=>1],
        ],
        'categories'=>[1=>['parent_id'=>0,'state'=>1]],
        'variants'=>[7004=>['product_id'=>1012,'stock'=>9,'state'=>1]],
        'hasVariants'=>[1012=>true],
        'items'=>[
            1090=>[
                ['component_product_id'=>1002,'component_variant_id'=>null,'quantity'=>1],
                ['component_product_id'=>1003,'component_variant_id'=>null,'quantity'=>2],
            ],
            1091=>[
                ['component_product_id'=>1090,'component_variant_id'=>null,'quantity'=>1],
                ['component_product_id'=>1003,'component_variant_id'=>null,'quantity'=>1],
            ],
        ],
    ];
    ok(Stock::available(1090,$snapshot)===14,'minimum of stock/quantity');
    ok(Stock::available(1091,$snapshot)===9,'nested bundle sums shared leaf stock 28/(2+1)');
    $snapshot['items'][1091][]= [
        'component_product_id'=>1012,'component_variant_id'=>7004,'quantity'=>1,
    ];
    ok(Stock::available(1091,$snapshot)===9,'exact SKU constrains nested availability');
    $snapshot['variants'][7004]['state']=0;
    ok(Stock::available(1091,$snapshot)===0,'inactive variant makes bundle unavailable');
    $snapshot['variants'][7004]['state']=1;
    $snapshot['products'][1003]['state']=0;
    ok(Stock::available(1091,$snapshot)===0,'unpublished component unavailable');
    $snapshot['products'][1003]['state']=1;
    $snapshot['items'][1090][]= [
        'component_product_id'=>1091,'component_variant_id'=>null,'quantity'=>1
    ];
    ok(Stock::available(1091,$snapshot)===0,'cycle does not loop indefinitely');
    $base=__DIR__.'/../src/com_minimarket/';
    $form=simplexml_load_file($base.'admin/forms/product.xml');
    ok($form!==false,'product form parses as XML');
    ok(count($form->xpath("//fieldset[@name='details']/field[@name='product_type']"))===1,
        'simple or bundle type in native Joomla form');
    ok(count($form->xpath("//fieldset[@name='bundle']/field[@name='bundle_items' and @type='Bundlepicker']"))===1,
        'searchable bundle SKU picker');
    $subform=simplexml_load_file($base.'admin/forms/product_bundle_item.xml');
    ok($subform!==false && count($subform->xpath("//field[@name='quantity']"))===1,
        'component line has quantity field');
    $model=file_get_contents($base.'admin/src/Model/ProductModel.php');
    ok(str_contains($model,'BundleItems::validate(')
        && str_contains($model,'BundleItems::replace(')
        && str_contains($model,'transactionRollback()'),
        'component save shares product transaction');
    $productTable=file_get_contents($base.'admin/src/Table/ProductTable.php');
    ok(str_contains($productTable,"'simple','bundle'"),
        'product type value has server-side allowlist');
    $schema=file_get_contents($base.'admin/sql/updates/mysql/0.5.51.sql');
    ok(str_contains($schema,'ADD COLUMN') &&
        str_contains($schema,'ON DELETE RESTRICT') &&
        str_contains($schema,'ON DELETE CASCADE') &&
        !preg_match('/\bDROP\s+TABLE|\bTRUNCATE\b/i',$schema),
        'additive schema protects used components and removes deleted parent lines');
    $seed=file_get_contents(__DIR__.'/../sample-data/minimarket_bundles_demo_0_5_51.sql');
    ok(str_contains($seed,'BUNDLE-1090')
        && str_contains($seed,'BUNDLE-1091')
        && str_contains($seed,'BUNDLE-1092')
        && str_contains($seed,'7004')
        && !preg_match('/^\s*(DROP|DELETE|TRUNCATE|ALTER)\b/im',$seed),
        'separate demo bundle seed is additive and uses exact SKU IDs');
    require __DIR__.'/../src/com_minimarket/admin/src/Service/BundlePrice.php';
    $pricing=[
        'products'=>[
            1002=>['price'=>'1000.00','product_type'=>'simple'],
            1003=>['price'=>'500.00','product_type'=>'simple'],
            1090=>['price'=>'1990.00','product_type'=>'bundle','bundle_price_mode'=>'fixed','bundle_discount_pct'=>'0.00'],
            1091=>['price'=>'0.00','product_type'=>'bundle','bundle_price_mode'=>'discount','bundle_discount_pct'=>'10.00'],
            1092=>['price'=>'0.00','product_type'=>'bundle','bundle_price_mode'=>'discount','bundle_discount_pct'=>'15.00']
        ],
        'items'=>[
            1090=>[
                ['component_product_id'=>1002,'quantity'=>1],
                ['component_product_id'=>1003,'quantity'=>2],
            ],
            1091=>[
                ['component_product_id'=>1002,'quantity'=>2],
                ['component_product_id'=>1003,'quantity'=>1],
            ],
            1092=>[
                ['component_product_id'=>1091,'quantity'=>1],
                ['component_product_id'=>1003,'quantity'=>1],
            ]
        ]
    ];
    $priced=\MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::class;
    ok($priced::calculate(1090,$pricing)['price']==='1990.00',
        'fixed bundle price independent of component sum');
    ok($priced::calculate(1091,$pricing)['price']==='2250.00',
        '10% discount from 2500.00 sum');
    ok($priced::calculate(1092,$pricing)['price']==='2337.50',
        'nested bundle uses discounted child price: (2250 + 500) x 85%');
    $pricing['products'][1003]['price']='700.00';
    ok($priced::calculate(1091,$pricing)['price']==='2430.00',
        'discount bundle price recalculates after changing component price');
    $pricing['items'][1091][]=['component_product_id'=>1090,'quantity'=>1];
    $pricing['items'][1090][]=['component_product_id'=>1092,'quantity'=>1];
    $pricing['items'][1092][]=['component_product_id'=>1090,'quantity'=>1];
    ok(!$priced::calculate(1092,$pricing)['valid'],
        'cycle detection rejects circular discounted bundle pricing');

    echo 'BUNDLE_SMOKE_OK: '.$n." cases\n";
}
