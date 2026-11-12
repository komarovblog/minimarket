<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;

$e=static fn($value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$providerOptions=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
foreach(FeedConfig::PROVIDERS as $key=>$label)$providerOptions[$key]=Text::_($label);
$mmSearch=[
 'view'=>'feeds','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_FEEDS_TITLE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
 ['name'=>'filter_provider','label'=>Text::_('COM_MINIMARKET_FEED_PROVIDER'),
  'value'=>$this->providerFilter,'options'=>$providerOptions],
 ['name'=>'filter_status','label'=>Text::_('JSTATUS'),'value'=>$this->statusFilter,'default'=>'all',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),'published'=>Text::_('JPUBLISHED'),
   'unpublished'=>Text::_('JUNPUBLISHED')]]]
];
$href='index.php?option=com_minimarket&view=feeds&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->statusFilter,'filter_provider'=>$this->providerFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<form id="adminForm" name="adminForm" class="mm-list-form" method="post"
        action="<?php echo $e(Route::_('index.php?option=com_minimarket&view=feeds',false)); ?>">
   <input type="hidden" name="task" value="">
   <input type="hidden" name="boxchecked" value="0">
   <?php echo HTMLHelper::_('form.token'); ?>
   <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <div id="j-main-container" class="j-main-container">

   <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-feeds-list" class="table table-striped mm-admin-list">
    <thead><tr>
     <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
     <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER'); ?></th>
     <th><?php echo Text::_('JSTATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FEED_URL'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FEED_ACTIONS'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->feeds as $i=>$feed):
       $url=\Joomla\CMS\Uri\Uri::root().'index.php?option=com_minimarket&task=feed.xml&id='
          .(int)$feed['id'].'&key='.rawurlencode((string)$feed['access_key']);
       $edit=Route::_('index.php?option=com_minimarket&view=feed&id='.(int)$feed['id'],false);
    ?>
     <tr>
      <td class="text-center"><?php echo HTMLHelper::_('grid.id',$i,(int)$feed['id']); ?></td>
     <td data-label="<?php echo Text::_('JGLOBAL_TITLE'); ?>"><a href="<?php echo $e($edit); ?>"><?php echo $e($feed['title']); ?></a></td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER'); ?>"><?php echo Text::_(FeedConfig::PROVIDERS[$feed['provider']]??'COM_MINIMARKET_FEED_INVALID'); ?></td>
      <td data-label="<?php echo Text::_('JSTATUS'); ?>"><span class="badge bg-<?php echo (int)$feed['state']===1?'success':'secondary'; ?>"><?php echo (int)$feed['state']===1?Text::_('JPUBLISHED'):Text::_('JUNPUBLISHED'); ?></span></td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_FEED_URL'); ?>">
       <?php if((int)$feed['state']===1): ?>
        <input class="form-control form-control-sm" type="text" readonly aria-label="<?php echo $e(Text::_('COM_MINIMARKET_FEED_URL')); ?>" value="<?php echo $e($url); ?>" onclick="this.select()">
       <?php else: ?>—<?php endif; ?>
      </td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_FEED_ACTIONS'); ?>">
       <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($edit); ?>"><?php echo Text::_('JEDIT'); ?></a>
        <?php if((int)$feed['state']===1): ?>
         <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_MINIMARKET_FEED_VIEW_XML'); ?></a>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-danger" type="submit"
                form="mm-feed-delete-<?php echo (int)$feed['id']; ?>"><?php echo Text::_('JACTION_DELETE'); ?></button>
       </div>
      </td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
<?php if (!$this->feeds): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_FEED_EMPTY'); ?></div><?php endif; ?>
  </div>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
   </div>
</form>
  <?php foreach($this->feeds as $feed): ?>
   <form id="mm-feed-delete-<?php echo (int)$feed['id']; ?>" method="post"
         action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=feed.delete',false)); ?>"
         onsubmit="return confirm(this.dataset.confirm)"
         data-confirm="<?php echo $e(Text::_('COM_MINIMARKET_FEED_CONFIRM_DELETE')); ?>">
     <input type="hidden" name="id" value="<?php echo (int)$feed['id']; ?>">
     <?php echo HTMLHelper::_('form.token'); ?>
   </form>
  <?php endforeach; ?>
  <p class="text-muted small mt-3"><?php echo Text::_('COM_MINIMARKET_FEED_LIMITATIONS'); ?></p>
