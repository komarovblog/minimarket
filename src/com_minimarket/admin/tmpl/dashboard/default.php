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
            ['view' => 'workqueue', 'label' => 'COM_MINIMARKET_WORKQUEUE_TITLE',
                'icon' => 'icon-bell', 'count' => 'workqueue'],
            ['view' => 'notifications', 'label' => 'COM_MINIMARKET_NOTIFICATIONS_TITLE',
                'icon' => 'icon-bell', 'count' => 'notifications_unread'],
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
            ['view' => 'deliverymethods', 'label' => 'COM_MINIMARKET_DELIVERY_METHODS_TITLE',
                'icon' => 'icon-truck', 'count' => null],
            ['view' => 'paymentmethods', 'label' => 'COM_MINIMARKET_PAYMENT_METHODS_TITLE',
                'icon' => 'icon-credit-card', 'count' => null],
            ['view' => 'registrationmethods', 'label' => 'COM_MINIMARKET_REGISTRATION_METHODS_TITLE',
                'icon' => 'icon-user', 'count' => null],
            ['view' => 'notificationmethods', 'label' => 'COM_MINIMARKET_NOTIFICATION_METHODS_TITLE',
                'icon' => 'icon-envelope', 'count' => null],
            ['view' => 'notificationevents', 'label' => 'COM_MINIMARKET_NOTIFICATION_EVENTS_TITLE',
                'icon' => 'icon-bell', 'count' => null],
            ['view' => 'fiscalproviders', 'label' => 'COM_MINIMARKET_FISCAL_PROVIDERS_TITLE',
                'icon' => 'icon-file', 'count' => null],
            ['view' => 'settings', 'label' => 'COM_MINIMARKET_CONFIG_SETTINGS_MENU',
                'icon' => 'icon-cog', 'count' => null],
        ],
    ],
];
?>
<div class="com-minimarket-dashboard mm-admin-panel">
    <div class="mm-dashboard-topbar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h2 class="h3 mb-1"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?><?php if(!empty($this->version)): ?> <span class="badge bg-secondary fs-6 align-middle ms-2">v<?php echo htmlspecialchars((string)$this->version, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></h2>
            <p class="text-muted mb-0"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_WIDGETS_HELP'); ?></p>
        </div>
        <a class="btn btn-primary d-inline-flex align-items-center gap-2"
           href="<?php echo htmlspecialchars(Uri::root().'index.php?option=com_minimarket&view=catalog', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
           target="_blank" rel="noopener noreferrer">
            <span class="icon-eye" aria-hidden="true"></span>
            <span><?php echo Text::_('COM_MINIMARKET_OPEN_SITE'); ?></span>
        </a>
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
                <?php if ($section['type'] === 'reports') : ?>
                    <p class="form-text mb-3"><?php echo Text::_('COM_MINIMARKET_REPORTS_OVERVIEW_HELP'); ?></p>
                <?php endif; ?>
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
                                        <span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> mm-dashboard-icon" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?php $mmCount=$item['count']===null?null:($this->stats[$item['count']]??null); ?>
                                    <?php if($mmCount!==null && (int)$mmCount>0): ?>
                                    <span class="badge mm-dashboard-count"
                                          data-mm-stat="<?php echo htmlspecialchars((string)$item['count'], ENT_QUOTES, 'UTF-8'); ?>"
                                          aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_MINIMARKET_DASHBOARD_ATTENTION_COUNT',
                                               Text::_($item['label']),(int)$mmCount),ENT_QUOTES,'UTF-8'); ?>">
                                      <?php echo $item['view']==='notifications'&&!empty($this->stats['notifications_capped'])
                                          ?'999+':(int)$mmCount; ?>
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
