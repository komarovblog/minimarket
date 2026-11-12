<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
// Preserve the parent attribute in both Save & Close and Cancel.
$parentAttributeId = (int) ($this->item->attribute_id ?? 0);
if ($parentAttributeId < 1) {
    $parentAttributeId = \Joomla\CMS\Factory::getApplication()->input->getInt('attribute_id', 0);
}
$returnToAttribute = $parentAttributeId > 0
    ? base64_encode('index.php?option=com_minimarket&view=attribute&layout=edit&id='
        . $parentAttributeId)
    : '';
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=value&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-value', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_TAB_MAIN',
        'publishing' => 'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ] as $section => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-value', $section, \Joomla\CMS\Language\Text::_($label)); ?>
        <div class="card card-body mm-admin-panel">
            <?php echo $this->form->renderFieldset($section); ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <?php if ($returnToAttribute !== '') : ?>
        <input type="hidden" name="return" value="<?php echo htmlspecialchars(
            $returnToAttribute, ENT_QUOTES, 'UTF-8'
        ); ?>">
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
