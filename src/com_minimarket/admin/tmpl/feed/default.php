<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection;

$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$cfg=$this->settings;
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
        <div class="card mb-3"><div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_FEED_GENERAL'); ?></h3>
            <div class="row g-3">
                <div class="col-lg-6"><label class="form-label" for="mm-feed-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
                    <input id="mm-feed-title" type="text" class="form-control" name="feed[title]" maxlength="200"
                        required value="<?php echo $e($this->feed['title']); ?>">
                </div>
                <div class="col-lg-6"><label class="form-label" for="mm-feed-company"><?php echo Text::_('COM_MINIMARKET_FEED_COMPANY'); ?></label>
                    <input id="mm-feed-company" type="text" class="form-control" name="feed[company]" maxlength="200"
                        value="<?php echo $e($this->feed['company']); ?>">
                </div>
                <div class="col-lg-6"><label class="form-label" for="mm-feed-provider"><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER'); ?></label>
                    <select id="mm-feed-provider" name="feed[provider]" class="form-select">
                        <?php foreach(FeedConfig::PROVIDERS as $key=>$label): ?>
                            <option value="<?php echo $e($key); ?>" <?php echo $this->feed['provider']===$key?'selected':''; ?>>
                                <?php echo Text::_($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER_HELP'); ?></div>
                </div>
                <div class="col-lg-6"><label class="form-label" for="mm-feed-state"><?php echo Text::_('JSTATUS'); ?></label>
                    <select id="mm-feed-state" name="feed[state]" class="form-select">
                        <option value="0" <?php echo (int)$this->feed['state']===0?'selected':''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
                        <option value="1" <?php echo (int)$this->feed['state']===1?'selected':''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
                    </select>
                </div>
            </div>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_FEED_SELECTION'); ?></h3>
            <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_FEED_SELECTION_HELP'); ?></p>
            <div class="row g-3 mb-3">
                <div class="col-md-5"><label for="mm-feed-mode" class="form-label"><?php echo Text::_('COM_MINIMARKET_FEED_MODE'); ?></label>
                    <select class="form-select" id="mm-feed-mode" name="feed[selection_mode]">
                        <option value="auto" <?php echo $cfg['selection_mode']==='auto'?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_FEED_AUTO'); ?></option>
                        <option value="manual" <?php echo $cfg['selection_mode']==='manual'?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_FEED_MANUAL'); ?></option>
                    </select>
                </div>
                <div class="col-md-7 d-flex gap-3 align-items-end flex-wrap">
                    <label class="form-check"><input class="form-check-input" type="checkbox"
                        name="feed[include_descendants]" value="1" data-mm-feed-descendants
                        <?php echo (int)$cfg['include_descendants']===1?'checked':''; ?>>
                        <span class="form-check-label"><?php echo Text::_('COM_MINIMARKET_FEED_DESCENDANTS'); ?></span></label>
                    <label class="form-check"><input class="form-check-input" type="checkbox"
                        name="feed[include_bundles]" value="1" data-mm-feed-bundles
                        <?php echo (int)$cfg['include_bundles']===1?'checked':''; ?>>
                        <span class="form-check-label"><?php echo Text::_('COM_MINIMARKET_FEED_BUNDLES'); ?></span></label>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-xl-5">
                    <h4 class="h6"><?php echo Text::_('COM_MINIMARKET_FEED_CATEGORIES'); ?></h4>
                    <div class="border rounded p-3 mm-feed-picker">
                        <?php foreach($this->categories as $cat):
                            $id=(int)$cat['id'];
                            if(!isset($visible[$id]))continue;
                        ?>
                        <label class="form-check d-block" style="margin-inline-start:<?php echo min(10,$depth($id))*14; ?>px">
                            <input class="form-check-input" type="checkbox" name="feed[categories][]"
                                data-mm-feed-category data-id="<?php echo $id; ?>"
                                data-parent="<?php echo (int)$cat['parent_id']; ?>" value="<?php echo $id; ?>"
                                <?php echo isset($selectedCategories[$id])?'checked':''; ?>>
                            <span class="form-check-label"><?php echo $e($cat['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_MINIMARKET_FEED_PRODUCTS'); ?> (<span data-mm-feed-count><?php echo count($checked); ?></span>)</h4>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" data-mm-feed-all><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_ALL'); ?></button>
                            <button type="button" class="btn btn-outline-secondary" data-mm-feed-none><?php echo Text::_('COM_MINIMARKET_FEED_SELECT_NONE'); ?></button>
                        </div>
                    </div>
                    <input type="search" class="form-control form-control-sm my-2" data-mm-feed-search
                        aria-label="<?php echo $e(Text::_('JSEARCH_FILTER')); ?>"
                        placeholder="<?php echo $e(Text::_('COM_MINIMARKET_FEED_SEARCH')); ?>">
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
                    </div>
                </div>
            </div>
        </div></div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-success"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
            <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=feeds',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
        </div>
    </form>
    <p class="mt-3 text-muted small"><?php echo Text::_('COM_MINIMARKET_FEED_LIMITATIONS'); ?></p>
</div>
