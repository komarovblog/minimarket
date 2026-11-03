<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Button\PublishedButton;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attributes'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <table class="table table-striped" id="mm-attributes-list">
        <thead><tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_CODE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_VARIANT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_FILTERABLE'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) : ?>
            <tr>
                <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                <td class="text-center">
                    <?php echo (new PublishedButton())->render((int) $item->state, $i,
                        ['task_prefix' => 'attributes.', 'id' => 'mm-attribute-state-' . (int) $item->id]); ?>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=attribute.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->code); ?></td>
                <td><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_' . strtoupper((string) $item->input_type)); ?></td>
                <td><?php echo (int) $item->is_variant ? Text::_('JYES') : Text::_('JNO'); ?></td>
                <td><?php echo (int) $item->is_filterable ? Text::_('JYES') : Text::_('JNO'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_NO_ATTRIBUTES'); ?></td></tr>
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
