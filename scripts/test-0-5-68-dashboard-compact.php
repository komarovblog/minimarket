<?php
/** MiniMarket 0.5.68: focused UI regression, preserving 0.5.67 layout. */
declare(strict_types=1);
$checks=0;
function verify68(bool $ok, string $message):void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('DASHBOARD_068_FAIL: '.$message);
}
$base=dirname(__DIR__).'/src/com_minimarket/';
$tpl=(string)file_get_contents($base.'admin/tmpl/dashboard/default.php');
$css=(string)file_get_contents($base.'media/css/dashboard.css');
verify68(!str_contains($tpl, "Text::_('COM_MINIMARKET_DASHBOARD_COMING_SOON')") &&
    !str_contains($tpl, '<small class="mm-dashboard-tile-note">'),
    'Orders no longer displays Coming soon');
verify68(str_contains($tpl, "'view' => 'orders'") &&
    str_contains($tpl, "'count' => 'orders_new'") &&
    !str_contains($tpl, "'pending' => true"),
    'Orders is a real management tile that counts newly received orders');
verify68(str_contains($tpl, "\$item['view'] === 'import'") &&
    str_contains($tpl, 'mm-dashboard-icon--import') &&
    str_contains($tpl, '<svg') &&
    str_contains($css, '.mm-dashboard-icon--import'),
    'Import/Export has reliable inline vector icon');
verify68(!str_contains($css, 'min-height: 6.3rem') &&
    !preg_match('/\\.mm-dashboard-tile\\s*\\{[^}]*min-height:/s', $css) &&
    str_contains($css, 'padding: .72rem .85rem;') &&
    str_contains($css, 'margin-bottom: .5rem;'),
    'no artificial tile min-height and symmetrical top/bottom padding');
verify68(str_contains($css, 'grid-template-columns: repeat(4, minmax(0, 1fr))') &&
    str_contains($css, '#wrapper.closed .com-minimarket-dashboard .mm-dashboard-tiles') &&
    str_contains($css, 'grid-template-columns: repeat(6, minmax(0, 1fr))'),
    'unchanged 4/6 desktop layout');
verify68(str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr))') &&
    str_contains($css, 'grid-template-columns: minmax(0, 1fr)'),
    'unchanged tablet and mobile layout');
verify68(str_contains($css, 'background: #272e36') &&
    str_contains($css, 'background: #151c24') &&
    str_contains($css, 'background: #303944'),
    'all colors preserved');
verify68(str_contains($tpl, "'view' => 'bundles'") &&
    str_contains($tpl, 'mm-dashboard-icon--bundle') &&
    str_contains($tpl, 'data-mm-stat=') &&
    str_contains($tpl, "Text::_('COM_MINIMARKET_DASHBOARD_HEADING')"),
    'other icons counters and header untouched');
echo 'DASHBOARD_068_COMPACT_PASS: '.$checks." checks\n";
