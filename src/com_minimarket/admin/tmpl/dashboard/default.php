<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

$sections=[
    ['title'=>'COM_MINIMARKET_DASHBOARD_CATALOG','icon'=>'icon-folder',
     'links'=>[['categories','COM_MINIMARKET_CATEGORIES'],['products','COM_MINIMARKET_PRODUCTS'],
       ['bundles','COM_MINIMARKET_BUNDLES'],['brands','COM_MINIMARKET_BRANDS'],
       ['collections','COM_MINIMARKET_COLLECTIONS']]],
    ['title'=>'COM_MINIMARKET_DASHBOARD_RELATIONS','icon'=>'icon-link',
     'links'=>[['groups','COM_MINIMARKET_PRODUCT_TAB_GROUPS'],['bundlegroups','COM_MINIMARKET_BUNDLE_GROUPS'],
       ['attributes','COM_MINIMARKET_ATTRIBUTES'],['orderoptions','COM_MINIMARKET_ORDER_OPTIONS']]],
    ['title'=>'COM_MINIMARKET_DASHBOARD_EXCHANGE','icon'=>'icon-share',
     'links'=>[['import','COM_MINIMARKET_IMPORT_TITLE'],['feeds','COM_MINIMARKET_FEEDS_TITLE']]],
];
$stats=[
    ['categories','COM_MINIMARKET_CATEGORIES','categories','icon-folder'],
    ['products','COM_MINIMARKET_PRODUCTS','products','icon-cube'],
    ['bundles','COM_MINIMARKET_BUNDLES','bundles','icon-box'],
    ['feeds','COM_MINIMARKET_FEEDS_TITLE','feeds','icon-share'],
];
?>
<div class="com-minimarket-dashboard mm-admin-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h3 mb-1"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?></h2>
            <p class="text-muted mb-0"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_WIDGETS_HELP'); ?></p>
        </div>
        <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars(Uri::root().'index.php?option=com_minimarket&view=catalog',ENT_QUOTES,'UTF-8'); ?>"
            target="_blank" rel="noopener noreferrer">
            <span class="icon-eye" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_OPEN_SITE'); ?>
        </a>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach($stats as [$key,$label,$view,$icon]): ?>
            <div class="col-6 col-lg-3">
                <a class="card h-100 text-decoration-none" href="<?php echo AdminEntityLinks::view($view); ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="text-muted"><?php echo Text::_($label); ?></span>
                            <span class="<?php echo $icon; ?>" aria-hidden="true"></span>
                        </div>
                        <div class="mm-dashboard-stat"><?php echo (int)($this->stats[$key]??0); ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-3 mm-dashboard-cards">
        <?php foreach($sections as $section): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card"><div class="card-body">
                <h3 class="h5"><span class="<?php echo $section['icon']; ?>" aria-hidden="true"></span>
                    <?php echo Text::_($section['title']); ?></h3>
                <div class="list-group list-group-flush mb-3">
                    <?php foreach($section['links'] as [$view,$label]): ?>
                        <a class="list-group-item list-group-item-action" href="<?php echo AdminEntityLinks::view($view); ?>">
                            <?php echo Text::_($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div></div>
        </div>
        <?php endforeach; ?>
        <div class="col-md-6 col-xl-4">
            <div class="card"><div class="card-body">
                <h3 class="h5"><span class="icon-cart" aria-hidden="true"></span>
                    <?php echo Text::_('COM_MINIMARKET_DASHBOARD_ORDERS'); ?></h3>
                <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_ORDERS_PENDING'); ?></p>
                <button class="btn btn-outline-secondary" type="button" disabled>
                    <?php echo Text::_('COM_MINIMARKET_DASHBOARD_ORDERS'); ?>
                </button>
            </div></div>
        </div>
    </div>
</div>
