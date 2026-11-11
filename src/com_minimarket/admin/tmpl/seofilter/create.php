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
$groups=[];
foreach($this->facets as $choice){
 $id=(int)$choice->attribute_id;
 $groups[$id]['title']=$choice->attribute_title;
 $groups[$id]['choices'][]=$choice;
}
$seoEditor=\Joomla\CMS\Editor\Editor::getInstance((string)Factory::getApplication()->get('editor','tinymce'));
?>
<form method="post" id="mm-seo-editor" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=seofilter.save',false)); ?>">
 <div class="d-flex gap-2 flex-wrap mb-3">
  <button type="submit" class="btn btn-success" name="save_close" value="0"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
  <button type="submit" class="btn btn-success" name="save_close" value="1"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVEANDCLOSE'); ?></button>
  <a class="btn btn-danger" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilters',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="id" value="<?php echo (int)($p->id??0); ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <section class="mm-admin-panel card card-body">
  <ul class="nav nav-tabs" role="tablist">
   <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#mm-seo-main"><?php echo Text::_('COM_MINIMARKET_SEO_TAB_MAIN'); ?></button></li>
   <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#mm-seo-filters"><?php echo Text::_('COM_MINIMARKET_SEO_TAB_FILTERS'); ?></button></li>
   <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#mm-seo-meta"><?php echo Text::_('COM_MINIMARKET_SEO_TAB_META'); ?></button></li>
  </ul>
  <div class="tab-content pt-3">
   <div id="mm-seo-main" class="tab-pane fade show active">
    <div class="row g-3">
     <div class="col-md-6"><label class="form-label" for="mm-seo-title"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?> *</label>
      <input id="mm-seo-title" class="form-control" name="title" maxlength="255" required value="<?php echo $this->escape($get('title')); ?>"></div>
     <div class="col-md-6"><label class="form-label" for="mm-seo-alias"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?> *</label>
      <input id="mm-seo-alias" class="form-control" name="alias" maxlength="190" pattern="[a-z0-9][a-z0-9-]*" required value="<?php echo $this->escape($get('alias')); ?>"></div>
     <div class="col-md-6"><label class="form-label" for="mm-seo-category"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'); ?> *</label>
      <select class="form-select" id="mm-seo-category" name="category_id" required>
       <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
       <?php foreach($this->categories as $cat): ?><option value="<?php echo (int)$cat->id; ?>"<?php echo (int)$get('category_id')===(int)$cat->id?' selected':''; ?>><?php echo $this->escape($cat->title); ?> (#<?php echo (int)$cat->id; ?>)</option><?php endforeach; ?>
      </select></div>
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
   <div id="mm-seo-filters" class="tab-pane fade">
    <div class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_SEO_FILTER_CHOICE_HELP'); ?></div>
    <?php foreach($groups as $aid=>$group): ?>
     <fieldset class="border rounded p-3 mb-3">
      <legend class="float-none w-auto px-2 fs-6"><?php echo $this->escape($group['title']); ?></legend>
      <div class="row g-2">
       <?php foreach($group['choices'] as $choice): $token='v:'.(int)$choice->value_id;
       $checked=in_array($token,(array)($saved['a'.$aid]??[]),true); ?>
        <label class="col-12 col-md-6 col-lg-4 form-check ms-2">
         <input class="form-check-input" type="checkbox" data-mm-seo-facet="a<?php echo $aid; ?>" value="<?php echo $this->escape($token); ?>"<?php echo $checked?' checked':''; ?>>
         <span class="form-check-label"><?php echo $this->escape($choice->value_title); ?></span>
        </label>
       <?php endforeach; ?>
      </div>
     </fieldset>
    <?php endforeach; ?>
    <?php
     $optionGroups=[];
     foreach($this->skuChoices as $choice){
      $id=(int)$choice->option_id;
      $optionGroups[$id]['title']=$choice->option_title;
      $optionGroups[$id]['values'][]=$choice;
     }
    ?>
    <?php foreach($optionGroups as $oid=>$group): ?>
     <fieldset class="border rounded p-3 mb-3">
      <legend class="float-none w-auto px-2 fs-6"><?php echo Text::_('COM_MINIMARKET_SEO_SKU_OPTION'); ?>: <?php echo $this->escape($group['title']); ?></legend>
      <div class="row g-2">
       <?php foreach($group['values'] as $choice): $token='v:'.(int)$choice->value_id; ?>
        <label class="col-12 col-md-6 col-lg-4 form-check ms-2">
         <input type="checkbox" class="form-check-input" data-mm-seo-facet="s<?php echo $oid; ?>"
                value="<?php echo $token; ?>"<?php echo in_array($token,(array)($saved['s'.$oid]??[]),true)?' checked':''; ?>>
         <span class="form-check-label"><?php echo $this->escape($choice->value_title); ?></span>
        </label>
       <?php endforeach; ?>
      </div>
     </fieldset>
    <?php endforeach; ?>
    <fieldset class="border rounded p-3 mb-3">
     <legend class="float-none w-auto px-2 fs-6"><?php echo Text::_('COM_MINIMARKET_SEO_SYSTEM_FILTERS'); ?></legend>
     <div class="row g-3">
      <div class="col-md-4"><label for="mm-seo-price-min" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_MIN_PRICE'); ?></label>
       <input id="mm-seo-price-min" data-mm-seo-range="price" data-mm-bound="min" class="form-control" type="number"
              min="0" step="0.01" value="<?php echo $this->escape((string)($saved['price']['min']??'')); ?>"></div>
      <div class="col-md-4"><label for="mm-seo-price-max" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_MAX_PRICE'); ?></label>
       <input id="mm-seo-price-max" data-mm-seo-range="price" data-mm-bound="max" class="form-control" type="number"
              min="0" step="0.01" value="<?php echo $this->escape((string)($saved['price']['max']??'')); ?>"></div>
      <div class="col-md-4"><label for="mm-seo-stock" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEO_STOCK'); ?></label>
       <select id="mm-seo-stock" class="form-select" data-mm-seo-stock>
        <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
        <option value="b:1"<?php echo in_array('b:1',(array)($saved['stock']??[]),true)?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_SEO_IN_STOCK'); ?></option>
        <option value="b:0"<?php echo in_array('b:0',(array)($saved['stock']??[]),true)?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_SEO_OUT_OF_STOCK'); ?></option>
       </select></div>
      <div class="col-12"><label for="mm-seo-sku" class="form-label"><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></label>
       <input id="mm-seo-sku" data-mm-seo-sku class="form-control" maxlength="100"
              value="<?php echo $this->escape((string)($saved['sku']??'')); ?>"></div>
     </div>
    </fieldset>
    <div class="alert alert-secondary"><?php echo Text::_('COM_MINIMARKET_SEO_FILTER_CATEGORY_NOTICE'); ?></div>
    <input type="hidden" id="mm-seo-query" name="filter_query" value="">
   </div>
   <div id="mm-seo-meta" class="tab-pane fade">
    <div class="row g-3">
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
  </div>
 </section>
</form>
<script>
(() => {
 const form=document.getElementById('mm-seo-editor'),hidden=document.getElementById('mm-seo-query');
 const original=<?php echo json_encode($saved,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?>;
 const build=()=>{
   const current=structuredClone(original),found={};
   for(const checkbox of form.querySelectorAll('[data-mm-seo-facet]')){
     const key=checkbox.dataset.mmSeoFacet;
     if(!found[key]){found[key]=[];delete current[key];}
     if(checkbox.checked)found[key].push(checkbox.value);
   }
   for(const [key,values] of Object.entries(found))if(values.length)current[key]=values;
   // Explicit system fields replace the previously stored system rules.
   delete current.price;delete current.stock;delete current.sku;
   const range={};
   for(const field of form.querySelectorAll('[data-mm-seo-range="price"]')){
     const v=field.value.trim();
     if(v!=='')range[field.dataset.mmBound]=v;
   }
   if(Object.keys(range).length)current.price=range;
   const stock=form.querySelector('[data-mm-seo-stock]')?.value||'';
   if(stock)current.stock=[stock];
   const sku=form.querySelector('[data-mm-seo-sku]')?.value.trim()||'';
   if(sku)current.sku=sku;
   const params=new URLSearchParams();
   for(const [key,values] of Object.entries(current)){
     if(!/^(?:a|s)[0-9]+$|^(?:price|stock|sku)$/.test(key))continue;
     if(Array.isArray(values))for(const v of values)params.append('mmf['+key+'][]',String(v));
     else if(values && typeof values==='object')
       for(const [sub,val] of Object.entries(values)){
         if(Array.isArray(val))for(const v of val)params.append('mmf['+key+']['+sub+'][]',String(v));
         else params.append('mmf['+key+']['+sub+']',String(val));
       }
     else if(values!==null)params.append('mmf['+key+']',String(values));
   }
   hidden.value=params.toString();
 };
 form.addEventListener('submit',build);
 build();
})();
</script>
