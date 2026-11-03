<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=category&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-category', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_CATEGORY_TAB_DETAILS',
        'descriptions' => 'COM_MINIMARKET_CATEGORY_TAB_DESCRIPTIONS',
        'display' => 'COM_MINIMARKET_CATEGORY_TAB_DISPLAY',
        'attributes' => 'COM_MINIMARKET_CATEGORY_TAB_ATTRIBUTES',
        'order_options' => 'COM_MINIMARKET_ORDER_OPTIONS',
        'seo' => 'COM_MINIMARKET_CATEGORY_TAB_SEO',
        'publishing' => 'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ] as $section => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-category', $section, Text::_($label)); ?>
        <div class="card card-body">
            <?php echo $this->form->renderFieldset($section); ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
