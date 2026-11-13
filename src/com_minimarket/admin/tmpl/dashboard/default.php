<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

/**
 * Full-width stacked Atum dashboard sections; each uses a responsive
 * tile grid. The native #wrapper.closed class switches 4 to 6 desktop tiles.
 * No vertical section menus; Orders links to real stored requests.
 */
$sections = [
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_MANAGEMENT',
        'type' => 'management',
        'icon' => 'icon-cog',
        'items' => [
            ['view' => 'orders', 'label' => 'COM_MINIMARKET_DASHBOARD_ORDERS',
                'icon' => 'icon-cart', 'count' => 'orders_new'],
            ['view' => 'deliveries', 'label' => 'COM_MINIMARKET_DASHBOARD_DELIVERIES',
                'icon' => 'icon-truck', 'count' => 'deliveries_attention'],
            ['view' => 'returns', 'label' => 'COM_MINIMARKET_RETURNS_TITLE',
                'icon' => 'icon-reply', 'count' => 'returns_new'],
            ['view' => 'payments', 'label' => 'COM_MINIMARKET_PAYMENTS_TITLE',
                'icon' => 'icon-credit-card', 'count' => 'payments_attention'],
            ['view' => 'fiscalization', 'label' => 'COM_MINIMARKET_CASH_REGISTER_TITLE',
                'icon' => 'icon-file', 'count' => 'fiscal_attention'],
            ['view' => 'customers', 'label' => 'COM_MINIMARKET_CUSTOMERS_TITLE',
                'icon' => 'icon-users', 'count' => null],
            ['view' => 'tickets', 'label' => 'COM_MINIMARKET_TICKETS_TITLE',
                'icon' => 'icon-comments', 'count' => 'tickets_new'],
            ['view' => 'documents', 'label' => 'COM_MINIMARKET_DOCUMENTS_TITLE',
                'icon' => 'icon-file', 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_REPORTS',
        'type' => 'reports',
        'icon' => 'icon-chart-bar',
        'items' => [
            ['view' => 'businessreport', 'label' => 'COM_MINIMARKET_BUSINESS_REPORT_TITLE',
                'icon' => 'icon-chart-bar', 'count' => null],
            ['view' => 'salesreport', 'label' => 'COM_MINIMARKET_SALES_REPORT_TITLE',
                'icon' => 'icon-chart-bar', 'count' => null],
            ['view' => 'stockreport', 'label' => 'COM_MINIMARKET_STOCK_REPORT_TITLE',
                'icon' => 'icon-box', 'count' => null],
            ['view' => 'returnsreport', 'label' => 'COM_MINIMARKET_RETURNS_REPORT_TITLE',
                'icon' => 'icon-reply', 'count' => null],
            ['view' => 'planfactreport', 'label' => 'COM_MINIMARKET_PLANFACT_REPORT_TITLE',
                'icon' => 'icon-chart-bar', 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_SHOP',
        'type' => 'shop',
        'icon' => 'icon-cart',
        'items' => [
            ['view' => 'categories', 'label' => 'COM_MINIMARKET_CATEGORIES',
                'icon' => 'icon-folder', 'count' => null],
            ['view' => 'products', 'label' => 'COM_MINIMARKET_PRODUCTS',
                'icon' => 'icon-cube', 'count' => null],
            ['view' => 'bundles', 'label' => 'COM_MINIMARKET_BUNDLES',
                'icon' => 'icon-cube', 'count' => null],
            ['view' => 'groups', 'label' => 'COM_MINIMARKET_GROUPS',
                'icon' => 'icon-link', 'count' => null],
            ['view' => 'bundlegroups', 'label' => 'COM_MINIMARKET_BUNDLE_GROUPS',
                'icon' => 'icon-link', 'count' => null],
            ['view' => 'brands', 'label' => 'COM_MINIMARKET_BRANDS',
                'icon' => 'icon-tag', 'count' => null],
            ['view' => 'collections', 'label' => 'COM_MINIMARKET_COLLECTIONS',
                'icon' => 'icon-bookmark', 'count' => null],
            ['view' => 'attributes', 'label' => 'COM_MINIMARKET_ATTRIBUTES',
                'icon' => 'icon-list', 'count' => null],
            ['view' => 'orderoptions', 'label' => 'COM_MINIMARKET_ORDER_OPTIONS',
                'icon' => 'icon-sliders-h', 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_MARKETING',
        'type' => 'marketing',
        'icon' => 'icon-bullhorn',
        'items' => [
            ['view' => 'autodiscounts', 'label' => 'COM_MINIMARKET_AUTO_DISCOUNTS_TITLE',
                'icon' => 'icon-percent', 'count' => null],
            ['view' => 'promocodes', 'label' => 'COM_MINIMARKET_PROMOCODES_TITLE',
                'icon' => 'icon-tag', 'count' => null],
            ['view' => 'marketingflows', 'label' => 'COM_MINIMARKET_MARKETING_FLOWS_TITLE',
                'icon' => 'icon-envelope', 'count' => null],
            ['view' => 'recommendations', 'label' => 'COM_MINIMARKET_RECOMMENDATIONS_TITLE',
                'icon' => 'icon-link', 'count' => null],
            ['view' => 'abandonedcarts', 'label' => 'COM_MINIMARKET_ABANDONED_CARTS_TITLE',
                'icon' => 'icon-cart', 'count' => 'abandonedcarts'],
            ['view' => 'reviews', 'label' => 'COM_MINIMARKET_REVIEWS_TITLE',
                'icon' => 'icon-star', 'count' => 'reviews'],
            ['view' => 'seofilters', 'label' => 'COM_MINIMARKET_SEOFILTERS_TITLE',
                'icon' => 'icon-filter', 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_TOOLS',
        'type' => 'tools',
        'icon' => 'icon-wrench',
        'items' => [
            ['view' => 'import', 'label' => 'COM_MINIMARKET_IMPORT_TITLE',
                'icon' => 'icon-exchange-alt', 'count' => null],
            ['view' => 'feeds', 'label' => 'COM_MINIMARKET_FEEDS_TITLE',
                'icon' => 'icon-share', 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_SETTINGS',
        'type' => 'settings',
        'icon' => 'icon-cog',
        'items' => [
            ['view' => 'deliverymethods', 'label' => 'COM_MINIMARKET_DELIVERY_SETTINGS_TITLE',
                'icon' => 'icon-truck', 'count' => null],
            ['view' => 'paymentmethods', 'label' => 'COM_MINIMARKET_PAYMENT_SETTINGS_TITLE',
                'icon' => 'icon-credit-card', 'count' => null],
            ['view' => 'registrationmethods', 'label' => 'COM_MINIMARKET_REGISTRATION_SETTINGS_TITLE',
                'icon' => 'icon-user', 'count' => null],
            ['view' => 'notificationevents', 'label' => 'COM_MINIMARKET_NOTIFICATION_SETTINGS_TITLE',
                'icon' => 'icon-bell', 'count' => null],
            ['view' => 'fiscalproviders', 'label' => 'COM_MINIMARKET_FISCAL_SETTINGS_TITLE',
                'icon' => 'icon-file', 'count' => null],
            ['view' => 'settings', 'label' => 'COM_MINIMARKET_GENERAL_SETTINGS_TITLE',
                'icon' => 'icon-cog', 'count' => null],
        ],
    ],
];

$mmDashboardSvgPaths=[
    'icon-cog'=>'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z M19.4 15a1.65 1.65 0 0 0 .33 1.82l-1.45 1.45a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-.95 1.5V21h-3v-1.56a1.65 1.65 0 0 0-1-1.5 1.65 1.65 0 0 0-1.81.33l-1.45-1.45a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.5-.95H5v-3h1.56a1.65 1.65 0 0 0 1.5-1 1.65 1.65 0 0 0-.33-1.81L9.18 6.8A1.65 1.65 0 0 0 11 7.13a1.65 1.65 0 0 0 1-1.5V4h3v1.56a1.65 1.65 0 0 0 1 1.5 1.65 1.65 0 0 0 1.81-.33l1.45 1.45a1.65 1.65 0 0 0-.33 1.82 1.65 1.65 0 0 0 1.5.95H21v3h-1.56a1.65 1.65 0 0 0-1.5 1Z',
    'icon-bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4',
    'icon-cart'=>'M3 3h2l2.4 11.4a2 2 0 0 0 2 1.6H19l2-9H6 M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM19 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
    'icon-truck'=>'M2 6h12v12H2V6ZM14 10h4l4 4v4h-8M5 18a2 2 0 1 0 4 0M17 18a2 2 0 1 0 4 0',
    'icon-reply'=>'M10 9 5 14l5 5M5 14h10a5 5 0 0 0 0-10h-3',
    'icon-credit-card'=>'M2 5h20v14H2V5ZM2 10h20M6 15h5',
    'icon-file'=>'M6 2h8l5 5v15H6V2ZM14 2v5h5M9 12h7M9 16h7',
    'icon-users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM18 8a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87',
    'icon-comments'=>'M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9H13a8.48 8.48 0 0 1 8 8v.5Z',
    'icon-chart-bar'=>'M4 20V11h4v9M10 20V4h4v16M16 20v-7h4v7M2 20h20',
    'icon-box'=>'M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8ZM3.3 7 12 12l8.7-5M12 22V12',
    'icon-folder'=>'M3 7V4h7l2 3h9v13H3V7Z',
    'icon-cube'=>'M12 2 3 7v10l9 5 9-5V7l-9-5ZM3 7l9 5 9-5M12 12v10',
    'icon-link'=>'M10 13a5 5 0 0 0 7 .5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7',
    'icon-tag'=>'M20 13 12 21 3 12V3h9l8 8v2ZM7.5 7.5h.01',
    'icon-bookmark'=>'M5 3h14v18l-7-4-7 4V3Z',
    'icon-list'=>'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
    'icon-sliders-h'=>'M4 7h16M4 17h16M8 4v6M16 14v6',
    'icon-bullhorn'=>'M3 9h4l12-5v16l-12-5H3V9ZM7 15l2 5h3',
    'icon-percent'=>'M19 5 5 19M7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
    'icon-star'=>'m12 2 3 6 6.6 1-4.8 4.7L18 21l-6-3.1L6 21l1.2-7.3L2.4 9 9 8l3-6Z',
    'icon-filter'=>'M3 4h18l-7 8v7l-4 2v-9L3 4Z',
    'icon-wrench'=>'M14 7a6 6 0 0 0-7 7l-5 5 3 3 5-5a6 6 0 0 0 7-7l-3 3-4-1-1-4 3-3Z',
    'icon-exchange-alt'=>'M4 7h16M16 3l4 4-4 4M20 17H4M8 13l-4 4 4 4',
    'icon-share'=>'M18 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM8.7 10.5l6.6-4M8.7 13.5l6.6 4',
    'icon-user'=>'M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM4 22v-3a8 8 0 0 1 16 0v3',
    'icon-envelope'=>'M3 5h18v14H3V5ZM3 6l9 7 9-7',
];
?>
<div class="com-minimarket-dashboard mm-admin-panel">
    <div class="mm-dashboard-topbar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h2 class="h3 mb-1"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?><?php if(!empty($this->version)): ?> <span class="badge bg-secondary fs-6 align-middle ms-2">v<?php echo htmlspecialchars((string)$this->version, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></h2>
            <p class="text-muted mb-0"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_WIDGETS_HELP'); ?></p>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
        <a class="btn btn-warning d-inline-flex align-items-center gap-2 mm-dashboard-alert-button"
           href="<?php echo AdminEntityLinks::view('notifications'); ?>">
            <span class="icon-bell" aria-hidden="true"></span>
            <span><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_TITLE'); ?></span>
            <?php if((int)($this->stats['notifications_attention']??0)>0): ?>
              <span class="badge bg-danger text-white rounded-pill"
                    data-mm-stat="notifications_attention">
                <?php echo (int)$this->stats['notifications_attention']; ?>
              </span>
            <?php endif; ?>
        </a>
        <a class="btn btn-primary d-inline-flex align-items-center gap-2"
           href="<?php echo htmlspecialchars(Uri::root().'index.php?option=com_minimarket&view=catalog', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
           target="_blank" rel="noopener noreferrer">
            <span class="icon-eye" aria-hidden="true"></span>
            <span><?php echo Text::_('COM_MINIMARKET_OPEN_SITE'); ?></span>
        </a>
        </div>
    </div>

    <?php foreach ($sections as $section) : ?>
        <section class="mm-dashboard-band card mm-dashboard-band--<?php echo htmlspecialchars($section['type'], ENT_QUOTES, 'UTF-8'); ?> mb-3">
            <div class="card-header mm-dashboard-band-header">
                <h3 class="h6 d-flex align-items-center gap-2 mb-0">
                    <span class="<?php echo htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8'); ?> mm-dashboard-heading-icon" aria-hidden="true"></span>
                    <span><?php echo Text::_($section['title']); ?></span>
                </h3>
            </div>
            <div class="card-body">
                <div class="mm-dashboard-tiles">
                    <?php foreach ($section['items'] as $item) : ?>
                        <?php if (!empty($item['pending'])) : ?>
                            <div class="mm-dashboard-tile mm-dashboard-tile--pending"
                                 aria-label="<?php echo htmlspecialchars(Text::_($item['label']), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="mm-dashboard-tile-top">
                                    <span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> mm-dashboard-icon" aria-hidden="true"></span>
                                    <span class="badge mm-dashboard-count"
                                          aria-label="<?php echo htmlspecialchars(Text::_('COM_MINIMARKET_DASHBOARD_NO_COUNT'), ENT_QUOTES, 'UTF-8'); ?>">—</span>
                                </div>
                                <span class="mm-dashboard-tile-title"><?php echo Text::_($item['label']); ?></span>
                            </div>
                        <?php else : ?>
                            <a class="mm-dashboard-tile text-decoration-none"
                               href="<?php echo AdminEntityLinks::view($item['view']); ?>">
                                <div class="mm-dashboard-tile-top">
                                    <?php if ($item['view'] === 'deliveries') : ?>
                                        <svg class="mm-dashboard-icon mm-dashboard-icon--delivery" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="<?php echo htmlspecialchars(Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'),ENT_QUOTES,'UTF-8'); ?>">
                                            <path d="M3 6h11v12H3zM14 10h4l3 4v4h-7M5 18a2 2 0 1 0 4 0M16 18a2 2 0 1 0 4 0M3 10h11"/>
                                        </svg>
                                    <?php elseif ($item['view'] === 'deliverymethods') : ?>
                                        <svg class="mm-dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="2" y="6" width="12" height="12" rx="1"/><path d="M14 10h4l4 4v4h-8"/>
                                            <circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>
                                        </svg>
                                    <?php elseif ($item['view'] === 'paymentmethods' || $item['view'] === 'payments') : ?>
                                        <svg class="mm-dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h5"/>
                                        </svg>
                                    <?php elseif ($item['view'] === 'registrationmethods') : ?>
                                        <svg class="mm-dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M18 9v6M15 12h6"/>
                                        </svg>
                                    <?php elseif ($item['view'] === 'bundles') : ?>
                                        <svg class="mm-dashboard-icon mm-dashboard-icon--bundle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M12 2 17 5 12 8 7 5 12 2ZM7 5v6l5 3 5-3V5M12 8v6"/>
                                            <path d="m6 12 5 3-5 3-5-3 5-3ZM1 15v5l5 3 5-3v-5M6 18v5"/>
                                            <path d="m18 12 5 3-5 3 5-3-5-3ZM13 15v5l5 3 5-3v-5M18 18v5"/>
                                        </svg>
                                    <?php elseif ($item['view'] === 'import') : ?>
                                        <svg class="mm-dashboard-icon mm-dashboard-icon--import" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M7 3v11m0 0-4-4m4 4 4-4"/>
                                            <path d="M17 21V10m0 0-4 4m4-4 4 4"/>
                                            <path d="M3 18v3h8M21 6V3h-8"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg class="mm-dashboard-icon" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                             stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="<?php echo htmlspecialchars($mmDashboardSvgPaths[$item['icon']],ENT_QUOTES,'UTF-8'); ?>"/>
                                        </svg>
                                    <?php endif; ?>
                                    <?php $mmCount=$item['count']===null?null:($this->stats[$item['count']]??null); ?>
                                    <?php $mmPartial=$item['view']==='notifications'
                                        && !empty($this->stats['notifications_capped']); ?>
                                    <?php if($mmCount!==null && ((int)$mmCount>0 || $mmPartial)): ?>
                                    <span class="badge mm-dashboard-count"
                                          data-mm-stat="<?php echo htmlspecialchars((string)$item['count'], ENT_QUOTES, 'UTF-8'); ?>"
                                          aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_MINIMARKET_DASHBOARD_ATTENTION_COUNT',
                                               Text::_($item['label']),(int)$mmCount),ENT_QUOTES,'UTF-8'); ?>">
                                      <?php echo $mmPartial
                                          ?((int)$mmCount>0?'≥'.(int)$mmCount:'…')
                                          :(int)$mmCount; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span class="mm-dashboard-tile-title"><?php echo Text::_($item['label']); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
