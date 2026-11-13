<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmSearch=['view'=>'messagetemplates','query'=>$this->search,'sort'=>$this->sort,
 'limit'=>$this->pageSize,'searchLabel'=>Text::_('COM_MINIMARKET_MESSAGE_TEMPLATES_TITLE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
 'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[['name'=>'filter_audience',
 'label'=>Text::_('COM_MINIMARKET_TEMPLATE_AUDIENCE'),'value'=>$this->audience,
 'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
 'customer'=>Text::_('COM_MINIMARKET_TEMPLATE_CUSTOMER'),
 'admin'=>Text::_('COM_MINIMARKET_TEMPLATE_ADMIN'),
 'marketing'=>Text::_('COM_MINIMARKET_TEMPLATE_MARKETING')]]];
?>
<form method="post" id="adminForm" name="adminForm"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=messagetemplates',false)); ?>">
 <div id="j-main-container">
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_TEMPLATES_HELP'); ?></p>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-message-templates';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-message-templates" class="table table-striped mm-admin-list">
    <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_TEMPLATE_AUDIENCE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_EVENT'); ?></th>
     <th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
    <tbody><?php foreach($this->items as $template): ?><tr>
     <td>#<?php echo (int)$template->id; ?></td>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=messagetemplate&id='.(int)$template->id,false)); ?>">
      <?php echo $this->escape((string)$template->title); ?></a></td>
     <td><?php echo Text::_('COM_MINIMARKET_TEMPLATE_'.strtoupper((string)$template->audience)); ?></td>
     <td><?php echo $this->escape((string)$template->event_code); ?></td>
     <td><?php echo Text::_((int)$template->state===1?'JPUBLISHED':'JUNPUBLISHED'); ?></td>
    </tr><?php endforeach; ?></tbody>
   </table>
  </div>
  <?php if(!$this->items): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_TEMPLATES_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
