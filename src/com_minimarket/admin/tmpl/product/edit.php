<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=product&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-product', ['active' => 'details']); ?>
    <?php
    // Distinct admin lists share one persisted entity and schema, not one UI.
    $isBundle=($this->item->product_type ?? '')==='bundle'
        || ((int)($this->item->id ?? 0)===0
            && \Joomla\CMS\Factory::getApplication()->input->getCmd('product_type')==='bundle');
    $sections=[
        'details' => 'COM_MINIMARKET_PRODUCT_TAB_DETAILS',
        'badges' => 'COM_MINIMARKET_BADGES_TAB',
        'descriptions' => 'COM_MINIMARKET_PRODUCT_TAB_DESCRIPTIONS',
        'merchandising' => 'COM_MINIMARKET_PRODUCT_TAB_MERCHANDISING',
        'attributes' => 'COM_MINIMARKET_PRODUCT_TAB_ATTRIBUTES',
        'variants' => 'COM_MINIMARKET_PRODUCT_TAB_VARIANTS',
        'groups' => 'COM_MINIMARKET_PRODUCT_TAB_GROUPS',
        'bundle' => 'COM_MINIMARKET_PRODUCT_TAB_BUNDLE',
        'images' => 'COM_MINIMARKET_PRODUCT_TAB_IMAGES',
        'videos' => 'COM_MINIMARKET_PRODUCT_TAB_VIDEOS',
        'seo' => 'COM_MINIMARKET_CATEGORY_TAB_SEO',
        'publishing' => 'COM_MINIMARKET_CATEGORY_TAB_PUBLISHING',
    ];
    unset($sections[$isBundle?'variants':'bundle']);
    if (!$isBundle) $sections['groups']='COM_MINIMARKET_PRODUCT_TAB_GROUPS';
    else $sections['groups']='COM_MINIMARKET_BUNDLE_GROUPS';
    foreach ($sections as $section=>$label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-product', $section, Text::_($label)); ?>
        <div class="card card-body mm-admin-panel"<?php echo $section === 'variants' ? ' data-mm-variants-editor' : ''; ?>>
            <?php if ($section === 'details') : ?>
                <?php echo $this->form->renderFieldset('details'); ?>
                <?php if ($isBundle) : ?>
                    <div class="mm-bundle-pricing-controls" data-mm-bundle-pricing>
                        <?php echo $this->form->renderFieldset('bundle_pricing'); ?>
                        <p class="text-muted small"><?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_MODE_HELP'); ?></p>
                    </div>
                <?php endif; ?>
            <?php elseif ($section === 'badges') : ?>
                <?php echo $this->form->renderFieldset('badges'); ?>
                <?php if ($isBundle) echo $this->form->renderFieldset('bundle_benefit'); ?>
            <?php elseif ($section === 'variants') : ?>
                <?php echo $this->form->renderField('variants_present'); ?>
                <?php echo $this->form->renderField('variant_dimensions_present'); ?>
                <?php echo $this->form->renderField('category_option_preview'); ?>
                <?php echo $this->form->renderField('sku_matrix_payload'); ?>
            <?php else : ?>
                <?php echo $this->form->renderFieldset($section); ?>
                <?php if ($section === 'bundle' && $isBundle) : ?>
                    <?php
                      $quote=(int)($this->item->id??0)>0
                        ? \MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::calculate(
                            (int)$this->item->id,
                            \MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::snapshot(
                              \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)))
                        : ['price'=>'0.00','component_total'=>'0.00','discount_amount'=>'0.00'];
                    ?>
                    <div class="mm-bundle-totals" data-mm-bundle-totals>
                        <span><?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_COMPONENT_TOTAL'); ?></span>
                        <strong data-mm-bundle-components-total><?php echo $this->escape($quote['component_total']); ?></strong>
                        <span><?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_CURRENT'); ?></span>
                        <strong data-mm-bundle-final-total><?php echo $this->escape($quote['price']); ?></strong>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
