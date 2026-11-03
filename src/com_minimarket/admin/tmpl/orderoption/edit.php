<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=orderoption&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-orderoption', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_ORDER_OPTION_EDIT',
        'values' => 'COM_MINIMARKET_ORDER_OPTION_VALUES'
    ] as $tab => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-orderoption', $tab, Text::_($label)); ?>
        <div class="card card-body"><?php echo $this->form->renderFieldset($tab); ?></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
