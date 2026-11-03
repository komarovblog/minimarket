<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Button\PublishedButton;
?>
<?php if ($this->attributeId > 0) : ?>
<p><a href="<?php echo Route::_(
    'index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) $this->attributeId
); ?>" class="btn btn-outline-secondary"><?php echo Text::_('COM_MINIMARKET_BACK_ATTRIBUTE'); ?></a></p>
<?php endif; ?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->attributeId); ?>"
      method="post" name="adminForm" id="adminForm">
    <table class="table table-striped">
        <thead><tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th class="w-1 text-center"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
            <th><a href="<?php echo Route::_(
                'index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->attributeId
                . '&sort=title&direction=' . ($this->sort === 'title' && $this->direction === 'asc' ? 'desc' : 'asc')
            ); ?>"><?php echo Text::_('JGLOBAL_TITLE'); ?></a></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_CODE'); ?></th>
            <th><a href="<?php echo Route::_(
                'index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->attributeId
                . '&sort=order&direction=' . ($this->sort === 'order' && $this->direction === 'asc' ? 'desc' : 'asc')
            ); ?>"><?php echo Text::_('JGRID_HEADING_ORDERING'); ?></a></th>
            <th>ID</th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) : ?>
            <tr>
                <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                <td class="text-center">
                    <?php echo (new PublishedButton())->render((int) $item->state, $i,
                        ['task_prefix' => 'values.', 'id' => 'mm-value-state-' . (int) $item->id]); ?>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=value.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->attribute_title); ?></td>
                <td><?php echo $this->escape($item->code); ?></td>
                <td><?php echo (int) $item->sort_order; ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="7"><?php echo Text::_('COM_MINIMARKET_NO_VALUES'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
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
