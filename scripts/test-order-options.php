<?php
/** 0.5.34: global order-option dictionary, category inheritance and Joomla fields. */
declare(strict_types=1);

namespace Joomla\Database { interface DatabaseInterface {} }

namespace MiniMarket\OrderOptionsTest {
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/OrderOptionCatalog.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryOrderOptions.php';

    use MiniMarket\Component\Minimarket\Administrator\Service\OrderOptionCatalog as Catalog;
    use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions as CategoryRules;

    $n = 0;
    function verify(bool $okay, string $name): void
    {
        global $n;
        ++$n;
        if (!$okay) throw new \RuntimeException('ORDER_OPTIONS_FAIL: ' . $name);
    }

    $rows = Catalog::normalize([
        ['id' => 0, 'title' => 'S', 'code' => 's', 'state' => 1],
        ['id' => 0, 'title' => 'M', 'code' => 'm', 'state' => 1],
        ['id' => 0, 'title' => 'L', 'code' => 'l', 'state' => 0]
    ]);
    verify(count($rows) === 3, 'three global size values');
    verify(array_column($rows, 'sort_order') === [0,1,2], 'native drag order preserved');
    verify($rows[2]['state'] === 0, 'unpublished option value retained');

    $old = [17 => ['code' => 'red', 'title' => 'Red', 'state' => 1, 'color_hex' => '#AA0000']];
    $renamed = Catalog::normalize([
        ['id' => 17, 'title' => 'Красный', 'code' => '', 'color_hex' => '#FF0000']
    ], $old);
    verify($renamed[0]['code'] === 'red' && $renamed[0]['id'] === 17,
        'title rename keeps stable value ID and existing code');
    verify($renamed[0]['color_hex'] === '#FF0000', 'HEX accepts normalized color');
    $media=Catalog::normalize([[
        'title'=>'Синий','code'=>'blue','color_hex'=>'#0011FF',
        'sample_image'=>'images/minimarket/blue.png#joomlaImage://local-images/blue.png'
    ]]);
    verify($media[0]['sample_image']==='images/minimarket/blue.png',
        'optional order-option image is a safe relative Joomla image, without editor metadata');
    verify(Catalog::normalize([['title'=>'M','code'=>'m']])[0]['sample_image']==='',
        'order options may have a HEX, photo, both or neither');
    $fallback = Catalog::normalize([['title' => 'Большой', 'code' => '']]);
    verify(str_starts_with($fallback[0]['code'], 'value-'), 'non-Latin name gets stable code');

    foreach ([
        [['title' => 'S', 'code' => 's'], ['title' => 'S again', 'code' => 's']],
        [['title' => '<script>', 'code' => 'x']],
        [['title' => 'M', 'code' => 'bad*']],
        [['id' => 999, 'title' => 'S', 'code' => 's']],
        [['title' => 'S', 'code' => 's', 'color_hex' => '#ABC']],
        [['title'=>'Broken','code'=>'broken','sample_image'=>'https://other.test/x.png']],
        [['title'=>'Broken','code'=>'broken','sample_image'=>'images/../secrets.png']],
    ] as $bad) {
        try {
            Catalog::normalize($bad, $old);
            verify(false, 'bad value rows accepted');
        } catch (\InvalidArgumentException $error) {
            verify(true, 'bad value rows rejected');
        }
    }
    verify(CategoryRules::normalize(['2' => '-1', '3' => '2'], [2,3]) === [3 => 2],
        'inherit omitted, direct required persisted');
    verify(CategoryRules::normalize(['3' => '0'], [3]) === [3 => 0],
        'explicitly disabled override persists');
    try {
        CategoryRules::normalize(['999' => '2'], [3]);
        verify(false, 'unknown global option assigned to category');
    } catch (\InvalidArgumentException $error) {
        verify(true, 'unknown category option rejected');
    }
    try {
        CategoryRules::normalize(['3' => '9'], [3]);
        verify(false, 'invalid category option mode accepted');
    } catch (\InvalidArgumentException $error) {
        verify(true, 'only inherit/disabled/optional/required accepted');
    }
    $parents = [1 => 0, 2 => 1, 3 => 2];
    $rules = [1 => [12 => 2, 13 => 1], 2 => [12 => 0], 3 => [12 => 1]];
    verify(CategoryRules::resolveModes(1, $parents, $rules) === [12 => 2, 13 => 1],
        'root applies own option rules');
    verify(CategoryRules::resolveModes(2, $parents, $rules) === [13 => 1],
        'child disables inherited required option');
    verify(CategoryRules::resolveModes(3, $parents, $rules) === [13 => 1, 12 => 1],
        'grandchild can re-enable disabled option as optional');
    verify(CategoryRules::resolveModes(100, $parents, $rules) === [],
        'broken category hierarchy fails closed');

