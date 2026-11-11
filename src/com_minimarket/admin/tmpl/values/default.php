<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Layout\LayoutHelper;
?>
<?php if ($this->attributeId > 0) : ?>
<p><a href="<?php echo Route::_(
    'index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) $this->attributeId
); ?>" class="btn btn-outline-secondary"><?php echo Text::_('COM_MINIMARKET_BACK_ATTRIBUTE'); ?></a></p>
<?php endif; ?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->attributeId); ?>"
      method="post" name="adminForm" id="adminForm" class="mm-list-form">
    <div id="j-main-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <div class="table-responsive mm-native-list-viewport">
<table class="table table-striped mm-admin-list">
        <thead><tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'v.title',
                    $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_CODE'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'v.sort_order',
                    $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'v.id',
                    $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) : ?>
            <tr>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('JSELECT')); ?>"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BULK_PUBLISHED')); ?>">
                    <?php echo (new PublishedButton())->render((int) $item->state, $i,
                        ['task_prefix' => 'values.', 'id' => 'mm-value-state-' . (int) $item->id]); ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('JGLOBAL_TITLE')); ?>"><a href="<?php echo Route::_('index.php?option=com_minimarket&task=value.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE')); ?>"><?php echo $this->escape($item->attribute_title); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_VALUE_CODE')); ?>"><?php echo $this->escape($item->code); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ORDERING')); ?>"><?php echo (int) $item->sort_order; ?></td>
                <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ID')); ?>"><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php if (!$this->items): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_NO_VALUES'); ?></div><?php endif; ?>
</div>
        <?php if (is_object($this->pagination) && method_exists($this->pagination, 'getListFooter')) : ?>
            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>
    </div>
    <?php if ($this->attributeId > 0) : ?>
        <input type="hidden" name="attribute_id" value="<?php echo (int) $this->attributeId; ?>">
        <input type="hidden" name="return" value="<?php echo htmlspecialchars(
            base64_encode('index.php?option=com_minimarket&view=attribute&layout=edit&id='
                . (int) $this->attributeId), ENT_QUOTES, 'UTF-8'
        ); ?>">
    <?php endif; ?>
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
