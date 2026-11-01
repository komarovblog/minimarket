<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attributes'); ?>"
      method="post" name="adminForm" id="adminForm">
    <table class="table">
        <thead><tr>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_CODE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_VARIANT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_FILTERABLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUES'); ?></th>
            <th><?php echo Text::_('JSTATUS'); ?></th><th>ID</th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=attribute.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->code); ?></td>
                <td><?php echo Text::_('COM_MINIMARKET_ATTRIBUTE_' . strtoupper((string) $item->input_type)); ?></td>
                <td><?php echo (int) $item->is_variant ? Text::_('JYES') : Text::_('JNO'); ?></td>
                <td><?php echo (int) $item->is_filterable ? Text::_('JYES') : Text::_('JNO'); ?></td>
                <td>
                    <?php if ($item->input_type === 'list') : ?>
                        <a href="<?php echo Route::_('index.php?option=com_minimarket&view=values&attribute_id=' . (int) $item->id); ?>"><?php echo Text::_('COM_MINIMARKET_EDIT_VALUES'); ?></a>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_NO_ATTRIBUTES'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
