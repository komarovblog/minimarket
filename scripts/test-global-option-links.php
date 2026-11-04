<?php
/** Global category-linked option IDs with stable legacy SKU keys. */
declare(strict_types=1);
namespace MiniMarket\GlobalLinksTest {
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/VariantMatrix.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\VariantMatrix as Matrix;

    $checks=0;
    function check(bool $condition, string $why): void {
        global $checks;
        ++$checks;
        if (!$condition) throw new \RuntimeException('GLOBAL_OPTION_LINK_FAIL: ' . $why);
    }
    $input=[
        ['source_option_id'=>'12','code'=>'size','title'=>'Размер','values'=>[
            ['source_value_id'=>'101','code'=>'s','title'=>'S'],
            ['source_value_id'=>'102','code'=>'m','title'=>'M']]],
        ['source_option_id'=>'13','code'=>'color','title'=>'Цвет','values'=>[
            ['source_value_id'=>'103','code'=>'red','title'=>'Красный'],
            ['source_value_id'=>'104','code'=>'blue','title'=>'Синий']]],
    ];
    $axes=Matrix::normalize($input);
    check($axes[0]['source_option_id']===12,'source option ID is preserved');
    check($axes[0]['values'][1]['source_value_id']===102,'source value ID is preserved');
    check(count(Matrix::preview($axes))===4,'2 x 2 global values yield four combinations');
    $signature=Matrix::preview($axes)[0]['signature'];
    $renamed=$input;
    $renamed[0]['title']='Размер одежды';
    $renamed[0]['values'][0]['title']='Маленький';
    check(Matrix::preview(Matrix::normalize($renamed))[0]['signature']===$signature,
        'renaming a global title does not change SKU identity');
    $legacy=Matrix::normalize([
        ['code'=>'material','title'=>'Материал',
         'values'=>[['code'=>'wood','title'=>'Дерево']]]
    ]);
    check($legacy[0]['source_option_id']===0 &&
          $legacy[0]['values'][0]['source_value_id']===0,
        'legacy local axes remain unlinked, not forcibly migrated');
    foreach ([
        ['source_option_id'=>'-1','source_value_id'=>'20'],
        ['source_option_id'=>'xxx','source_value_id'=>'20'],
        ['source_option_id'=>4294967296,'source_value_id'=>20],
        ['source_option_id'=>12,'source_value_id'=>0],
        ['source_option_id'=>0,'source_value_id'=>30],
    ] as $bad) {
        try {
            Matrix::normalize([[
                'code'=>'size','title'=>'Размер',
                'source_option_id'=>$bad['source_option_id'],
                'values'=>[['code'=>'s','title'=>'S',
                    'source_value_id'=>$bad['source_value_id']]]
            ]]);
            check(false,'invalid source ID pair accepted');
        } catch (\InvalidArgumentException $error) {
            check(true,'invalid source ID pair rejected');
        }
    }
    $base=__DIR__.'/../src/com_minimarket/';
    $dimensions=simplexml_load_file($base.'admin/forms/product_variant_dimension.xml');
    $values=simplexml_load_file($base.'admin/forms/product_variant_value.xml');
    check(count($dimensions->xpath('//field[@name="source_option_id" and @type="hidden"]'))===1,
        'axes post source option IDs');
    check(count($values->xpath('//field[@name="source_value_id" and @type="hidden"]'))===1,
        'values post source value IDs');
    $model=file_get_contents($base.'admin/src/Model/ProductModel.php');
    check(str_contains($model,'ProductCatalogAxes::validate($db,'),
        'server validates source IDs against category assignment');
    $service=file_get_contents($base.'admin/src/Service/ProductCatalogAxes.php');
    check(str_contains($service,'function forAllCategories(')
       && str_contains($service,'function validate(')
       && str_contains($service,'CategoryOrderOptions::resolveModes('),
       'product uses effective inherited category assignment');
    check(str_contains($service,'existingDimensionId(')
       && str_contains($service,'$historicalAxes'),
       'historical values stay valid when category assignment changes');
    $persistence=file_get_contents($base.'admin/src/Service/ProductLocalDimensions.php');
    check(str_contains($persistence,"'source_option_id'")
       && str_contains($persistence,"'source_value_id'")
       && str_contains($persistence,'$sourceOptions')
       && str_contains($persistence,'$sourceValues'),
       'stable source links and live global titles used');
    $field=file_get_contents($base.'admin/src/Field/ProductcategoryoptionsField.php');
    check(str_contains($field,'ProductCatalogAxes::forAllCategories(')
       && str_contains($field,'data-mm-category-catalog'),
       'all category options loaded in bounded queries');
    check(!str_contains($field,'data-mm-copy-category-options'),
       'no manual copy button');
    $ui=file_get_contents($base.'media/js/variants-local.js');
    check(str_contains($ui,'hydrateFromCategory()')
       && str_contains($ui,"categoryInput?.addEventListener('change'"),
       'category selection hydrates options automatically');
    check(str_contains($ui,'if (selectAxisTitles(optionsPanel).length || selectionInputs().length)'),
       'existing axes and SKU rows are not overwritten');
    check(str_contains($ui,'lockCatalogNames()'),
       'global option names are not edited as detached local values');
    $migration=file_get_contents($base.'admin/sql/updates/mysql/0.5.37.sql');
    check(str_contains($migration,'source_option_id')
       && str_contains($migration,'source_value_id')
       && str_contains($migration,'FOREIGN KEY')
       && !str_contains($migration,'UPDATE ')
       && !str_contains($migration,'DELETE FROM'),
       'additive SQL does not rewrite products or SKU');
    echo 'GLOBAL_OPTION_LINK_PASS: '.$checks." cases\n";
}
