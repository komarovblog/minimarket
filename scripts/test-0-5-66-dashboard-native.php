<?php
/** Long-lived regression for Atum section labels, tile icons, counters and focus. */
declare(strict_types=1);
$checks = 0;
function checkNative(bool $ok, string $message): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('DASHBOARD_066_FAIL: ' . $message);
}
$root = dirname(__DIR__);
$html = (string) file_get_contents($root . '/src/com_minimarket/admin/tmpl/dashboard/default.php');
$css = (string) file_get_contents($root . '/src/com_minimarket/media/css/dashboard.css');
checkNative(str_contains($html, 'mm-dashboard-band-header') &&
    str_contains($html, 'card-header') && str_contains($css, 'border-bottom: 1px solid'),
    'native Joomla-like heading remains');
checkNative(str_contains($html, "'title' => 'COM_MINIMARKET_DASHBOARD_SHOP'") &&
    str_contains($html, "'icon' => 'icon-cart'") &&
    str_contains($html, "'type' => 'shop'"),
    'Store section has a native Joomla cart icon');
checkNative(str_contains($html, 'mm-dashboard-icon--bundle') &&
    str_contains($html, '<svg') && str_contains($html, "'view' => 'bundles'"),
    'Bundles have distinct vector icon');
checkNative(substr_count($html, "'count' => '") === 14 &&
    substr_count($html, "'pending' => true") === 0,
    'all fourteen live count mappings including Reviews and SEO filters');
checkNative(str_contains($css, 'a.mm-dashboard-tile:focus-visible') &&
    str_contains($css, 'outline: 2px solid') &&
    str_contains($css, 'prefers-reduced-motion'),
    'keyboard and reduced motion remain accessible');
checkNative(str_contains($css, 'background: #272e36') &&
    str_contains($css, 'background: #151c24'),
    'muted graphite colors retained');
foreach (['ru-RU' => 'Магазин', 'en-GB' => 'Store'] as $lang => $title) {
    $ini = parse_ini_file($root . '/src/com_minimarket/admin/language/' . $lang . '/com_minimarket.ini');
    checkNative(($ini['COM_MINIMARKET_DASHBOARD_SHOP'] ?? null) === $title,
        'Store section localized: ' . $lang);
}
checkNative(!str_contains($html, 'list-group') &&
    !str_contains($html, 'mm-dashboard-grid'), 'no vertical menus or narrow panel grid');
echo 'DASHBOARD_ATUM_BASE_OK: ' . $checks . " checks\n";
