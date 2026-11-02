<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=product&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-product', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_PRODUCT_TAB_DETAILS',
        'descriptions' => 'COM_MINIMARKET_PRODUCT_TAB_DESCRIPTIONS',
        'attributes' => 'COM_MINIMARKET_PRODUCT_TAB_ATTRIBUTES',
        'variants' => 'COM_MINIMARKET_PRODUCT_TAB_VARIANTS',
        'images' => 'COM_MINIMARKET_PRODUCT_TAB_IMAGES',
        'videos' => 'COM_MINIMARKET_PRODUCT_TAB_VIDEOS',
        'seo' => 'COM_MINIMARKET_CATEGORY_TAB_SEO',
        'publishing' => 'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ] as $section => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-product', $section, Text::_($label)); ?>
        <div class="card card-body">
            <?php echo $this->form->renderFieldset($section); ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
