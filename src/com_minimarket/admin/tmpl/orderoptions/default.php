<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Button\PublishedButton;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=orderoptions'); ?>"
      method="post" name="adminForm" id="adminForm" class="mm-list-form">
    <div id="j-main-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <div class="table-responsive mm-native-list-viewport">
<table class="table table-striped mm-admin-list" id="mm-order-options-list">
            <thead><tr>
                <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
                <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
                <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'o.title', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_ORDER_OPTION_CODE'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_ORDER_OPTION_VALUES'); ?></th>
                <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'o.id', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center" data-label="<?php echo $this->escape(Text::_('JSELECT')); ?>"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                    <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BULK_PUBLISHED')); ?>">
                        <?php echo (new PublishedButton())->render((int) $item->state, $i,
                            ['task_prefix' => 'orderoptions.', 'id' => 'mm-order-option-state-' . (int) $item->id]); ?>
                    </td>
                    <td data-label="<?php echo $this->escape(Text::_('JGLOBAL_TITLE')); ?>"><a href="<?php echo Route::_('index.php?option=com_minimarket&task=orderoption.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape((string) $item->title); ?></a></td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_ORDER_OPTION_CODE')); ?>"><?php echo $this->escape((string) $item->code); ?></td>
                    <td data-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_ORDER_OPTION_VALUES')); ?>"><?php echo (int) $item->value_count; ?></td>
                    <td data-label="<?php echo $this->escape(Text::_('JGRID_HEADING_ID')); ?>"><?php echo (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
<?php if (!$this->items): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_ORDER_OPTIONS_EMPTY'); ?></div><?php endif; ?>
</div>
        <?php if (is_object($this->pagination) && method_exists($this->pagination, 'getListFooter')) : ?>
            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>
    </div>
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
