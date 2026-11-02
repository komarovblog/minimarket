<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
?>
<form action="<?php echo Route::_('index.php'); ?>" method="get" class="d-flex align-items-end gap-2 mb-3">
    <input type="hidden" name="option" value="com_minimarket">
    <input type="hidden" name="view" value="categories">
    <label for="mm-search"><?php echo Text::_('COM_MINIMARKET_CATEGORY_FILTER_SEARCH'); ?>
        <input id="mm-search" type="search" class="form-control" name="mm_search" value="<?php echo $this->escape($this->search); ?>">
    </label>
    <label for="mm-state"><?php echo Text::_('JSTATUS'); ?>
        <select id="mm-state" name="mm_state" class="form-select">
            <?php foreach (['all' => 'COM_MINIMARKET_CATEGORY_ALL_STATUSES', '1' => 'JPUBLISHED', '0' => 'JUNPUBLISHED'] as $state => $key) : ?>
                <option value="<?php echo $state; ?>"<?php echo $this->stateFilter === (string) $state ? ' selected' : ''; ?>>
                    <?php echo Text::_($key); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_minimarket&view=categories'); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
</form>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=categories'); ?>" method="post"
      name="adminForm" id="adminForm">
    <table class="table table-striped" id="mm-category-tree">
        <thead>
        <tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_THUMB'); ?></th>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_COUNT_DIRECT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_COUNT_BRANCH'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_ORDER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th>ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->rows as $i => $row) :
            $item = $row['item'];
            $id = (int) $item->id;
            $depth = (int) $row['depth'];
            $image = explode('#', (string) ($item->image ?? ''), 2)[0];
            $safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
                && !preg_match('/[\x00-\x1f<>]/', $image);
        ?>
            <tr data-mm-depth="<?php echo $depth; ?>">
                <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $id); ?></td>
                <td>
                    <?php if ($safeImage) : ?>
                        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
                             alt="<?php echo $this->escape((string) ($item->image_alt ?: $item->title)); ?>"
                             width="48" height="48" loading="lazy" style="object-fit:contain">
                    <?php else : ?>
                        <span aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_NO_IMAGE')); ?>">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="display:inline-block;padding-inline-start:<?php echo min(20, $depth) * 18; ?>px">
                        <?php if ($row['has_children']) : ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary mm-tree-toggle"
                                    aria-expanded="true" aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_TOGGLE')); ?>">▾</button>
                        <?php else : ?><span style="display:inline-block;width:2rem"></span><?php endif; ?>
                        <a href="<?php echo Route::_('index.php?option=com_minimarket&task=category.edit&id=' . $id); ?>">
                            <?php echo $this->escape($item->title); ?>
                        </a>
                        <?php if ($row['broken_parent']) : ?>
                            <span class="text-warning" title="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_BROKEN_PARENT')); ?>">⚠</span>
                        <?php endif; ?>
                    </span>
                </td>
                <td><?php echo (int) $row['direct_count']; ?></td>
                <td><?php echo (int) $row['branch_count']; ?></td>
                <td><?php echo (int) ($item->sort_order ?? 0); ?></td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo $id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->rows) : ?>
            <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_NO_CATEGORIES'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.querySelectorAll('#mm-category-tree .mm-tree-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        button.setAttribute('aria-expanded', button.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
        const rows = Array.from(document.querySelectorAll('#mm-category-tree tbody tr[data-mm-depth]'));
        const collapsed = [];
        rows.forEach(function (row) {
            const depth = Number(row.dataset.mmDepth);
            collapsed.length = depth;
            row.hidden = collapsed.some(Boolean);
            const toggle = row.querySelector('.mm-tree-toggle');
            collapsed[depth] = !!toggle && toggle.getAttribute('aria-expanded') === 'false';
        });
    });
});
</script>
