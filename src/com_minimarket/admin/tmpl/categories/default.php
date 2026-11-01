<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=categories'); ?>" method="post"
      name="adminForm" id="adminForm">
    <table class="table">
        <thead>
        <tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('JFIELD_ALIAS_LABEL'); ?></th><th><?php echo Text::_('COM_MINIMARKET_CATEGORY_PARENT'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th>ID</th></tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=category.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->alias); ?></td>
                <td><?php echo (int) ($item->parent_id ?? 0) > 0
                    ? $this->escape((string) ($item->parent_title ?? 'ID ' . (int) $item->parent_id))
                    : Text::_('COM_MINIMARKET_CATEGORY_PARENT_ROOT'); ?></td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="5"><?php echo Text::_('COM_MINIMARKET_NO_CATEGORIES'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
