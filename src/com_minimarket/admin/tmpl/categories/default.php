<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Button\PublishedButton;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=categories'); ?>"
      method="post" name="adminForm" id="adminForm" class="mm-list-form">
    <div id="j-main-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <div class="table-responsive mm-native-list-viewport">
<table class="table table-striped mm-admin-list" id="mm-category-tree">
            <thead>
            <tr>
                <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
                <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_THUMB'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_COUNT_DIRECT'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_COUNT_BRANCH'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_CATEGORY_ORDER'); ?></th>
                <th class="w-1"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->rows as $i => $row) :
                $item = $row['item'];
                $id = (int) $item->id;
                $depth = min(20, (int) $row['depth']);
                $editUrl = Route::_('index.php?option=com_minimarket&task=category.edit&id=' . $id);
                $image = explode('#', (string) ($item->image ?? ''), 2)[0];
                $safeImage = str_starts_with($image, 'images/')
                    && !str_contains($image, '..')
                    && !preg_match('/[\x00-\x1f<>]/', $image)
                && is_file(JPATH_ROOT . '/' . $image);
            ?>
                <tr>
                    <td class="text-center" data-label="<?php echo $this->escape(Text::_('JSELECT')); ?>"><?php echo HTMLHelper::_('grid.id', $i, $id); ?></td>
                    <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BULK_PUBLISHED')); ?>">
                        <?php echo (new PublishedButton())->render((int) $item->state, $i,
                            ['task_prefix' => 'categories.', 'id' => 'mm-category-state-' . $id]); ?>
                    </td>
                    <td data-label="<?php echo $this->escape(Text::_('JGLOBAL_TITLE')); ?>">
                        <span style="display:inline-block;padding-inline-start:<?php echo $depth * 16; ?>px">
                            <?php if ($depth > 0) : ?>
                                <span class="text-muted" aria-hidden="true">— </span>
                            <?php endif; ?>
                            <a href="<?php echo $editUrl; ?>"><?php echo $this->escape((string) $item->title); ?></a>
                            <?php if ($row['broken_parent']) : ?>
                                <span class="text-warning"
                                      title="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_BROKEN_PARENT')); ?>">⚠</span>
                            <?php endif; ?>
                            <small class="d-block text-muted">
                                <?php echo $this->escape((string) ($item->alias ?? '')); ?>
                            </small>
                        </span>
                    </td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_THUMB')); ?>">
                        <?php if ($safeImage) : ?>
                            <a href="<?php echo $editUrl; ?>">
                                <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
                                     alt="<?php echo $this->escape((string) ($item->image_alt ?: $item->title)); ?>"
                                     width="48" height="48" loading="lazy" style="object-fit:contain"/>
                            </a>
                        <?php else : ?>
                            <span class="text-muted"
                                  aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_NO_IMAGE')); ?>">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_COUNT_DIRECT')); ?>"><?php echo (int) $row['direct_count']; ?></td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_COUNT_BRANCH')); ?>"><?php echo (int) $row['branch_count']; ?></td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CATEGORY_ORDER')); ?>"><?php echo (int) ($item->sort_order ?? 0); ?></td>
                    <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ID')); ?>"><?php echo $id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->rows) : ?>
                <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_NO_CATEGORIES'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
</div>
    </div>
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
