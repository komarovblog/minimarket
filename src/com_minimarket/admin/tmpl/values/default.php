<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<p>
    <a href="<?php echo Route::_('index.php?option=com_minimarket&view=attributes'); ?>"
       class="btn btn-outline-secondary"><?php echo Text::_('COM_MINIMARKET_BACK_ATTRIBUTES'); ?></a>
</p>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->attributeId); ?>"
      method="post" name="adminForm" id="adminForm">
    <table class="table">
        <thead><tr>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_VALUE_CODE'); ?></th>
            <th><?php echo Text::_('JSTATUS'); ?></th>
            <th>ID</th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=value.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->attribute_title); ?></td>
                <td><?php echo $this->escape($item->code); ?></td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="5"><?php echo Text::_('COM_MINIMARKET_NO_VALUES'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
