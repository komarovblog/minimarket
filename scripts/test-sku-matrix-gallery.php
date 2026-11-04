<?php
/** Standalone 0.5.39 regression: one product price, direct SKU grid, photo rules. */
declare(strict_types=1);
namespace MiniMarket\SkuMatrixSmoke {
    \define('_JEXEC', 1);
    $base=__DIR__.'/../src/com_minimarket/';
    require $base.'admin/src/Service/MediaPath.php';
    require $base.'admin/src/Service/VariantMatrix.php';
    require $base.'admin/src/Service/ProductGallery.php';
    require $base.'admin/src/Service/ProductVariants.php';
    require $base.'admin/src/Service/ProductSkuMatrix.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductGallery as Gallery;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants as Variant;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductSkuMatrix as Grid;
    $count=0;
    function check(bool $test,string $why):void {
        global $count;
        ++$count;
        if (!$test) throw new \RuntimeException('SKU_MATRIX_GALLERY_FAIL: '.$why);
    }
    $axes=['color'=>['title'=>'Цвет','options'=>[
        ['value'=>'v:blue','title'=>'Синий'],['value'=>'v:red','title'=>'Красный']]],
        'size'=>['title'=>'Размер','options'=>[
        ['value'=>'v:s','title'=>'S'],['value'=>'v:m','title'=>'M']]]];
    $blueS=['id'=>0,'sku'=>'BLUE-S','price'=>'1200','stock'=>'3','state'=>1,
        'selection'=>['color'=>'v:blue','size'=>'v:s']];
    $blueM=['id'=>0,'sku'=>'','price'=>'1200','stock'=>'0','state'=>0,
        'selection'=>['color'=>'v:blue','size'=>'v:m']];
    $items=Variant::normalize([$blueS,$blueM],$axes);
    check(count($items)===2,'both active and disabled combinations can exist');
    check($items[0]['sku']==='BLUE-S' && $items[0]['stock']===3,
        'SKU article and real stock stay separate fields');
    check($items[1]['sku']==='' && $items[1]['state']===0,
        'blank SKU labels accepted; real variant_id provides identity');
    check($items[0]['price']===$items[1]['price'],
        'variants may share product price as required by matrix');
    check(Grid::key(['size'=>'v:s','color'=>'v:blue']) ===
          Grid::key(['color'=>'v:blue','size'=>'v:s']),
        'gallery targets combinations by canonical stable selection, not article');
    $key=Grid::key($blueS['selection']);
    $photos=Gallery::normalizeRows([
        ['path'=>'images/blue.jpg','alt'=>'Синяя','visibility_mode'=>'1',
         'show_keys'=>json_encode([$key]),'cover_keys'=>json_encode([$key])],
        ['path'=>'images/global.jpg','alt'=>'Общая','visibility_mode'=>'0',
         'show_keys'=>'[]','cover_keys'=>'[]'],
    ],'images/cover.jpg');
    check($photos[0]['visibility_mode']===1 &&
          $photos[0]['show_keys'][0]===$key &&
          $photos[0]['cover_keys'][0]===$key,
        'restricted picture with SKU-specific primary role normalizes');
    check($photos[1]['visibility_mode']===0 &&
          $photos[1]['show_keys']===[],'global gallery photo remains visible everywhere');
    foreach ([
        ['path'=>'images/x.jpg','visibility_mode'=>1,'show_keys'=>'[]'],
        ['path'=>'images/x.jpg','visibility_mode'=>1,
         'show_keys'=>json_encode([$key]),'cover_keys'=>json_encode(['{}'])],
        ['path'=>'images/x.jpg','visibility_mode'=>3],
    ] as $invalid) {
        try {
            Gallery::normalizeRows([$invalid]);
            check(false,'invalid visibility or cover assignment accepted');
        } catch (\InvalidArgumentException $error) {
            check(true,'invalid gallery assignment rejected');
        }
    }
    $form=simplexml_load_file($base.'admin/forms/product.xml');
    check(count($form->xpath("//fieldset[@name='variants']/field[@name='sku_matrix_payload' and @type='Skumatrix']"))===1,
        'one direct matrix field in Product form');
    $galleryForm=simplexml_load_file($base.'admin/forms/product_gallery.xml');
    foreach (['visibility_mode','show_keys','cover_keys'] as $field) {
        check(count($galleryForm->xpath("//field[@name='".$field."']"))===1,
            'gallery has '.$field);
    }
    $template=file_get_contents($base.'admin/tmpl/product/edit.php');
    check(str_contains($template,"renderField('sku_matrix_payload')") &&
        !str_contains($template,"renderField('variants')"),
        'product UI edits one matrix, not a separate native SKU subform');
    $model=file_get_contents($base.'admin/src/Model/ProductModel.php');
    check(str_contains($model,'ProductSkuMatrix::normalize(') &&
        str_contains($model,'ProductGallery::replaceRows(') &&
        str_contains($model,'$db->transactionStart();'),
        'matrix and SKU gallery persisted atomically by ProductModel');
    $server=file_get_contents($base.'admin/src/Service/ProductSkuMatrix.php');
    check(str_contains($server, "'price' => " . '$productPrice') &&
        str_contains($server,'ProductVariants::formRows('),
        'product price reused in SKU rows; existing media carried through');
    $view=file_get_contents($base.'admin/src/View/Product/HtmlView.php');
    check(str_contains($view,'media/com_minimarket/js/sku-matrix.js') &&
        !str_contains($view,'media/com_minimarket/js/variants-local.js'),
        'no old native row-add dependency registered in product');
    $javascript=file_get_contents($base.'media/js/sku-matrix.js');
    check(str_contains($javascript,'data-mm-matrix-payload') &&
        str_contains($javascript,'data-mm-photo-select') &&
        str_contains($javascript, 'a < b ? -1 : a > b ? 1 : 0') &&
        !str_contains($javascript,'group-add') &&
        !str_contains($javascript,'data-mm-sku-price'),
        'direct option columns and SKU photo lists without hidden Joomla add button');
    $site=file_get_contents($base.'media/js/variants.js');
    check(str_contains($site,'variant.gallery') &&
        !str_contains($site,'details.price.textContent = selected.price'),
        'storefront switches photos by SKU but never changes product price');
    $migration=file_get_contents($base.'admin/sql/updates/mysql/0.5.39.sql');
    check(str_contains($migration,'VARCHAR(100) NULL') &&
        str_contains($migration,'visibility_mode') &&
        str_contains($migration,'product_image_variants') &&
        !preg_match('/^\s*(?:UPDATE|DELETE)\s/mi',$migration),
        'additive SQL with optional SKU labels and photo links');
    echo 'SKU_MATRIX_GALLERY_PASS: '.$count." cases\n";
}
