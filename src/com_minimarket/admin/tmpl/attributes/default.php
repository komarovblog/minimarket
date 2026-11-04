<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Button\PublishedButton;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attributes'); ?>"
      method="post" name="adminForm" id="adminForm" class="mm-list-form">
    <div id="j-main-container" class="j-main-container">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <table class="table table-striped mm-admin-list" id="mm-attributes-list">
        <thead><tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_CODE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_FILTERABLE'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) : ?>
            <tr>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('JSELECT')); ?>"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BULK_PUBLISHED')); ?>">
                    <?php echo (new PublishedButton())->render((int) $item->state, $i,
                        ['task_prefix' => 'attributes.', 'id' => 'mm-attribute-state-' . (int) $item->id]); ?>
                </td>
                <td data-label="<?php echo $this->escape(Text::_('JGLOBAL_TITLE')); ?>"><a href="<?php echo Route::_('index.php?option=com_minimarket&task=attribute.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE')); ?>"><?php echo $this->escape($item->code); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE')); ?>"><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_' . strtoupper((string) $item->input_type)); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_ATTRIBUTE_FILTERABLE')); ?>"><?php echo (int) $item->is_filterable ? Text::_('JYES') : Text::_('JNO'); ?></td>
                <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ID')); ?>"><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="7"><?php echo Text::_('COM_MINIMARKET_NO_ATTRIBUTES'); ?></td></tr>
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
