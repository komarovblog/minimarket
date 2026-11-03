<?php
/** One SKU engine: order options defined on a product, never category attributes. */
declare(strict_types=1);

namespace Joomla\Database { interface DatabaseInterface {} }
namespace Joomla\CMS\Filter {
    final class InputFilter {
        public static function getInstance(): self { return new self(); }
        public function clean(string $value, string $kind): string {
            return preg_replace('~<script\b[^>]*>.*?</script>~is','',$value)??'';
        }
    }
}
namespace MiniMarket\VariantSmoke {
    \define('_JEXEC',1);
    $services=__DIR__.'/../src/com_minimarket/admin/src/Service/';
    foreach (['MediaPath','TextPresetRows','ProductGallery','ProductVideos',
              'VariantValueGallery','VariantMatrix','ProductLocalDimensions','ProductVariants'] as $file) {
        require $services.$file.'.php';
    }
    use MiniMarket\Component\Minimarket\Administrator\Service\VariantMatrix as Matrix;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductLocalDimensions as Local;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants as Sku;

    $count=0;
    function ok(bool $condition,string $why): void {
        global $count;
        $count++;
        if (!$condition) throw new \RuntimeException('SKU_LOCAL_ONLY_FAIL: '.$why);
    }

    $dimensions=Matrix::normalize([
        ['code'=>'color','title'=>'Цвет','values'=>[
            ['code'=>'red','title'=>'Красный'],['code'=>'blue','title'=>'Синий']]],
        ['code'=>'size','title'=>'Размер','values'=>[
            ['code'=>'s','title'=>'S'],['code'=>'m','title'=>'M'],['code'=>'l','title'=>'L']]],
    ]);
    $axes=Local::axesForVariants($dimensions);
    ok(array_keys($axes)===['color','size'],'only local order options define SKU axes');
    ok(count(Matrix::preview($dimensions))===6,'two colors and three sizes have six possible SKUs');

    $redS=[
        'id'=>'0','sku'=>'CLASSIC-RED-S','price'=>'1590.00','stock'=>'5',
        'selection'=>'{"color":"v:red","size":"v:s"}','state'=>'1',
        'image'=>'images/red.webp#joomlaImage://local-images/red.webp?width=600',
        'image_alt'=>'Red front','photos'=>[
            ['path'=>'images/red-back.webp','alt'=>'Red back']],
    ];
    $redM=array_replace($redS,[
        'sku'=>'CLASSIC-RED-M','stock'=>'0','price'=>'1690',
        'selection'=>'{"color":"v:red","size":"v:m"}',
        'image'=>'images/red.webp','photos'=>[]
    ]);
    $blueS=array_replace($redS,[
        'sku'=>'CLASSIC-BLUE-S','stock'=>'7','selection'=>'{"color":"v:blue","size":"v:s"}',
        'image'=>'images/blue.webp','photos'=>[]
    ]);
    $result=Sku::normalize([$redS,$redM,$blueS],$axes);
    ok(count($result)===3,'three independently identifiable size/color SKUs');
    ok($result[0]['sku']!=$result[1]['sku'],'different sizes use different article numbers');
    ok($result[0]['stock']===5 && $result[1]['stock']===0
        && $result[2]['stock']===7,'each SKU has its own physical stock');
    ok($result[0]['image']==='images/red.webp','Joomla Media metadata removed');
    ok($result[0]['photos'][0]['path']==='images/red-back.webp','SKU gallery preserved');
    ok($result[1]['price']==='1690.00','price normalized to exact money format');
    ok($result[0]['signature']!==$result[1]['signature'],'combination signatures unique');
    ok($result[0]['selection_json']==='{"color":"v:red","size":"v:s"}',
        'SKU selection only uses product option identities');

    ok(Sku::normalize([],[])===[],'simple product may have no order options');
    ok(Sku::normalize('{}',[])===[],'last SKU row may be deleted');
    foreach ([
        [[$redS,$redS],'duplicate combination'],
        [[$redS,array_replace($redM,['sku'=>'CLASSIC-RED-S'])],'duplicate SKU'],
        [[$redS,array_replace($redM,['selection'=>$redS['selection']])],'duplicate combination across SKUs'],
        [[array_replace($redS,['selection'=>'{"color":"v:red"}'])],'missing selected size'],
        [[array_replace($redS,['selection'=>'{"color":"v:red","size":"v:xl"}'])],'unknown option'],
        [[array_replace($redS,['selection'=>'{"10":"d:100","11":"t:S"}'])],'old category-attribute format cannot define a SKU'],
        [[array_replace($redS,['selection'=>'{"color":"v:red","size":"v:s","other":"x"}'])],'forged third option'],
        [[array_replace($redS,['stock'=>'-1'])],'negative stock'],
        [[array_replace($redS,['stock'=>'4294967296'])],'stock out of range'],
        [[array_replace($redS,['price'=>'1.234'])],'invalid money'],
        [[array_replace($redS,['image'=>'images/../../configuration.php'])],'unsafe media'],
        [[array_replace($redS,['photos'=>[['path'=>'images/../../bad.webp']]])],'unsafe gallery'],
        [[array_replace($redS,['id'=>'9abc'])],'unsafe SKU ID'],
        [[array_replace($redS,['sku'=>'<script>'])],'unsafe article'],
        [[array_replace($redS,['state'=>'9'])],'invalid publication'],
    ] as [$bad,$name]) {
        try {
            Sku::normalize($bad,$axes);
            ok(false,$name);
        } catch (\InvalidArgumentException $error) {
            ok(true,$name);
        }
    }
    try {
        Sku::normalize([$redS],[]);
        ok(false,'SKU without configured options rejected');
    } catch (\InvalidArgumentException $error) {
        ok($error->getMessage()==='COM_MINIMARKET_VARIANTS_NO_AXES',
            'SKU requires product order options');
    }
    $productForm=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product.xml');
    $attributeForm=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/attribute.xml');
    ok(count($productForm->xpath('//field[@name="variant_dimensions"]'))===1,
        'product defines order options');
    ok(count($productForm->xpath('//field[@name="variant_info"]'))===0,
        'no category-wide SKU compatibility editor');
    ok(count($attributeForm->xpath('//field[@name="is_variant"]'))===0,
        'informational attributes cannot be marked as SKU options');
    $model=file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Model/ProductModel.php');
    ok(!str_contains($model,'hasLegacyVariants')
        && !str_contains($model,'ProductVariants::axes'),
        'product editor never invokes old SKU mode');
    echo 'SKU_LOCAL_ONLY_PASS: '.$count." cases\n";
}
