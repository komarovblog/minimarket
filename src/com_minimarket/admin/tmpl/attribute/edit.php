<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate"
      data-mm-inherited="<?php
      $global=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
      $current=(string)$global->get('attribute_display_location','both');
      $label=['product'=>'COM_MINIMARKET_VISIBILITY_PRODUCT',
              'category'=>'COM_MINIMARKET_VISIBILITY_CATEGORY',
              'both'=>'COM_MINIMARKET_VISIBILITY_BOTH'][$current]??'COM_MINIMARKET_VISIBILITY_BOTH';
      echo htmlspecialchars(json_encode(['display_location'=>Text::_($label)],
          JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP),
          ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
      ?>">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-attribute', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_TAB_MAIN',
        'filter' => 'COM_MINIMARKET_ATTRIBUTE_FILTER_TAB',
        'categories' => 'COM_MINIMARKET_ATTRCAT_TAB',
        'publishing' => 'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ] as $section => $title) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-attribute', $section, Text::_($title)); ?>
        <div class="card card-body mm-admin-panel"><?php echo $this->form->renderFieldset($section); ?></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
