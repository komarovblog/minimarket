<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
?>
<form action="<?php echo Route::_('index.php'); ?>" method="get" class="d-flex flex-wrap align-items-end gap-2 mb-3">
    <input type="hidden" name="option" value="com_minimarket">
    <input type="hidden" name="view" value="products">
    <label for="mm-product-search"><?php echo Text::_('COM_MINIMARKET_PRODUCT_SEARCH'); ?>
        <input id="mm-product-search" name="mm_search" type="search" class="form-control"
               value="<?php echo $this->escape($this->search); ?>">
    </label>
    <label for="mm-product-category"><?php echo Text::_('COM_MINIMARKET_PRODUCT_CATEGORY'); ?>
        <select id="mm-product-category" class="form-select" name="mm_category">
            <option value="0"><?php echo Text::_('COM_MINIMARKET_PRODUCT_ALL_CATEGORIES'); ?></option>
            <?php foreach ($this->categoryOptions as $row) : ?>
                <?php $category = $row['item']; ?>
                <option value="<?php echo (int) $category->id; ?>"<?php echo (int) $category->id === $this->categoryFilter ? ' selected' : ''; ?>>
                    <?php echo $this->escape(str_repeat('— ', min(20, $row['depth'])) . $category->title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label for="mm-product-state"><?php echo Text::_('JSTATUS'); ?>
        <select id="mm-product-state" class="form-select" name="mm_state">
            <?php foreach (['all' => 'COM_MINIMARKET_CATEGORY_ALL_STATUSES', '1' => 'JPUBLISHED', '0' => 'JUNPUBLISHED'] as $state => $key) : ?>
                <option value="<?php echo $state; ?>"<?php echo $this->stateFilter === (string) $state ? ' selected' : ''; ?>>
                    <?php echo Text::_($key); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label for="mm-product-sort"><?php echo Text::_('COM_MINIMARKET_PRODUCT_SORT'); ?>
        <select id="mm-product-sort" class="form-select" name="mm_sort">
            <?php foreach ([
                'newest' => 'COM_MINIMARKET_PRODUCT_SORT_NEWEST',
                'title' => 'COM_MINIMARKET_PRODUCT_SORT_TITLE',
                'price_asc' => 'COM_MINIMARKET_PRODUCT_SORT_PRICE_ASC',
                'price_desc' => 'COM_MINIMARKET_PRODUCT_SORT_PRICE_DESC',
                'order' => 'COM_MINIMARKET_PRODUCT_SORT_MANUAL',
            ] as $sort => $key) : ?>
                <option value="<?php echo $sort; ?>"<?php echo $this->sort === $sort ? ' selected' : ''; ?>>
                    <?php echo Text::_($key); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_minimarket&view=products'); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
</form>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=products'); ?>" method="post"
      name="adminForm" id="adminForm">
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_IMAGE'); ?></th>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORIES'); ?></th>
            <th><?php echo Text::_('JSTATUS'); ?></th>
            <th>ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $item) :
            $image = explode('#', (string) ($item->image ?? ''), 2)[0];
            $safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
                && !preg_match('/[\x00-\x1f<>]/', $image);
        ?>
            <tr>
                <td>
                    <?php if ($safeImage) : ?>
                        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
                             alt="<?php echo $this->escape((string) ($item->image_alt ?: $item->title)); ?>"
                             width="48" height="48" loading="lazy" style="object-fit:contain">
                    <?php else : ?>
                        <span aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_NO_IMAGE')); ?>">—</span>
                    <?php endif; ?>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=product.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->sku); ?></td>
                <td><?php echo $this->escape((string) $item->price); ?></td>
                <td><?php echo (int) $item->stock; ?></td>
                <td><?php echo $this->escape($item->category_title); ?></td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_NO_PRODUCTS'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if (is_object($this->pagination) && method_exists($this->pagination, 'getListFooter')) : ?>
        <?php echo $this->pagination->getListFooter(); ?>
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
