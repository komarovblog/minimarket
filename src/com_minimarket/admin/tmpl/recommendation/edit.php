<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$pid=(int)$this->product->id;
?>
<form id="mm-recommendations-form" method="post"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=recommendation.save',false)); ?>">
 <div class="btn-toolbar d-flex gap-2 flex-wrap mb-3">
  <button type="submit" name="save_close" value="0" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
  <button type="submit" name="save_close" value="1" class="btn btn-success"><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=recommendations',false)); ?>" class="btn btn-outline-secondary"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <h2 class="h5 mb-3"><?php echo $this->escape((string)$this->product->title); ?>
  <span class="text-muted">#<?php echo $pid; ?></span></h2>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_RECOMMENDATIONS_HELP'); ?></p>
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-recommendations',['active'=>'similar']); ?>
 <?php foreach(['similar','together'] as $type): ?>
  <?php echo HTMLHelper::_('uitab.addTab','mm-recommendations',$type,
     Text::_($type==='similar'?'COM_MINIMARKET_RECOMMEND_SIMILAR':'COM_MINIMARKET_RECOMMEND_TOGETHER')); ?>
  <div class="card card-body mm-admin-panel" data-mm-recommend-section>
   <div class="mb-3">
    <label class="form-label" for="mm-rec-mode-<?php echo $type; ?>"><?php echo Text::_('COM_MINIMARKET_RECOMMEND_MODE'); ?></label>
    <select class="form-select" id="mm-rec-mode-<?php echo $type; ?>" name="<?php echo $type; ?>_mode" data-mm-recommend-mode>
     <?php foreach(['inherit','auto','manual','hybrid'] as $mode): ?>
      <option value="<?php echo $mode; ?>"<?php echo $this->modes[$type]===$mode?' selected':''; ?>>
       <?php echo Text::_('COM_MINIMARKET_RECOMMEND_MODE_'.strtoupper($mode)); ?>
      </option>
     <?php endforeach; ?>
    </select>
   </div>
   <div data-mm-recommend-targets>
    <?php
     $mmReference=['input'=>$type.'_ids','endpoint'=>'search','multiple'=>true,
      'selected'=>$this->{$type},
      'title'=>Text::_('COM_MINIMARKET_RECOMMEND_TARGETS'),
      'help'=>Text::_('COM_MINIMARKET_RECOMMEND_TARGET_HELP')];
     require dirname(__DIR__).'/shared/reference-picker.php';
    ?>
   </div>
  </div>
  <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endforeach; ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
<script>
(() => {
 const groups=document.querySelectorAll('[data-mm-recommend-section]');
 groups.forEach(group=>{
  const mode=group.querySelector('[data-mm-recommend-mode]');
  const targets=group.querySelector('[data-mm-recommend-targets]');
  if(!mode||!targets)return;
  const sync=()=>{targets.hidden=mode.value==='auto';};
  mode.addEventListener('change',sync);sync();
 });
})();
</script>
