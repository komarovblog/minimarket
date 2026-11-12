<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
if(!isset($mmColumnTable)||!preg_match('/^mm-[a-z0-9-]+-list$/D',(string)$mmColumnTable))
    throw new \InvalidArgumentException('Invalid MiniMarket table column target');
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseScript('com_minimarket.admin-columns',
        'media/com_minimarket/js/admin-columns.js',[],['defer'=>true]);
?>
<div class="d-flex justify-content-end mb-2" data-mm-columns-for="<?php echo htmlspecialchars($mmColumnTable,ENT_QUOTES,'UTF-8'); ?>"
 data-mm-columns-reset="<?php echo htmlspecialchars(Text::_('COM_MINIMARKET_LIST_COLUMNS_RESET'),ENT_QUOTES,'UTF-8'); ?>">
 <details class="mm-column-toggle">
  <summary class="btn btn-outline-secondary btn-sm"><span class="icon-columns" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_LIST_COLUMNS'); ?></summary>
  <div class="border rounded p-2 bg-body mt-1" data-mm-columns-options></div>
 </details>
</div>
