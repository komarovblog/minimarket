<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-attribute', ['active' => 'details']); ?>
    <?php foreach ([
        'details' => 'COM_MINIMARKET_ATTRIBUTE_DETAILS',
        'categories' => 'COM_MINIMARKET_ATTRCAT_TAB',
    ] as $section => $title) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-attribute', $section, \Joomla\CMS\Language\Text::_($title)); ?>
        <div class="card card-body"><?php echo $this->form->renderFieldset($section); ?></div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php if ((string) ($this->item->input_type ?? '') === 'list') : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-attribute', 'values',
            Text::_('COM_MINIMARKET_ATTR_VALUES_TAB')); ?>
        <?php if ((int) $this->item->id < 1) : ?>
            <p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SAVE_FIRST'); ?></p>
        <?php else : ?>
            <?php
            $returnToAttribute = base64_encode(
                'index.php?option=com_minimarket&view=attribute&layout=edit&id=' . (int) $this->item->id
            );
            ?>
            <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_HELP'); ?></p>
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                <a class="btn btn-success" href="<?php echo Route::_(
                    'index.php?option=com_minimarket&view=value&layout=edit&attribute_id=' . (int) $this->item->id
                    . '&return=' . rawurlencode($returnToAttribute)
                ); ?>"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_ADD'); ?></a>
                <label for="mm-values-sort"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SORT_LABEL'); ?></label>
                <select id="mm-values-sort" class="form-select w-auto" data-mm-values-sort>
                    <option value="order"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SORT_ORDER'); ?></option>
                    <option value="az"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SORT_AZ'); ?></option>
                    <option value="za"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SORT_ZA'); ?></option>
                </select>
            </div>
            <table class="table table-striped" data-mm-values-table>
                <thead><tr>
                    <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_MINIMARKET_VALUE_CODE'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
                    <th scope="col"><?php echo Text::_('JGRID_HEADING_ORDERING'); ?></th>
                </tr></thead>
                <tbody data-mm-values-body>
                <?php foreach ($this->dictionaryValues as $value) : ?>
                    <tr data-mm-value-row
                        data-mm-value-title="<?php echo $this->escape($value->title); ?>"
                        data-mm-value-id="<?php echo (int) $value->id; ?>">
                        <td><a href="<?php echo Route::_(
                            'index.php?option=com_minimarket&view=value&layout=edit&id=' . (int) $value->id
                            . '&return=' . rawurlencode($returnToAttribute)
                        ); ?>"><?php echo $this->escape($value->title); ?></a></td>
                        <td><?php echo $this->escape($value->code); ?></td>
                        <td><?php echo (int) $value->state === 1
                            ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                        <td>
                            <input type="number" class="form-control form-control-sm"
                                style="max-width:7rem"
                                name="jform[values_order][<?php echo (int) $value->id; ?>]"
                                data-mm-value-order
                                min="0" max="4294967295" step="1"
                                value="<?php echo (int) $value->sort_order; ?>"
                                aria-label="<?php echo $this->escape(Text::sprintf(
                                    'COM_MINIMARKET_ATTR_VALUES_ORDER_FOR', $value->title
                                )); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$this->dictionaryValues) : ?>
                    <tr><td colspan="4"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_EMPTY'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <input type="hidden" name="jform[values_order_present]" value="1">
            <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_SORT_HELP'); ?></p>
        <?php endif; ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