    $base = __DIR__ . '/../src/com_minimarket/admin/';
    $optionForm = simplexml_load_file($base . 'forms/orderoption.xml');
    $categoryForm = simplexml_load_file($base . 'forms/category.xml');
    verify(count($optionForm->xpath("//field[@name='option_values' and @type='subform']")) === 1,
        'option values use native Joomla repeatable subform');
    verify(count($categoryForm->xpath("//fieldset[@name='order_options']/field[@name='order_option_rules']")) === 1,
        'category has order-option inheritance tab');
    $manifest = file_get_contents(__DIR__ . '/../src/com_minimarket/minimarket.xml');
    preg_match('/<version>([^<]+)<\/version>/', $manifest, $versionMatches);
    verify(str_contains($manifest, 'view=orderoptions')
        && version_compare($versionMatches[1] ?? '0', '0.5.34', '>='),
        'global option menu persists through future component versions');

    $optionForm = simplexml_load_file($base . 'forms/orderoption.xml');
    verify(count($optionForm->xpath("//fieldset[@name='categories']/field[@name='category_rules' and @type='Optioncategories']")) === 1,
        'option editor has same reverse category assignments as attributes');
    $optionEditor = file_get_contents($base . 'src/Model/OrderoptionModel.php');
    verify(str_contains($optionEditor, 'CategoryOrderOptions::replaceForOption')
        && str_contains($optionEditor, 'CategoryOrderOptions::directForOption'),
        'reverse category assignments saved transactionally with the option');
    $optionTemplate = file_get_contents($base . 'tmpl/orderoption/edit.php');
    verify(str_contains($optionTemplate, "'categories' => 'COM_MINIMARKET_ORDER_OPTION_CATEGORIES'") &&
        str_contains($optionTemplate, "renderFieldset(\$tab)"),
        'option category assignment tab is actually rendered and saved, not hidden');
    $optionValueForm=simplexml_load_file($base.'forms/orderoptionvalue.xml');
    verify(count($optionValueForm->xpath("//field[@name='sample_image' and @type='media']"))===1,
        'option values can have a real image as well as an optional HEX color');
    $optionValueService=file_get_contents($base.'src/Service/OrderOptionCatalog.php');
    verify(str_contains($optionValueService,"'sample_image' => ".'$image'),
        'option-value images are validated and preserved in the global dictionary');
    $reverseService = file_get_contents($base . 'src/Service/CategoryOrderOptions.php');
    verify(str_contains($reverseService, 'function replaceForOption(')
        && str_contains($reverseService, 'function directForOption('),
        'one relationship table is edited from category and option');
    verify((string)$optionForm->xpath("//fieldset[@name='values']/field[@name='option_values']")[0]['layout'] ===
        'joomla.form.field.subform.repeatable',
        'option values render compact native rows without horizontal scroll');

    $groupForm=simplexml_load_file($base.'forms/group.xml');
    $productForm=simplexml_load_file($base.'forms/product.xml');
    verify(count($groupForm->xpath("//field[@name='member_editor' and @type='Groupeditor']"))===1,
        'standalone group uses paged searchable product chooser');
    verify(count($productForm->xpath("//field[@name='group_editor' and @type='Groupeditor']"))===1,
        'product links use the same editor instead of hundreds of checkboxes');
    echo 'ORDER_OPTIONS_OK: ' . $n . " cases\n";
}
