<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

/**
 * Three full-width horizontal sections; every action is a 25%-width tile
 * on desktop. No vertical section menus and no fake Orders navigation.
 * CSS uses only scoped classes + Joomla's Bootstrap 5.
 */
$sections = [
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_MANAGEMENT',
        'icon' => 'icon-cog',
        'items' => [
            ['view' => null, 'label' => 'COM_MINIMARKET_DASHBOARD_ORDERS',
                'icon' => 'icon-cart', 'pending' => true, 'count' => null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_PRODUCTS_SECTION',
        'icon' => 'icon-box',
        'items' => [
            ['view' => 'categories', 'label' => 'COM_MINIMARKET_CATEGORIES',
                'icon' => 'icon-folder', 'count' => 'categories'],
            ['view' => 'products', 'label' => 'COM_MINIMARKET_PRODUCTS',
                'icon' => 'icon-cube', 'count' => 'products'],
            ['view' => 'bundles', 'label' => 'COM_MINIMARKET_BUNDLES',
                'icon' => 'icon-box', 'count' => 'bundles'],
            ['view' => 'brands', 'label' => 'COM_MINIMARKET_BRANDS',
                'icon' => 'icon-tag', 'count' => 'brands'],
            ['view' => 'collections', 'label' => 'COM_MINIMARKET_COLLECTIONS',
                'icon' => 'icon-bookmark', 'count' => 'collections'],
            ['view' => 'groups', 'label' => 'COM_MINIMARKET_GROUPS',
                'icon' => 'icon-link', 'count' => 'groups'],
            ['view' => 'bundlegroups', 'label' => 'COM_MINIMARKET_BUNDLE_GROUPS',
                'icon' => 'icon-link', 'count' => 'bundlegroups'],
            ['view' => 'attributes', 'label' => 'COM_MINIMARKET_ATTRIBUTES',
                'icon' => 'icon-list', 'count' => 'attributes'],
            ['view' => 'orderoptions', 'label' => 'COM_MINIMARKET_ORDER_OPTIONS',
                'icon' => 'icon-sliders-h', 'count' => 'orderoptions'],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_TOOLS',
        'icon' => 'icon-wrench',
        'items' => [
            ['view' => 'import', 'label' => 'COM_MINIMARKET_IMPORT_TITLE',
                'icon' => 'icon-exchange-alt', 'count' => null],
            ['view' => 'feeds', 'label' => 'COM_MINIMARKET_FEEDS_TITLE',
                'icon' => 'icon-share', 'count' => 'feeds'],
        ],
    ],
];
?>
<div class="com-minimarket-dashboard mm-admin-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h2 class="h3 mb-1"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?></h2>
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
        <section class="mm-dashboard-band card mb-3">
            <div class="card-body">
                <h3 class="h5 d-flex align-items-center gap-2 mb-3">
                    <span class="<?php echo htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
                    <span><?php echo Text::_($section['title']); ?></span>
                </h3>
                <div class="row g-3 mm-dashboard-tiles">
                    <?php foreach ($section['items'] as $item) : ?>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <?php if (!empty($item['pending'])) : ?>
                                <div class="mm-dashboard-tile mm-dashboard-tile--pending d-flex align-items-center gap-3 h-100"
                                     aria-label="<?php echo htmlspecialchars(Text::_($item['label']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo Text::_($item['label']); ?></div>
                                        <small class="text-muted"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_COMING_SOON'); ?></small>
                                    </div>
                                    <span class="badge mm-dashboard-count" aria-label="<?php echo htmlspecialchars(Text::_('COM_MINIMARKET_DASHBOARD_NO_COUNT'), ENT_QUOTES, 'UTF-8'); ?>">—</span>
                                </div>
                            <?php else : ?>
                                <a class="mm-dashboard-tile d-flex align-items-center gap-3 h-100 text-decoration-none"
                                   href="<?php echo AdminEntityLinks::view($item['view']); ?>">
                                    <span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
                                    <span class="flex-grow-1 fw-semibold"><?php echo Text::_($item['label']); ?></span>
                                    <span class="badge mm-dashboard-count" aria-label="<?php echo htmlspecialchars(Text::_($item['label']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($item['count'] === null) : ?>
                                            <span aria-hidden="true">—</span>
                                        <?php else : ?>
                                            <?php echo (int) ($this->stats[$item['count']] ?? 0); ?>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
