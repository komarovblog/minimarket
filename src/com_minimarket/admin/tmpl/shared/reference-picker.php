<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
/**
 * $mmReference: input, endpoint, multiple, selected (id=>title),
 * title, subtitle, filters ([name,label,type]), optional class.
 * All selected IDs are revalidated on the server before saving.
 */
$ref=(array)$mmReference;
$esc=static fn($value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$endpoint='index.php?option=com_minimarket&task=productpicker.'.$ref['endpoint']
    .'&'.Session::getFormToken().'=1';
$multiple=!empty($ref['multiple']);
$selected=(array)($ref['selected']??[]);
$input=(string)$ref['input'];
$editPrefix=(string)($ref['edit_url_prefix']??'');
$labels=[
 'add'=>Text::_('COM_MINIMARKET_REF_ADD'),
 'change'=>Text::_('COM_MINIMARKET_REF_CHANGE'),
 'edit'=>Text::_('COM_MINIMARKET_REF_EDIT'),
 'remove'=>Text::_('COM_MINIMARKET_REF_REMOVE'),
 'loading'=>Text::_('COM_MINIMARKET_REF_LOADING'),
 'empty'=>Text::_('COM_MINIMARKET_REF_EMPTY'),
 'error'=>Text::_('COM_MINIMARKET_REF_ERROR')
];
?>
<div class="mm-entity-picker mm-reference-picker <?php echo $esc($ref['class']??''); ?>"
     data-mm-reference-picker data-mm-ref-url="<?php echo $esc($endpoint); ?>"
     data-mm-ref-multiple="<?php echo $multiple?'1':'0'; ?>"
     data-mm-ref-edit-prefix="<?php echo $esc($editPrefix); ?>"
     data-mm-ref-labels="<?php echo $esc(json_encode($labels,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)); ?>">
 <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
  <strong><?php echo $esc($ref['title']); ?></strong>
  <button type="button" class="btn btn-success btn-sm" data-mm-ref-toggle>
   <?php echo !$multiple && count($selected)>0 ? Text::_('COM_MINIMARKET_REF_CHANGE') : '+ '.Text::_('COM_MINIMARKET_REF_ADD'); ?>
  </button>
 </div>
 <div data-mm-ref-panel hidden class="py-2 mb-2">
  <div class="d-flex gap-2 flex-wrap">
   <input type="search" data-mm-ref-search class="form-control flex-grow-1"
          style="min-width:12rem" maxlength="100"
          placeholder="<?php echo $esc($ref['placeholder']??Text::_('COM_MINIMARKET_REF_SEARCH')); ?>"
          aria-label="<?php echo $esc($ref['title']); ?>">
   <?php foreach((array)($ref['filters']??[]) as $field): ?>
    <?php if(($field['type']??'text')==='date'): ?>
     <label class="small"><?php echo $esc($field['label']); ?>
      <input data-mm-ref-filter="<?php echo $esc($field['name']); ?>" class="form-control" type="date"></label>
    <?php endif; ?>
   <?php endforeach; ?>
   <button type="button" class="btn btn-primary btn-sm" data-mm-ref-submit><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
  </div>
  <div data-mm-ref-results class="mt-2"></div>
  <p data-mm-ref-feedback class="text-danger mb-0" role="status"></p>
 </div>
 <div data-mm-ref-selected aria-live="polite">
  <?php foreach($selected as $id=>$title): if((int)$id<1)continue; ?>
   <div class="mm-entity-picker__row" data-mm-ref-key="<?php echo (int)$id; ?>">
    <span class="flex-grow-1"><?php echo $esc($title); ?></span>
    <?php if($editPrefix!==''): ?><a class="btn btn-outline-secondary btn-sm" href="<?php echo $esc($editPrefix.(int)$id); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_MINIMARKET_REF_EDIT'); ?></a><?php endif; ?>
    <button type="button" class="btn btn-danger btn-sm" data-mm-ref-remove
      aria-label="<?php echo $esc(Text::_('COM_MINIMARKET_REF_REMOVE')); ?>">−</button>
   </div>
  <?php endforeach; ?>
 </div>
 <input type="hidden" data-mm-ref-input name="<?php echo $esc($input); ?>"
        value="<?php echo $esc($multiple?implode(',',array_keys($selected)):(array_key_first($selected)??'')); ?>">
 <?php if(isset($ref['help'])): ?><p class="form-text"><?php echo $esc($ref['help']); ?></p><?php endif; ?>
</div>