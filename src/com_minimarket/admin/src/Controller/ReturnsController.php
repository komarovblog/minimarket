<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderHistory;

/** Native checked-row actions; all decisions remain operator-only, never automatic refunds. */
final class ReturnsController extends BaseController
{
    public function approve():void {$this->change('approved',['new']);}
    public function reject():void {$this->change('rejected',['new','approved']);}
    public function receive():void {$this->change('received',['approved']);}

    private function change(string $target,array $eligible):void
    {
        $app=Factory::getApplication();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try{
            $ids=ListBulkActions::selected('core.edit.state');
            $db->transactionStart();
            try {
                $q=$db->getQuery(true)->select(['id','order_id','status'])
                    ->from($db->quoteName('#__minimarket_returns'))
                    ->where('id IN ('.implode(',',$ids).')');
                $db->setQuery((string)$q.' FOR UPDATE');
                $rows=(array)$db->loadAssocList();
                if(count($rows)!==count($ids))
                    throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
                foreach($rows as $row)
                    if(!in_array($row['status'],$eligible,true))
                        throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_returns'))
                    ->set('status='.$db->quote($target))
                    ->set('updated_at=UTC_TIMESTAMP()')
                    ->where('id IN ('.implode(',',$ids).')');
                $db->setQuery($q);$db->execute();
                foreach($rows as $row)
                    OrderHistory::record($db,(int)$row['order_id'],(int)$app->getIdentity()->id,
                        'return_status',(string)$row['status'],$target);
                $db->transactionCommit();
                $app->enqueueMessage(Text::sprintf('COM_MINIMARKET_LIST_BULK_DONE',count($rows)));
            }catch(\Throwable $e){
                $db->transactionRollback();throw $e;
            }
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=returns');
    }
}
