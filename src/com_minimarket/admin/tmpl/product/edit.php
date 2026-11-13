<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=product&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate"
      data-mm-inventory-enabled="<?php echo (int) ComponentHelper::getParams('com_minimarket')->get('inventory_enabled',1) === 1 ? '1' : '0'; ?>"
      data-mm-inherited="<?php echo htmlspecialchars(json_encode(['meta_robots'=>(string)\Joomla\CMS\Factory::getConfig()->get('robots','index,follow')],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP),ENT_QUOTES,'UTF-8'); ?>">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-product', ['active' => 'details']); ?>
    <?php
    // Distinct admin lists share one persisted entity and schema, not one UI.
    $isBundle=($this->item->product_type ?? '')==='bundle'
        || ((int)($this->item->id ?? 0)===0
            && \Joomla\CMS\Factory::getApplication()->input->getCmd('product_type')==='bundle');
    $sections=[
        'details' => 'COM_MINIMARKET_TAB_MAIN',
        'badges' => 'COM_MINIMARKET_BADGES_TAB',
        'descriptions' => 'COM_MINIMARKET_PRODUCT_TAB_DESCRIPTIONS',
        'merchandising' => 'COM_MINIMARKET_PRODUCT_TAB_MERCHANDISING',
        'recommend_similar' => 'COM_MINIMARKET_RECOMM_SIMILAR_TAB',
        'recommend_together' => 'COM_MINIMARKET_RECOMM_TOGETHER_TAB',
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
                foreach (['id','title','alias','category_id','product_type','delivery_type',
                    'digital_delivery_mode','digital_delivery_note','sku'] as $field)
                    echo $this->form->renderField($field);
                if ($isBundle) {
                    echo $this->form->renderField('bundle_price_mode');
                    echo $this->form->renderField('price');
                    echo $this->form->renderField('old_price');
                    echo $this->form->renderField('bundle_discount_pct');
                    if (\Joomla\CMS\Factory::getApplication()->getIdentity()
                        ->authorise('core.admin','com_minimarket')) {
                        echo '<p class="form-text">'.Text::_('COM_MINIMARKET_BUNDLE_COST_DERIVED').'</p>';
                    }
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
                    if (\Joomla\CMS\Factory::getApplication()->getIdentity()
                        ->authorise('core.admin','com_minimarket')) {
                        echo $this->form->renderField('cost_price');
                    }
                    echo $this->form->renderField('old_price');
                    echo $this->form->renderField('option_stock_mode');
                    echo $this->form->renderField('stock');
                }
                if (!$isBundle) :
                ?>
                <div class="control-group" data-mm-digital-upload
                     <?php echo ($this->item->delivery_type??'physical')==='digital'
                         &&($this->item->digital_delivery_mode??'file')==='file'?'':'hidden'; ?>>
                    <div class="control-label">
                        <label for="mm-digital-upload"><?php echo Text::_('COM_MINIMARKET_DIGITAL_UPLOAD'); ?></label>
                    </div>
                    <div class="controls">
                        <input id="mm-digital-upload" name="digital_upload" type="file" class="form-control"
                               accept=".pdf,.zip,.epub,.mp3,.mp4,.txt">
                        <div class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_UPLOAD_HELP'); ?></div>
                        <?php if(!empty($this->item->digital_filename)): ?>
                            <div class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_CURRENT_FILE'); ?>:
                                <?php echo $this->escape((string)$this->item->digital_filename); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="control-group" data-mm-digital-license
                     <?php echo ($this->item->delivery_type??'physical')==='digital'
                         &&($this->item->digital_delivery_mode??'file')==='license'?'':'hidden'; ?>>
                    <div class="control-label">
                        <label for="mm-digital-license-import"><?php echo Text::_('COM_MINIMARKET_DIGITAL_LICENSE_IMPORT'); ?></label>
                    </div>
                    <div class="controls">
                        <textarea id="mm-digital-license-import" class="form-control" rows="4"
                                  name="digital_keys_import" autocomplete="off"
                                  placeholder="<?php echo $this->escape(Text::_('COM_MINIMARKET_DIGITAL_LICENSE_PLACEHOLDER')); ?>"></textarea>
                        <div class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_LICENSE_IMPORT_HELP'); ?></div>
                    </div>
                </div>
                <?php endif;
                // Delivery zone tariffs use these physical measurements.
                echo $this->form->renderField('shipping_weight_grams');
                echo $this->form->renderField('shipping_volume_cm3');
                ?>
            <?php elseif ($section === 'badges') : ?>
                <?php
                // A flag's generic heading is followed by its own name next
                // to the native Joomla checkbox. Display units are independent.
                $renderFlag = function (string $field, string $name, string $help = ''): void {
                    $id = 'jform_' . $field;
                    ?>
                    <div class="control-group mm-badge-flag" data-mm-badge-flag="<?php echo $this->escape($field); ?>">
                        <div class="control-label">
                            <span class="form-label"><?php echo Text::_('COM_MINIMARKET_BADGE_SHOW_FLAG'); ?></span>
                        </div>
                        <div class="controls">
                            <div class="d-flex align-items-center gap-2">
                                <?php echo $this->form->getInput($field); ?>
                                <label for="<?php echo $this->escape($id); ?>" class="form-check-label">
                                    <?php echo Text::_($name); ?>
                                </label>
                            </div>
                            <?php if ($help !== '') : ?>
                                <div class="form-text"><?php echo Text::_($help); ?></div>
                                <?php if ($field === 'badge_sale') : ?>
                                    <div class="text-warning small" data-mm-badge-sale-hint hidden>
                                        <?php echo Text::_('COM_MINIMARKET_BADGE_SALE_NEEDS_PRICE'); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                };
                if ($isBundle) {
                    $renderFlag('badge_benefit', 'COM_MINIMARKET_BADGE_BENEFIT_NAME',
                        'COM_MINIMARKET_BADGE_BENEFIT_HELP');
                    echo $this->form->renderField('badge_benefit_display');
                }
                $renderFlag('badge_sale', 'COM_MINIMARKET_BADGE_SALE_NAME',
                    'COM_MINIMARKET_BADGE_SALE_HELP');
                echo $this->form->renderField('badge_sale_display');
                $renderFlag('badge_new', 'COM_MINIMARKET_BADGE_NEW_NAME');
                echo $this->form->renderField('badge_new_days');
                $renderFlag('badge_popular', 'COM_MINIMARKET_BADGE_POPULAR_NAME');
                echo $this->form->renderField('badge_popular_days');
                ?>
            <?php elseif ($section === 'recommend_similar' || $section === 'recommend_together') : ?>
                <?php
                $type=$section==='recommend_similar'?'similar':'together';
                if((int)($this->item->id??0)>0):
                    echo $this->form->renderField('rec_'.$type.'_mode');
                    $mmReference=['input'=>'jform[rec_'.$type.'_ids]',
                        'endpoint'=>'search','multiple'=>true,
                        'selected'=>$this->{'rec'.ucfirst($type)},
                        'title'=>Text::_('COM_MINIMARKET_RECOMMEND_TARGETS'),
                        'help'=>Text::_('COM_MINIMARKET_RECOMM_PRODUCT_OVERRIDE_HELP')];
                    require dirname(__DIR__).'/shared/reference-picker.php';
                    ?>
                    <p class="form-text"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=category.edit&id='.(int)$this->item->category_id,false)); ?>">
                        <?php echo Text::_('COM_MINIMARKET_RECOMM_OPEN_CATEGORY_RULES'); ?></a></p>
                <?php else: ?>
                    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_RECOMM_SAVE_PRODUCT_FIRST'); ?></p>
                <?php endif; ?>
            <?php elseif ($section === 'variants') : ?>
                <?php echo $this->form->renderField('variants_present'); ?>
                <?php echo $this->form->renderField('variant_dimensions_present'); ?>
                <?php echo $this->form->renderField('category_option_preview'); ?>
                <?php echo $this->form->renderField('sku_matrix_payload'); ?>
            <?php elseif ($section === 'videos') : ?>
                <?php echo $this->form->renderField('videos_present'); ?>
                <section class="card mb-3" data-mm-promo-video-editor>
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <strong><?php echo Text::_('COM_MINIMARKET_PROMO_VIDEO_SECTION'); ?></strong>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-mm-promo-toggle>
                            <?php echo Text::_('COM_MINIMARKET_PROMO_VIDEO_ADD'); ?></button>
                    </div>
                    <div class="card-body" data-mm-promo-fields<?php echo empty($this->item->promo_video)?' hidden':''; ?>>
                        <p class="text-muted small"><?php echo Text::_('COM_MINIMARKET_PROMO_VIDEO_HELP'); ?></p>
                        <?php echo $this->form->renderField('promo_video'); ?>
                    </div>
                </section>
                <h3 class="h5 mb-3"><?php echo Text::_('COM_MINIMARKET_REGULAR_VIDEOS_SECTION'); ?></h3>
                <?php echo $this->form->renderField('videos'); ?>
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
