<?php
/** 0.5.67 responsive dashboard layout contract, without modifying Joomla core. */
declare(strict_types=1);
$checks = 0;
function checkResponsive(bool $ok, string $label): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('DASHBOARD_067_FAIL: '.$label);
}
$root = dirname(__DIR__);
$template = (string) file_get_contents($root.'/src/com_minimarket/admin/tmpl/dashboard/default.php');
$css = (string) file_get_contents($root.'/src/com_minimarket/media/css/dashboard.css');
checkResponsive(substr_count($template, '<section class="mm-dashboard-band card ') === 1 &&
    substr_count($template, '<section class="mm-dashboard-band') === 1 &&
    str_contains($template, 'foreach ($sections as $section)'),
    'three sections rendered by one sequential loop, not grid columns');
checkResponsive(!str_contains($template, 'mm-dashboard-grid') &&
    !str_contains($css, 'max-width: 1120px') &&
    str_contains($css, 'max-width: none') &&
    str_contains($css, 'width: 100%'),
    'no fixed container cap or right-side unused column');
checkResponsive(str_contains($css, 'grid-template-columns: minmax(0, 1fr)'),
    'mobile has exactly one column');
checkResponsive(str_contains($css, '@media (min-width: 768px) and (max-width: 1199.98px)') &&
    str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr))'),
    'tablet has exactly two columns');
checkResponsive(str_contains($css, '@media (min-width: 1200px)') &&
    str_contains($css, 'grid-template-columns: repeat(4, minmax(0, 1fr))'),
    'desktop sidebar open has four columns');
checkResponsive(str_contains($css, '#wrapper.closed .com-minimarket-dashboard .mm-dashboard-tiles') &&
    str_contains($css, 'grid-template-columns: repeat(6, minmax(0, 1fr))'),
    'Atum sidebar collapsed class immediately gives six columns');
checkResponsive(str_contains($template, 'mm-dashboard-tile-top') &&
    str_contains($template, 'mm-dashboard-tile-title') &&
    substr_count($template, '<div class="mm-dashboard-tiles">') === 1,
    'tile icon and counter above label; one tile grid per rendered section');
checkResponsive(str_contains($css, 'font-size: .875rem') &&
    str_contains($css, 'word-break: normal') &&
    str_contains($css, 'overflow-wrap: break-word'),
    'native sidebar-size label without arbitrary mid-word breaks');
checkResponsive(str_contains($css, 'background: #272e36') &&
    str_contains($css, 'background: #151c24') &&
    !str_contains($css, 'background: #283a4e'),
    'muted Joomla grey replaces bright blue');
checkResponsive(str_contains($template, 'mm-dashboard-icon--bundle') &&
    str_contains($template, 'data-mm-stat=') &&
    str_contains($template, 'COM_MINIMARKET_DASHBOARD_SHOP') &&
    str_contains($template, 'COM_MINIMARKET_DASHBOARD_TOOLS'),
    'bundle icon, counts and section labels preserved');
checkResponsive(str_contains($css, 'a.mm-dashboard-tile:focus-visible') &&
    str_contains($css, 'prefers-reduced-motion'),
    'keyboard accessibility and motion preference retained');
echo 'DASHBOARD_067_RESPONSIVE_OK: '.$checks." checks\n";
