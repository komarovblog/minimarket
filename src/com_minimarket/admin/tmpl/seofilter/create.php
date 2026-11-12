<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$p=$this->page;
$get=static fn(string $key,string $default='')=>$p===null?$default:(string)($p->$key??$default);
$saved=$p?json_decode((string)$p->filters_json,true):[];
if(!is_array($saved))$saved=[];
$conditions=$p?json_decode((string)($p->conditions_json??''),true):[];
if(!is_array($conditions))$conditions=[];
$seoEditor=\Joomla\CMS\Editor\Editor::getInstance((string)Factory::getApplication()->get('editor','tinymce'));
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseScript('com_minimarket.seo-category-editor',
        'media/com_minimarket/js/seo-category-editor.js',[],['defer'=>true]);
?>
<form method="post" id="mm-seo-editor" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=seofilter.save',false)); ?>">
 <div class="d-flex gap-2 flex-wrap mb-3">
  <button type="submit" class="btn btn-success" name="save_close" value="0"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
  <button type="submit" class="btn btn-success" name="save_close" value="1"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  <a class="btn btn-danger" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilters',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="id" value="<?php echo (int)($p->id??0); ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-seofilter', ['active' => 'main']); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-seofilter', 'main', Text::_('COM_MINIMARKET_SEO_TAB_MAIN')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label class="form-label" for="mm-seo-name"><?php echo Text::_('COM_MINIMARKET_SEO_LANDING_NAME'); ?> *</label>
      <input id="mm-seo-name" class="form-control" name="name" maxlength="255" required
             value="<?php echo $this->escape($get('name',$get('title'))); ?>"></div>
     <div class="col-md-6"><label class="form-label" for="mm-seo-alias"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?> *</label>
      <input id="mm-seo-alias" class="form-control" name="alias" maxlength="190" pattern="[a-z0-9][a-z0-9-]*" required value="<?php echo $this->escape($get('alias')); ?>"></div>
     <div class="col-12"><label class="form-label" for="mm-seo-heading"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_HEADING'); ?> *</label>
      <input id="mm-seo-heading" class="form-control" name="heading" maxlength="255" required value="<?php echo $this->escape($get('heading')); ?>"></div>
     <div class="col-12 col-lg-6">
      <label class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_IMAGE'); ?></label>
      <?php echo $this->mediaForm->getInput('image'); ?>
     </div>
     <div class="col-12 col-lg-6"><label for="mm-seo-image-alt" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_IMAGE_ALT'); ?></label>
      <input id="mm-seo-image-alt" class="form-control" name="image_alt" maxlength="255"
             value="<?php echo $this->escape($get('image_alt')); ?>"></div>
     <div class="col-12"><label class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_INTRO'); ?></label>
      <?php echo $seoEditor->display('introtext',$get('introtext'),'100%','220','65','12',false); ?></div>
     <div class="col-12"><label class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_DESCRIPTION'); ?></label>
      <?php echo $seoEditor->display('description',$get('description'),'100%','350','65','18',false); ?></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-seofilter', 'filters', Text::_('COM_MINIMARKET_SEO_TAB_FILTERS')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="alert alert-info">
     <p><?php echo Text::_('COM_MINIMARKET_SEO_FILTER_CATEGORY_NOTICE'); ?></p>
     <p class="mb-0"><?php echo Text::_('COM_MINIMARKET_SEO_FILTER_CHOICE_HELP'); ?></p>
    </div>
     <div class="col-12">
      <?php
       $categorySelected=[];
       if((int)$get('category_id')>0){
        foreach($this->categories as $cat){
         if((int)$cat->id===(int)$get('category_id')){
          $categorySelected[(int)$cat->id]=$cat->title;
          break;
         }
        }
       }
       $mmReference=[
        'title'=>Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'),
        'input'=>'category_id','endpoint'=>'categories','multiple'=>false,
        'selected'=>$categorySelected,'placeholder'=>Text::_('COM_MINIMARKET_CATEGORY_FILTER_SEARCH')
       ];
       require dirname(__DIR__).'/shared/reference-picker.php';
      ?>
     </div>
    <div id="mm-seo-category-choices" data-mm-seo-choices-url="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=seofilter.choices&'.\Joomla\CMS\Session\Session::getFormToken().'=1',false)); ?>"
         data-mm-seo-saved="<?php echo $this->escape(json_encode($saved,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)); ?>"
         data-mm-seo-conditions="<?php echo $this->escape(json_encode($conditions,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)); ?>"
         data-mm-seo-unused="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_STATE_UNUSED')); ?>"
         data-mm-seo-any="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_STATE_ANY')); ?>"
         data-mm-seo-exact="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_STATE_EXACT')); ?>"
         data-mm-seo-exact-value="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_EXACT_VALUE')); ?>"
         data-mm-seo-legacy-warning="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_LEGACY_MULTIPLE')); ?>"
         data-mm-seo-needs-exact="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEOFILTER_NEEDS_EXACT')); ?>"
         data-mm-seo-original-category="<?php echo (int)$get('category_id'); ?>"
         data-mm-seo-choose="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_PICK_CATEGORY_FIRST')); ?>"
         data-mm-seo-none="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_NO_CATEGORY_CHOICES')); ?>"
         data-mm-seo-loading="<?php echo $this->escape(Text::_('COM_MINIMARKET_REF_LOADING')); ?>"
         data-mm-seo-error="<?php echo $this->escape(Text::_('COM_MINIMARKET_SEO_CHOICE_LOAD_FAILED')); ?>"
         aria-live="polite">
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SEO_PICK_CATEGORY_FIRST'); ?></p>
    </div>
    <fieldset class="border rounded p-3 mb-3">
     <legend class="float-none w-auto px-2 fs-6"><?php echo Text::_('COM_MINIMARKET_SEO_SYSTEM_FILTERS'); ?></legend>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SEO_RULES_EXPLAIN'); ?></p>
     <div class="row g-3">
      <?php foreach(['price','stock','sku'] as $systemKey):
          $label=Text::_($systemKey==='price'?'COM_MINIMARKET_FILTER_PRICE':
             ($systemKey==='stock'?'COM_MINIMARKET_SEO_STOCK':'COM_MINIMARKET_PRODUCT_SKU'));
      ?>
       <div class="col-12">
        <label class="form-label" for="mm-seo-state-<?php echo $systemKey; ?>"><?php echo $label; ?></label>
        <div class="row g-2 align-items-start">
         <div class="col-12 col-md-5">
          <select id="mm-seo-state-<?php echo $systemKey; ?>" class="form-select"
                  data-mm-seo-state="<?php echo $systemKey; ?>">
           <option value="unused"><?php echo Text::_('COM_MINIMARKET_SEO_STATE_UNUSED'); ?></option>
           <option value="any"><?php echo Text::_('COM_MINIMARKET_SEO_STATE_ANY'); ?></option>
           <option value="exact"><?php echo Text::_('COM_MINIMARKET_SEO_STATE_EXACT'); ?></option>
          </select>
         </div>
         <div class="col-12 col-md-7" data-mm-seo-details="<?php echo $systemKey; ?>" hidden>
          <?php if($systemKey==='price'): ?>
           <div class="row g-2">
            <div class="col-6">
             <label class="form-label" for="mm-seo-price-min"><?php echo Text::_('COM_MINIMARKET_SEO_MIN_PRICE'); ?></label>
             <input id="mm-seo-price-min" data-mm-seo-range="price" data-mm-bound="min"
                    class="form-control" type="number" min="0" step="0.01">
            </div>
            <div class="col-6">
             <label class="form-label" for="mm-seo-price-max"><?php echo Text::_('COM_MINIMARKET_SEO_MAX_PRICE'); ?></label>
             <input id="mm-seo-price-max" data-mm-seo-range="price" data-mm-bound="max"
                    class="form-control" type="number" min="0" step="0.01">
            </div>
           </div>
          <?php elseif($systemKey==='stock'): ?>
           <select class="form-select" data-mm-seo-choice="stock"
                   aria-label="<?php echo $this->escape($label); ?>">
            <option value="b:1"><?php echo Text::_('COM_MINIMARKET_SEO_IN_STOCK'); ?></option>
            <option value="b:0"><?php echo Text::_('COM_MINIMARKET_SEO_OUT_OF_STOCK'); ?></option>
           </select>
          <?php else: ?>
           <input class="form-control" data-mm-seo-text="sku" maxlength="100"
                  aria-label="<?php echo $this->escape($label); ?>">
          <?php endif; ?>
         </div>
        </div>
       </div>
      <?php endforeach; ?>
     </div>
    </fieldset>
    <div class="alert alert-warning" id="mm-seo-rule-warning" role="alert" hidden></div>
    <input type="hidden" id="mm-seo-conditions" name="conditions_json" value="">
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-seofilter', 'meta', Text::_('COM_MINIMARKET_SEO_TAB_META')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-12"><label class="form-label" for="mm-seo-title"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?> *</label>
      <input id="mm-seo-title" class="form-control" name="title" maxlength="255" required value="<?php echo $this->escape($get('title')); ?>"></div>
     <div class="col-12"><label for="mm-meta" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_META_DESC'); ?></label>
      <textarea id="mm-meta" name="meta_description" class="form-control" rows="3" maxlength="320"><?php echo $this->escape($get('meta_description')); ?></textarea></div>
     <div class="col-12"><label for="mm-og-title" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_OG_TITLE'); ?></label>
      <input id="mm-og-title" class="form-control" name="og_title" maxlength="255"
             value="<?php echo $this->escape($get('og_title')); ?>"></div>
     <div class="col-12"><label for="mm-og-desc" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_OG_DESCRIPTION'); ?></label>
      <textarea id="mm-og-desc" name="og_description" class="form-control" rows="2" maxlength="320"><?php echo $this->escape($get('og_description')); ?></textarea></div>
     <div class="col-12"><label class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_OG_IMAGE'); ?></label>
      <?php echo $this->mediaForm->getInput('og_image'); ?></div>
     <div class="col-md-6"><label for="mm-robots" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ROBOTS'); ?></label>
      <select id="mm-robots" class="form-select" name="meta_robots">
       <?php foreach(['index,follow','noindex,follow','noindex,nofollow'] as $robots): ?><option value="<?php echo $robots; ?>"<?php echo $get('meta_robots','index,follow')===$robots?' selected':''; ?>><?php echo $robots; ?></option><?php endforeach; ?>
      </select></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-seofilter', 'publication', Text::_('COM_MINIMARKET_CATEGORY_TAB_PUBLISHING')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-12">
      <label class="form-label"><?php echo Text::_('JSTATUS'); ?></label>
      <?php if($this->canPublish): ?>
       <select name="state" class="form-select" style="max-width:25rem">
        <option value="0"<?php echo (int)$get('state')===0?' selected':''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
        <option value="1"<?php echo (int)$get('state')===1?' selected':''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
       </select>
      <?php else: ?>
       <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SEO_PUBLISH_PERMISSION'); ?></p>
      <?php endif; ?>
     </div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
