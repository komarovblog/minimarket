<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Button\PublishedButton;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=bundles'); ?>" method="post"
      name="adminForm" id="adminForm" class="mm-list-form">
    <div id="j-main-container" class="j-main-container">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <table class="table table-striped mm-admin-list" id="mm-bundles-list">
        <thead>
        <tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_IMAGE'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'p.title', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_TYPE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_MINIMARKET_PRODUCT_PRICE', 'p.price', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_BRAND'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORIES'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_GROUPS'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) :
            $image = explode('#', (string) ($item->image ?? ''), 2)[0];
            $safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
                && !preg_match('/[\x00-\x1f<>]/', $image)
                && is_file(JPATH_ROOT . '/' . $image);
        ?>
            <tr>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('JSELECT')); ?>"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BULK_PUBLISHED')); ?>">
                    <?php echo (new PublishedButton())->render((int) $item->state, $i,
                        ['task_prefix' => 'bundles.', 'id' => 'mm-product-state-' . (int) $item->id]); ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_IMAGE')); ?>">
                    <?php if ($safeImage) : ?>
                        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
                             alt="<?php echo $this->escape((string) ($item->image_alt ?: $item->title)); ?>"
                             width="48" height="48" loading="lazy" style="object-fit:contain">
                    <?php else : ?>
                        <span aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_NO_IMAGE')); ?>">—</span>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('JGLOBAL_TITLE')); ?>">
                    <a href="<?php echo AdminEntityLinks::edit('product',(int)$item->id); ?>"><?php echo $this->escape($item->title); ?></a>
                    <?php if ((string)($item->collection_titles ?? '') !== '') : ?>
                    <small class="d-block text-muted"><?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_COLLECTIONS') . ': ' . $item->collection_titles); ?></small>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_TYPE')); ?>">
                    <?php echo Text::_(($item->product_type ?? 'simple')==='bundle'
                        ? 'COM_MINIMARKET_PRODUCT_TYPE_BUNDLE' : 'COM_MINIMARKET_PRODUCT_TYPE_SIMPLE'); ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_SKU')); ?>"><?php echo $this->escape($item->sku); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_PRICE')); ?>"><?php echo $this->escape((string) $item->price); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_STOCK')); ?>"><?php echo (int) $item->stock; ?><?php if (($item->product_type ?? 'simple')==='bundle') : ?><small class="d-block text-muted"><?php echo Text::_('COM_MINIMARKET_BUNDLE_STOCK_DERIVED'); ?></small><?php endif; ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_BRAND')); ?>">
                    <?php if ((int)($item->brand_id ?? 0)>0) : ?>
                        <a href="<?php echo AdminEntityLinks::edit('brand',(int)$item->brand_id); ?>"><?php echo $this->escape((string)$item->brand_title); ?></a>
                    <?php else : ?>—<?php endif; ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORIES')); ?>"><?php echo $this->escape($item->category_title); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_GROUPS')); ?>"><?php echo $this->escape((string) ($item->group_titles ?? '')); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ID')); ?>"><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="12"><?php echo Text::_('COM_MINIMARKET_NO_PRODUCTS'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if (is_object($this->pagination) && method_exists($this->pagination, 'getListFooter')) : ?>
        <?php echo $this->pagination->getListFooter(); ?>
    <?php endif; ?>
    </div>
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
