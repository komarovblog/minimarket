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
    ] as $section => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-product', $section, Text::_($label)); ?>
        <div class="card card-body mm-admin-panel"<?php echo $section === 'variants' ? ' data-mm-variants-editor' : ''; ?>>
            <?php if ($section === 'bundle' && (int)($this->item->id ?? 0)>0
                && ($this->item->product_type ?? 'simple')==='bundle') :
                $bundleQuote=\MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::calculate(
                    (int)$this->item->id,
                    \MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice::snapshot(
                        \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)));
            ?>
                <p class="alert alert-info">
                    <?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_CURRENT'); ?>:
                    <strong><?php echo $this->escape((string)$bundleQuote['price']); ?></strong>
                    (<?php echo Text::_('COM_MINIMARKET_BUNDLE_PRICE_COMPONENT_TOTAL'); ?>:
                    <?php echo $this->escape((string)$bundleQuote['component_total']); ?>)
                </p>
            <?php endif; ?>
            <?php if ($section === 'variants') : ?>
                <?php echo $this->form->renderField('variants_present'); ?>
                <?php echo $this->form->renderField('variant_dimensions_present'); ?>
                <?php echo $this->form->renderField('category_option_preview'); ?>
                <?php echo $this->form->renderField('sku_matrix_payload'); ?>
            <?php else : ?>
                <?php echo $this->form->renderFieldset($section); ?>
            <?php endif; ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
