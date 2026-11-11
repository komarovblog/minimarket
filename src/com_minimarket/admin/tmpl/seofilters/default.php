<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$categoryOptions=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
foreach($this->categories as $category)$categoryOptions[(string)(int)$category->id]=(string)$category->title;
$mmSearch=[
 'view'=>'seofilters','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_SEOFILTER_TITLE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
 ['name'=>'filter_category','label'=>Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'),
  'value'=>$this->categoryFilter,'options'=>$categoryOptions],
 ['name'=>'filter_status','label'=>Text::_('JSTATUS'),
  'value'=>$this->statusFilter,'default'=>'all',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'published'=>Text::_('COM_MINIMARKET_SEOFILTER_PUBLISHED'),
   'unpublished'=>Text::_('COM_MINIMARKET_SEOFILTER_UNPUBLISHED')]]]
];
$href='index.php?option=com_minimarket&view=seofilters&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->statusFilter,'filter_category'=>$this->categoryFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<div class="mm-unified-list">
 <div id="j-main-container" class="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilters',false)); ?>"
        method="post" name="adminForm" id="adminForm" class="mm-list-form">
   <div class="table-responsive">
    <table id="mm-seofilters-list" class="table table-striped mm-admin-list">
     <thead><tr>
      <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
      <th>ID</th>
      <th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?></th>
      <th><?php echo Text::_('JSTATUS'); ?></th>
     </tr></thead>
     <tbody>
     <?php foreach($this->pages as $i=>$page): ?>
      <tr>
       <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>"><?php echo HTMLHelper::_('grid.id',$i,(int)$page->id); ?></td>
       <td data-label="ID"><?php echo (int)$page->id; ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?>"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilter&id='.(int)$page->id,false)); ?>"><?php echo $this->escape($page->title); ?></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'); ?>"><?php echo $this->escape($page->category_title); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?>">
        <a href="<?php echo $this->escape(Uri::root().'index.php?option=com_minimarket&view=seofilter&id='.(int)$page->id); ?>" target="_blank" rel="noopener noreferrer">/seo/<?php echo $this->escape($page->alias); ?>-<?php echo (int)$page->id; ?></a></td>
       <td data-label="<?php echo Text::_('JSTATUS'); ?>"><span class="badge bg-<?php echo (int)$page->state===1?'success':'secondary'; ?>"><?php echo Text::_((int)$page->state===1?'COM_MINIMARKET_SEOFILTER_PUBLISHED':'COM_MINIMARKET_SEOFILTER_UNPUBLISHED'); ?></span></td>
      </tr>
     <?php endforeach; ?>
     <?php if(!$this->pages): ?><tr><td colspan="6"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_EMPTY'); ?></td></tr><?php endif; ?>
     </tbody>
    </table>
   </div>
   <input type="hidden" name="boxchecked" value="0">
   <input type="hidden" name="task" value="">
   <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <?php if($this->total>$this->pageSize): ?>
   <nav class="mm-native-pagination">
    <?php if($this->offset>0): ?><a href="<?php echo $this->escape(Route::_($href.'&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>"><?php echo Text::_('JPREV'); ?></a><?php endif; ?>
    <span><?php echo $this->offset+1; ?>–<?php echo min($this->total,$this->offset+$this->pageSize); ?> / <?php echo $this->total; ?></span>
    <?php if($this->offset+$this->pageSize<$this->total): ?><a href="<?php echo $this->escape(Route::_($href.'&limitstart='.($this->offset+$this->pageSize),false)); ?>"><?php echo Text::_('JNEXT'); ?></a><?php endif; ?>
   </nav>
  <?php endif; ?>
 </div>
</div>
