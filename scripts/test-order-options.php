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
    $fallback = Catalog::normalize([['title' => 'Большой', 'code' => '']]);
    verify(str_starts_with($fallback[0]['code'], 'value-'), 'non-Latin name gets stable code');

    foreach ([
        [['title' => 'S', 'code' => 's'], ['title' => 'S again', 'code' => 's']],
        [['title' => '<script>', 'code' => 'x']],
        [['title' => 'M', 'code' => 'bad*']],
        [['id' => 999, 'title' => 'S', 'code' => 's']],
        [['title' => 'S', 'code' => 's', 'color_hex' => '#ABC']],
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
    $reverseService = file_get_contents($base . 'src/Service/CategoryOrderOptions.php');
    verify(str_contains($reverseService, 'function replaceForOption(')
        && str_contains($reverseService, 'function directForOption('),
        'one relationship table is edited from category and option');
    verify((string)$optionForm->xpath("//fieldset[@name='values']/field[@name='option_values']")[0]['layout'] ===
        'joomla.form.field.subform.repeatable',
        'option values render compact native rows without horizontal scroll');

    foreach (['ProductgroupField.php', 'GroupmembersField.php'] as $field) {
        $code = file_get_contents($base . 'src/Field/' . $field);
        verify(str_contains($code, '$option->checked = in_array(')
            && str_contains($code, '$this->value'),
            'checkbox field sets checked property for native Joomla layout: ' . $field);
    }
    echo 'ORDER_OPTIONS_OK: ' . $n . " cases\n";
}
