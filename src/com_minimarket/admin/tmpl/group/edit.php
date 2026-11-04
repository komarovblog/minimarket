<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=group&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-group', ['active' => 'details']); ?>
    <?php foreach (['details' => 'COM_MINIMARKET_GROUP_DETAILS',
                    'members' => 'COM_MINIMARKET_GROUP_MEMBERS'] as $section => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-group', $section, Text::_($label)); ?>
        <div class="card card-body mm-admin-panel"><?php echo $this->form->renderFieldset($section); ?></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
