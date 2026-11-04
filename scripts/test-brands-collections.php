<?php
/** Brands / collections editor and relationship regression. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\BrandsCollectionsSmoke {
    \define('_JEXEC',1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/ProductCollections.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductCollections as C;

    $tests=0;
    function check(bool $pass,string $message):void {
        global $tests; ++$tests;
        if (!$pass) throw new \RuntimeException('BRANDS_COLLECTIONS_FAIL: '.$message);
    }
    $allowed=[3=>true,7=>true,22=>true];
    check(C::normalize(null,$allowed)===[],'missing collections are empty');
    check(C::normalize('',$allowed)===[],'clearing all collections works');
    check(C::normalize(['3','7','22'],$allowed)===[3,7,22],
        'multiple selections keep input order');
    foreach ([['3','3'],['9'],['0'],['foo'],[0],17,['3',[]]] as $bad) {
        try { C::normalize($bad,$allowed); check(false,'invalid ID accepted'); }
        catch (\InvalidArgumentException $e) {check(true,'invalid ID rejected');}
    }
    $root=__DIR__.'/../src/com_minimarket/';
    $productForm=simplexml_load_file($root.'admin/forms/product.xml');
    check($productForm!==false,'product form valid XML');
    check(count($productForm->xpath('//fieldset[@name="merchandising"]/field[@name="brand_id" and @type="Brandlist"]'))===1,
        'one brand on product');
    check(count($productForm->xpath('//fieldset[@name="merchandising"]/field[@name="collection_ids" and @type="Collectionlist" and @multiple="true"]'))===1,
        'native collection multiple checkboxes');
    check(count($productForm->xpath('//fieldset[@name="merchandising"]/field[@name="collections_present"]'))===1,
        'empty selections are submitted');
    foreach (['brand','collection'] as $name) {
        $form=simplexml_load_file($root.'admin/forms/'.$name.'.xml');
        check($form!==false,'editor form XML valid: '.$name);
        foreach (['id','title','alias','image','image_alt','introtext','description',
            'meta_title','meta_description','meta_robots','og_title','og_image','state','sort_order'] as $field) {
            check(count($form->xpath('//field[@name="'.$field.'"]'))===1,
                $name.' has '.$field);
        }
        $upper=ucfirst($name);
        $plural=$name.'s';
        $upperPlural=ucfirst($plural);
        foreach ([
            'src/Controller/'.$upper.'Controller.php','src/Controller/'.$upperPlural.'Controller.php',
            'src/Model/'.$upper.'Model.php','src/Model/'.$upperPlural.'Model.php',
            'src/Table/'.$upper.'Table.php','src/View/'.$upper.'/HtmlView.php',
            'src/View/'.$upperPlural.'/HtmlView.php','tmpl/'.$name.'/edit.php',
            'tmpl/'.$plural.'/default.php','forms/filter_'.$plural.'.xml',
        ] as $file) {
            check(is_file($root.'admin/'.$file),$name.' admin file '.$file);
        }
        $list=(string)file_get_contents($root.'admin/tmpl/'.$plural.'/default.php');
        check(str_contains($list,"AdminEntityLinks::edit('".$name."'")
            && str_contains($list,'PublishedButton')
            && str_contains($list,'item->alias'),$name.' native list links');
        $model=(string)file_get_contents($root.'admin/src/Model/'.$upper.'Model.php');
        check(str_contains($model,'function delete(')
            && str_contains($model,'loadResult()'),
            $name.' deletion blocks assigned products');
    }
    $model=(string)file_get_contents($root.'admin/src/Model/ProductModel.php');
    check(str_contains($model,'ProductCollections::selected(')
        && str_contains($model,'ProductCollections::allowed(')
        && str_contains($model,'ProductCollections::normalize(')
        && str_contains($model,'ProductCollections::replace('),
        'products have atomic collection relationships');
    $table=(string)file_get_contents($root.'admin/src/Table/ProductTable.php');
    check(str_contains($table,'$this->brand_id = $brandId > 0 ? $brandId : null'),
        'no brand uses nullable FK instead of zero');
    $migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.49.sql');
    check(str_contains($migration,'ADD COLUMN')
        && str_contains($migration,'#__minimarket_brands')
        && str_contains($migration,'#__minimarket_collections')
        && str_contains($migration,'#__minimarket_product_collections')
        && !preg_match('/\bDROP\s+TABLE|\bTRUNCATE\b/i',$migration),
        'migration adds relationships without deleting old catalog');
    $links=(string)file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
    check(str_contains($links,"'brand', 'collection'") &&
        str_contains($links,"'brands', 'collections'"),
        'safe edit and list links know about new entities');
    $site=(string)file_get_contents($root.'site/src/Service/CatalogRepository.php');
    check(str_contains($site,'getProductCollections(')
        && str_contains($site,'c.state=1') && str_contains($site,'b.state=1'),
        'public product metadata only uses published dictionaries');
    echo 'BRANDS_COLLECTIONS_OK: '.$tests." cases\n";
}
