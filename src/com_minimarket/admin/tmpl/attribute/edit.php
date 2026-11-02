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
            <p><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_HELP'); ?></p>
            <p>
                <a class="btn btn-success" href="<?php echo Route::_(
                    'index.php?option=com_minimarket&view=value&layout=edit&attribute_id=' . (int) $this->item->id
                ); ?>"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_ADD'); ?></a>
                <a class="btn btn-outline-secondary" href="<?php echo Route::_(
                    'index.php?option=com_minimarket&view=values&attribute_id=' . (int) $this->item->id
                ); ?>"><?php echo Text::_('COM_MINIMARKET_EDIT_VALUES'); ?></a>
            </p>
            <table class="table table-striped">
                <thead><tr>
                    <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                    <th><?php echo Text::_('COM_MINIMARKET_VALUE_CODE'); ?></th>
                    <th><?php echo Text::_('COM_MINIMARKET_BULK_PUBLISHED'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($this->dictionaryValues as $value) : ?>
                    <tr>
                        <td><a href="<?php echo Route::_(
                            'index.php?option=com_minimarket&view=value&layout=edit&id=' . (int) $value->id
                        ); ?>"><?php echo $this->escape($value->title); ?></a></td>
                        <td><?php echo $this->escape($value->code); ?></td>
                        <td><?php echo (int) $value->state === 1
                            ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$this->dictionaryValues) : ?>
                    <tr><td colspan="3"><?php echo Text::_('COM_MINIMARKET_ATTR_VALUES_EMPTY'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
