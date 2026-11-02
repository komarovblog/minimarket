<?php
/** Standalone regression for product-local SKU dimension/matrix planner. */
declare(strict_types=1);
namespace MiniMarket\MatrixSmoke {
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/VariantMatrix.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\VariantMatrix as M;
    $checks = 0;
    function check(bool $pass, string $message): void {
        global $checks;
        $checks++;
        if (!$pass) throw new \RuntimeException('VARIANT_MATRIX_FAIL: ' . $message);
    }
    $dims = M::normalize([
        ['title' => 'Цвет', 'code' => 'color',
            'values' => [
                ['code' => 'red', 'title' => 'Красный'],
                ['code' => 'blue', 'title' => 'Синий']]],
        ['title' => 'Размер', 'code' => 'size', 'values' => ['S', 'M', 'L']],
    ]);
    check(count($dims) === 2, 'dimensions created inside a product without global attributes');
    check(count($dims[1]['values']) === 3, 'values from simple repeatable names');
    $rows = M::preview($dims);
    check(count($rows) === 6, 'two colors by three sizes yield six previews');
    check($rows[0]['title'] === 'Красный / S', 'human-readable product-local combination');
    check($rows[1]['title'] === 'Красный / M', 'same-color adjacent sizes');
    check($rows[3]['title'] === 'Синий / S', 'second color');
    check($rows[0]['selection'] === ['color' => 'red', 'size' => 's'],
        'dimension/value code stable selection');
    check($rows[0]['signature'] !== $rows[1]['signature'], 'different SKUs have different signatures');
    check(M::signature(['size' => 's', 'color' => 'red']) === $rows[0]['signature'],
        'axis reorder does not change variant identity');
    check(M::normalize([]) === [], 'simple product without visible dimensions supported');
    check(M::preview([]) === [], 'no dimension means no phantom SKU generated');
    $renamed = $dims;
    $renamed[0]['title'] = 'Основной цвет';
    $renamed[0]['values'][0]['title'] = 'Тёмно-красный';
    check(M::preview($renamed)[0]['signature'] === $rows[0]['signature'],
        'renamed label with stable code does not change SKU identity');
    $single = M::preview(M::normalize([[
        'title' => 'Фасовка', 'values' => ['1 кг', '3 кг']
    ]]));
    check(count($single) === 2, 'single axis such as weight is valid');
    check(strlen($single[0]['signature']) === 64, 'hash length');
    foreach ([
        [[['title' => 'Цвет', 'values' => []]], 'empty choices'],
        [[['title' => '', 'values' => ['S']]], 'empty axis'],
        [[['title' => 'Размер', 'values' => ['S', 'S']]], 'duplicate choices'],
        [[['title' => 'Размер', 'values' => [['title'=>'S','code'=>'v1'],['title'=>'S','code'=>'v2']]]], 'duplicate labels with distinct codes'],
        [[['title' => 'Размер', 'values' => [['title'=>'S','code'=>'s'], ['title'=>'Small','code'=>'s']]]], 'duplicate choice code'],
        [[['title' => 'Размер', 'values' => ['S']], ['title' => 'Размер', 'values' => ['M']]], 'duplicate axes'],
        [[['title' => 'Размер', 'values' => ['S'], 'code' => '../evil']], 'unsafe axis code'],
        [[['title' => 'Размер', 'values' => [['title'=>'S','code'=>'<script>']]]], 'unsafe option code'],
        [[['title' => '<script>', 'values' => ['S']]], 'unsafe HTML labels'],
        [[['title' => 'Размер', 'values' => ['S', 'M', 'L']],
          ['title' => 'Цвет', 'values' => array_map(
            static fn(int $n): string => 'Shade '.$n, range(1, 100))]], 'too many generated SKUs'],
        [array_fill(0, 5, ['title' => 'Размер', 'values' => ['S']]), 'too many axes'],
    ] as [$bad, $reason]) {
        try {
            M::normalize($bad);
            check(false, $reason);
        } catch (\InvalidArgumentException $error) {
            check(true, $reason);
        }
    }
    check(count(M::preview(M::normalize([
        ['title'=>'Размер','values'=>['S','M','L']]
    ]))) === 3, 'size only does not require duplicate photos');
    echo 'VARIANT_MATRIX_OK: ' . $checks . " cases\n";
}
