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
    <div class="alert alert-secondary"><?php echo Text::_('COM_MINIMARKET_SEO_FILTER_CATEGORY_NOTICE'); ?></div>
    <input type="hidden" id="mm-seo-query" name="filter_query" value="">
   </div>
   <div id="mm-seo-meta" class="tab-pane fade">
    <div class="row g-3">
     <div class="col-12"><label for="mm-meta" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_META_DESC'); ?></label>
      <textarea id="mm-meta" name="meta_description" class="form-control" rows="3" maxlength="320"><?php echo $this->escape($get('meta_description')); ?></textarea></div>
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
