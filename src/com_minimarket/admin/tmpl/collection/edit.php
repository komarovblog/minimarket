<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=collection&layout=edit&id='
    . (int)($this->item->id??0)); ?>" method="post" name="adminForm" id="adminForm" class="form-validate"
      data-mm-inherited="<?php echo htmlspecialchars(json_encode(['meta_robots'=>(string)\Joomla\CMS\Factory::getConfig()->get('robots','index,follow')],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP),ENT_QUOTES,'UTF-8'); ?>">
    <?php echo HTMLHelper::_('uitab.startTabSet','mm-collection',['active'=>'details']); ?>
    <?php foreach ([
        'details'=>'COM_MINIMARKET_TAB_MAIN',
        'descriptions'=>'COM_MINIMARKET_PRODUCT_TAB_DESCRIPTIONS',
        'seo'=>'COM_MINIMARKET_CATEGORY_TAB_SEO',
        'publishing'=>'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ] as $section=>$label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab','mm-collection',$section,Text::_($label)); ?>
        <div class="card card-body mm-admin-panel">
            <?php echo $this->form->renderFieldset($section); ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
