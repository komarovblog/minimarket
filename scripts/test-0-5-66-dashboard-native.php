<?php
/** Regression for native-size Atum dashboard, store title and bundle icon. */
declare(strict_types=1);
$checks = 0;
function checkNative(bool $ok, string $message): void {
    global $checks;
    $checks++;
    if (!$ok) {
        throw new RuntimeException('DASHBOARD_066_FAIL: ' . $message);
    }
}
$root = dirname(__DIR__);
$html = (string) file_get_contents($root . '/src/com_minimarket/admin/tmpl/dashboard/default.php');
$css = (string) file_get_contents($root . '/src/com_minimarket/media/css/dashboard.css');
checkNative(str_contains($html, 'class="mm-dashboard-grid"') &&
    str_contains($css, 'max-width: 1120px') &&
    str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr))'),
    'two native-width panels at desktop and capped dashboard width');
checkNative(str_contains($css, '@media (min-width: 1200px)') &&
    str_contains($css, 'grid-template-columns: minmax(0, 1fr)'),
    'single column on smaller screens');
checkNative(str_contains($html, 'mm-dashboard-band-header') &&
    str_contains($html, 'card-header') &&
    str_contains($css, 'border-bottom: 1px solid'),
    'separate native Joomla-style module heading');
checkNative(str_contains($html, "'title' => 'COM_MINIMARKET_DASHBOARD_SHOP'") &&
    str_contains($html, "'icon' => 'icon-cart'") &&
    str_contains($html, "'type' => 'shop'"),
    'Store section with visible Joomla cart icon');
checkNative(str_contains($html, 'mm-dashboard-icon--bundle') &&
    str_contains($html, '<svg') &&
    str_contains($html, "'view' => 'bundles'"),
    'bundle has its own independent inline icon, not a missing icon-font glyph');
checkNative(substr_count($html, "'count' => '") === 10 &&
    substr_count($html, "'pending' => true") === 1,
    'real counter keys and pending Orders retained');
checkNative(str_contains($css, 'background: #1c2a39') &&
    str_contains($css, 'background: #283a4e') &&
    str_contains($css, 'background: #354d64') &&
    !str_contains($css, 'background: #101821'),
    'muted blue-grey surface instead of black native-sized cards');
checkNative(str_contains($css, 'a.mm-dashboard-tile:focus-visible') &&
    str_contains($css, 'outline: 2px solid') &&
    str_contains($css, 'prefers-reduced-motion'),
    'keyboard focus and reduced motion');
foreach (['ru-RU' => 'Магазин', 'en-GB' => 'Store'] as $lang => $title) {
    $ini = parse_ini_file($root . '/src/com_minimarket/admin/language/' . $lang . '/com_minimarket.ini');
    checkNative(($ini['COM_MINIMARKET_DASHBOARD_SHOP'] ?? null) === $title,
        'Store title localized: ' . $lang);
}
checkNative(!str_contains($html, 'list-group') &&
    !str_contains($html, 'mm-dashboard-section'),
    'no legacy vertical navigation');
echo 'DASHBOARD_066_ATUM_NATIVE_OK: ' . $checks . " checks\n";
