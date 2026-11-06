<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

// Every section is one quarter of the row on wide desktops. A fourth section
// can be introduced later without changing this layout or Joomla's Atum.
$sections = [
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_ORDERS',
        'icon' => 'icon-cart',
        'kind' => 'orders',
        'links' => [],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_SHOP',
        'icon' => 'icon-store',
        'kind' => 'shop',
        'links' => [
            ['categories', 'COM_MINIMARKET_CATEGORIES', 'categories'],
            ['products', 'COM_MINIMARKET_PRODUCTS', 'products'],
            ['bundles', 'COM_MINIMARKET_BUNDLES', 'bundles'],
            ['brands', 'COM_MINIMARKET_BRANDS', null],
            ['collections', 'COM_MINIMARKET_COLLECTIONS', null],
            ['groups', 'COM_MINIMARKET_GROUPS', null],
            ['bundlegroups', 'COM_MINIMARKET_BUNDLE_GROUPS', null],
            ['attributes', 'COM_MINIMARKET_ATTRIBUTES', null],
            ['orderoptions', 'COM_MINIMARKET_ORDER_OPTIONS', null],
        ],
    ],
    [
        'title' => 'COM_MINIMARKET_DASHBOARD_TOOLS',
        'icon' => 'icon-wrench',
        'kind' => 'tools',
        'links' => [
            ['import', 'COM_MINIMARKET_IMPORT_TITLE', null],
            ['feeds', 'COM_MINIMARKET_FEEDS_TITLE', 'feeds'],
        ],
    ],
];
?>
<div class="com-minimarket-dashboard mm-admin-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
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

    <div class="row g-3 mm-dashboard-cards">
        <?php foreach ($sections as $section) : ?>
            <div class="col-12 col-md-6 col-xl-3">
                <section class="card mm-dashboard-section h-100">
                    <div class="card-body">
                        <h3 class="h5 d-flex align-items-center gap-2">
                            <span class="<?php echo htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
                            <span><?php echo Text::_($section['title']); ?></span>
                        </h3>
                        <?php if ($section['kind'] === 'orders') : ?>
                            <p class="text-muted mb-0">
                                <?php echo Text::_('COM_MINIMARKET_DASHBOARD_ORDERS_PENDING'); ?>
                            </p>
                        <?php else : ?>
                            <nav class="list-group list-group-flush" aria-label="<?php echo htmlspecialchars(Text::_($section['title']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($section['links'] as [$view, $label, $counter]) : ?>
                                    <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2"
                                       href="<?php echo AdminEntityLinks::view($view); ?>">
                                        <span><?php echo Text::_($label); ?></span>
                                        <?php if ($counter !== null) : ?>
                                            <span class="badge text-bg-light border">
                                                <?php echo (int) ($this->stats[$counter] ?? 0); ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
    </div>
</div>
