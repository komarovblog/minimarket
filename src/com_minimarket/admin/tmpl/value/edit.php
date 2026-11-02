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
    <div class="card card-body">
        <?php echo $this->form->renderFieldset('details'); ?>
    </div>
    <?php if ($returnToAttribute !== '') : ?>
        <input type="hidden" name="return" value="<?php echo htmlspecialchars(
            $returnToAttribute, ENT_QUOTES, 'UTF-8'
        ); ?>">
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
