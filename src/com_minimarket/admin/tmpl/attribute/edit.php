<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-attribute', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_ATTRIBUTE_DETAILS',
        'categories' => 'COM_MINIMARKET_ATTRCAT_TAB',
    ] as $section => $title) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-attribute', $section, Text::_($title)); ?>
        <div class="card card-body"><?php echo $this->form->renderFieldset($section); ?></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
