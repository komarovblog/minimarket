<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection;

$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$cfg=$this->settings;
$feedUrl=(int)$this->feed['id']>0
  ? \Joomla\CMS\Uri\Uri::root().'index.php?option=com_minimarket&task=feed.xml&id='
    .(int)$this->feed['id'].'&key='.rawurlencode((string)$this->feed['access_key'])
  : '';
$checked=array_fill_keys($this->selected,true);
$selectedCategories=array_fill_keys($cfg['category_ids'],true);
$visible=array_fill_keys(FeedSelection::visibleCategories($this->categories),true);
$map=[];foreach($this->categories as $category)$map[(int)$category['id']]=$category;
$depth=static function(int $id) use($map):int {
    $n=0;$seen=[];
    while(isset($map[$id]) && (int)$map[$id]['parent_id']>0 && $n<12) {
        $id=(int)$map[$id]['parent_id'];
        if(isset($seen[$id]))break;
        $seen[$id]=true;$n++;
    }
    return $n;
};
?>
<div class="com-minimarket-feed mm-admin-panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="h4 mb-0"><?php echo Text::_((int)$this->feed['id']?'COM_MINIMARKET_FEED_EDIT':'COM_MINIMARKET_FEED_NEW'); ?></h2>
        <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=feeds',false)); ?>">
            <?php echo Text::_('JTOOLBAR_BACK'); ?>
        </a>
    </div>
    <form action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=feed.save',false)); ?>"
          method="post" id="mm-feed-editor">
        <input type="hidden" name="feed[id]" value="<?php echo (int)$this->feed['id']; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
        <?php echo HTMLHelper::_('uitab.startTabSet','minimarket-feed',['active'=>'details']); ?>
        <?php echo HTMLHelper::_('uitab.addTab','minimarket-feed','details',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
        <div class="card mb-3"><div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_FEED_GENERAL'); ?></h3>
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="mm-feed-title"><?php echo Text::_('COM_MINIMARKET_FEED_NAME'); ?></label>
                    <input id="mm-feed-title" type="text" class="form-control" name="feed[title]" maxlength="200"
                        required value="<?php echo $e($this->feed['title']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mm-feed-public-url"><?php echo Text::_('COM_MINIMARKET_FEED_URL'); ?></label>
                    <input id="mm-feed-public-url" class="form-control" type="text" readonly onclick="this.select()"
                        value="<?php echo $e($feedUrl); ?>"
                        placeholder="<?php echo $e(Text::_('COM_MINIMARKET_FEED_URL_PENDING')); ?>">
                    <?php if($feedUrl!=='' && (int)$this->feed['state']!==1): ?>
                      <div class="form-text"><?php echo Text::_('COM_MINIMARKET_FEED_URL_INACTIVE'); ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12"><label class="form-label" for="mm-feed-provider"><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER'); ?></label>
                    <select id="mm-feed-provider" name="feed[provider]" class="form-select">
                        <?php foreach(FeedConfig::PROVIDERS as $key=>$label): ?>
                            <option value="<?php echo $e($key); ?>" <?php echo $this->feed['provider']===$key?'selected':''; ?>>
                                <?php echo Text::_($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER_HELP'); ?></div>
                    <div class="form-text" data-mm-feed-sitemap-help<?php echo $this->feed['provider']==='sitemap'?'':' hidden'; ?>>
                        <?php echo Text::_('COM_MINIMARKET_FEED_SITEMAP_HELP'); ?>
                    </div>
                </div>
                <div class="col-12"><label class="form-label" for="mm-feed-company"><?php echo Text::_('COM_MINIMARKET_FEED_COMPANY'); ?></label>
                    <input id="mm-feed-company" type="text" class="form-control" name="feed[company]" maxlength="200"
                        value="<?php echo $e($this->feed['company']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-check"><input class="form-check-input" type="checkbox"
                        name="feed[include_bundles]" value="1" data-mm-feed-bundles
                        <?php echo (int)$cfg['include_bundles']===1?'checked':''; ?>>
                        <span class="form-check-label"><?php echo Text::_('COM_MINIMARKET_FEED_BUNDLES'); ?></span></label>
                </div>
            </div>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_FEED_SELECTION'); ?></h3>
            <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_FEED_SELECTION_HELP'); ?></p>
            <input type="hidden" name="feed[selection_mode]" value="auto">
            <div class="row g-3">
                <div class="col-lg-6">
                    <h4 class="h6 mb-0"><?php echo Text::_('COM_MINIMARKET_FEED_CATEGORIES'); ?> (<span data-mm-feed-category-count><?php echo count(array_intersect_key($selectedCategories,$visible)); ?></span>)</h4>
                    <input type="search" class="form-control my-2" data-mm-feed-category-search
                        aria-label="<?php echo $e(Text::_('COM_MINIMARKET_FEED_CATEGORIES')); ?>"
                        placeholder="<?php echo $e(Text::_('COM_MINIMARKET_FEED_CATEGORY_SEARCH')); ?>">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mm-feed-cats-all><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_ALL'); ?></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mm-feed-cats-none><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_NONE'); ?></button>
                    </div>
                    <div class="border rounded p-3 mm-feed-picker">
                        <?php foreach($this->categories as $cat):
                            $id=(int)$cat['id'];
                            if(!isset($visible[$id]))continue;
                        ?>
                        <label class="form-check d-block" data-mm-feed-category-row
                            data-search="<?php echo $e(mb_strtolower((string)$cat['title'])); ?>"
                            style="margin-inline-start:<?php echo min(10,$depth($id))*14; ?>px">
                            <input class="form-check-input" type="checkbox" name="feed[categories][]"
                                data-mm-feed-category data-id="<?php echo $id; ?>"
                                data-parent="<?php echo (int)$cat['parent_id']; ?>" value="<?php echo $id; ?>"
                                <?php echo isset($selectedCategories[$id])?'checked':''; ?>>
                            <span class="form-check-label"><?php echo $e($cat['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h4 class="h6 mb-0"><?php echo Text::_('COM_MINIMARKET_FEED_PRODUCTS'); ?> (<span data-mm-feed-count><?php echo count($checked); ?></span>)</h4>
                    <div class="d-flex align-items-stretch gap-2 my-2" data-mm-feed-product-toolbar>
                        <input type="search" class="form-control mm-feed-product-search" data-mm-feed-search
                            aria-label="<?php echo $e(Text::_('JSEARCH_FILTER')); ?>"
                            placeholder="<?php echo $e(Text::_('COM_MINIMARKET_FEED_SEARCH')); ?>">
                        <details class="mm-feed-product-category-filter flex-shrink-0" data-mm-feed-product-filter>
                            <summary class="btn btn-outline-secondary d-flex align-items-center gap-2 h-100">
                                <span class="icon-filter" aria-hidden="true"></span>
                                <span><?php echo Text::_('COM_MINIMARKET_FEED_FILTER_PRODUCT_CATEGORIES'); ?></span>
                                <span class="badge text-bg-secondary" data-mm-feed-product-filter-count hidden></span>
                            </summary>
                            <div class="dropdown-menu show p-3 shadow-sm mm-feed-product-category-filter-menu">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <strong class="small"><?php echo Text::_('COM_MINIMARKET_FEED_FILTER_PRODUCT_CATEGORIES'); ?></strong>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-mm-feed-product-filter-reset><?php echo Text::_('COM_MINIMARKET_FEED_FILTER_ALL_CATEGORIES'); ?></button>
                                </div>
                                <p class="form-text mt-0 mb-2"><?php echo Text::_('COM_MINIMARKET_FEED_FILTER_PRODUCT_CATEGORIES_HINT'); ?></p>
                                <p class="form-text" data-mm-feed-filter-empty hidden><?php echo Text::_('COM_MINIMARKET_FEED_FILTER_EMPTY'); ?></p>
                                <div class="mm-feed-product-category-options">
                                    <?php foreach ($this->categories as $cat): $cid=(int)$cat['id'];
                                        if(!isset($visible[$cid]))continue; ?>
                                    <label class="form-check d-block" data-mm-feed-product-filter-row>
                                        <input type="checkbox" class="form-check-input"
                                            data-mm-feed-product-filter-option value="<?php echo $cid; ?>">
                                        <span class="form-check-label"><?php echo $e($cat['title']); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mm-feed-all><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_ALL'); ?></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mm-feed-none><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_NONE'); ?></button>
                    </div>
                    <div class="border rounded p-3 mm-feed-picker">
                        <?php foreach($this->products as $product):
                            $id=(int)$product['id'];
                            if((int)$product['state']!==1 || !isset($visible[(int)$product['category_id']]))continue;
                        ?>
                        <label class="form-check d-block mm-feed-product" data-mm-feed-row
                            data-search="<?php echo $e(mb_strtolower($product['title'].' '.($product['sku']??''))); ?>">
                            <input class="form-check-input" type="checkbox"
                                name="feed[products][]" value="<?php echo $id; ?>"
                                data-mm-feed-product data-category="<?php echo (int)$product['category_id']; ?>"
                                data-bundle="<?php echo ($product['product_type']==='bundle')?'1':'0'; ?>"
                                <?php echo isset($checked[$id])?'checked':''; ?>>
                            <span class="form-check-label"><?php echo $e($product['title']); ?>
                                <small class="text-muted"><?php echo $e($product['sku']??''); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                        <p class="form-text mt-2" data-mm-feed-no-products hidden><?php echo Text::_('COM_MINIMARKET_FEED_NO_PRODUCTS'); ?></p>
                    </div>
                </div>
            </div>
        </div></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.addTab','minimarket-feed','publishing',Text::_('COM_MINIMARKET_CATEGORY_TAB_PUBLISHING')); ?>
                    <div class="card mb-3" data-mm-feed-publication><div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_CATEGORY_TAB_PUBLISHING'); ?></h3>
            <div class="row g-3">
                <div class="col-lg-6"><label class="form-label" for="mm-feed-state"><?php echo Text::_('JSTATUS'); ?></label>
                    <select id="mm-feed-state" name="feed[state]" class="form-select">
                        <option value="0" <?php echo (int)$this->feed['state']===0?'selected':''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
                        <option value="1" <?php echo (int)$this->feed['state']===1?'selected':''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
                    </select>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="mm-feed-order"><?php echo Text::_('COM_MINIMARKET_CATEGORY_ORDER'); ?></label>
                    <input class="form-control" type="number" id="mm-feed-order" name="feed[sort_order]"
                        min="0" max="4294967295" step="1" value="<?php echo (int)($this->feed['sort_order']??0); ?>">
                </div>
            </div>
        </div></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-success"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
            <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=feeds',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
        </div>
</form>
    <p class="mt-3 text-muted small"><?php echo Text::_('COM_MINIMARKET_FEED_LIMITATIONS'); ?></p>
</div>
