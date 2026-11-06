<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=product&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate"
      data-mm-inventory-enabled="<?php echo (int) ComponentHelper::getParams('com_minimarket')->get('inventory_enabled',1) === 1 ? '1' : '0'; ?>">
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
    $bundleQuote = ['price'=>'0.00','component_total'=>'0.00','discount_amount'=>'0.00'];
    if ($isBundle && (int)($this->item->id??0)>0) {
        $bundleQuote=\MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::calculate(
            (int)$this->item->id,
            \MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::snapshot(
                \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)));
    }
    unset($sections[$isBundle?'variants':'bundle']);
    if (!$isBundle) $sections['groups']='COM_MINIMARKET_PRODUCT_TAB_GROUPS';
    else $sections['groups']='COM_MINIMARKET_BUNDLE_GROUPS';
    foreach ($sections as $section=>$label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-product', $section, Text::_($label)); ?>
        <div class="card card-body mm-admin-panel"<?php echo $section === 'variants' ? ' data-mm-variants-editor' : ''; ?>>
            <?php if ($section === 'details') : ?>
                <?php
                // Entity kind comes from the sidebar editor, not a dropdown.
                // The bundle price method appears directly after SKU.
                foreach (['id','title','alias','category_id','product_type','sku'] as $field)
                    echo $this->form->renderField($field);
                if ($isBundle) {
                    echo $this->form->renderField('bundle_price_mode');
                    echo $this->form->renderField('price');
                    echo $this->form->renderField('old_price');
                    echo $this->form->renderField('bundle_discount_pct');
                    ?>
                    <div class="mm-bundle-totals" data-mm-bundle-totals data-mm-discount-only<?php echo (($this->item->bundle_price_mode ?? "fixed")==="discount") ? "" : " hidden"; ?>>
                        <span><?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_COMPONENT_TOTAL'); ?></span>
                        <strong data-mm-bundle-components-total><?php echo $this->escape($bundleQuote['component_total']); ?></strong>
                        <span data-mm-bundle-final-label
                            data-mm-fixed-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BUNDLE_PRICE_FINAL_FIXED')); ?>"
                            data-mm-discount-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BUNDLE_PRICE_FINAL_DISCOUNT')); ?>"><?php echo Text::_(($this->item->bundle_price_mode ?? 'fixed')==='discount' ? 'COM_MINIMARKET_BUNDLE_PRICE_FINAL_DISCOUNT' : 'COM_MINIMARKET_BUNDLE_PRICE_FINAL_FIXED'); ?></span>
                        <strong data-mm-bundle-final-total><?php echo $this->escape($bundleQuote['price']); ?></strong>
                    </div>
                    <?php
                    // Retain stored values for fields irrelevant to bundle sales.
                    echo '<div hidden aria-hidden="true">';
                    echo $this->form->renderField('stock');
                    echo $this->form->renderField('option_stock_mode');
                    echo '</div>';
                } else {
                    echo $this->form->renderField('price');
                    echo $this->form->renderField('old_price');
                    echo $this->form->renderField('option_stock_mode');
                    echo $this->form->renderField('stock');
                }
                ?>
            <?php elseif ($section === 'badges') : ?>
                <?php
                  // Explicit order is part of the admin UI contract.
                  if ($isBundle) {
                      echo $this->form->renderField('badge_benefit');
                      echo $this->form->renderField('badge_benefit_display');
                  }
                  echo $this->form->renderField('badge_sale');
                  echo $this->form->renderField('badge_sale_display');
                  echo $this->form->renderField('badge_new');
                  echo $this->form->renderField('badge_new_days');
                  echo $this->form->renderField('badge_popular');
                  echo $this->form->renderField('badge_popular_days');
                ?>
            <?php elseif ($section === 'variants') : ?>
                <?php echo $this->form->renderField('variants_present'); ?>
                <?php echo $this->form->renderField('variant_dimensions_present'); ?>
                <?php echo $this->form->renderField('category_option_preview'); ?>
                <?php echo $this->form->renderField('sku_matrix_payload'); ?>
            <?php else : ?>
                <?php echo $this->form->renderFieldset($section); ?>
                <?php if ($section === 'bundle' && $isBundle) : ?>
                    <div class="mm-bundle-totals" data-mm-bundle-totals>
                        <span><?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_COMPONENT_TOTAL'); ?></span>
                        <strong data-mm-bundle-components-total><?php echo $this->escape($bundleQuote['component_total']); ?></strong>
                        <span data-mm-bundle-final-label
                            data-mm-fixed-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BUNDLE_PRICE_FINAL_FIXED')); ?>"
                            data-mm-discount-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BUNDLE_PRICE_FINAL_DISCOUNT')); ?>"><?php echo Text::_(($this->item->bundle_price_mode ?? 'fixed')==='discount' ? 'COM_MINIMARKET_BUNDLE_PRICE_FINAL_DISCOUNT' : 'COM_MINIMARKET_BUNDLE_PRICE_FINAL_FIXED'); ?></span>
                        <strong data-mm-bundle-final-total><?php echo $this->escape($bundleQuote['price']); ?></strong>
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
