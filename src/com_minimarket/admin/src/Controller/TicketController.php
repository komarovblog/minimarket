<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
final class TicketController extends BaseController
{
    private function check(string $permission):void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise($permission,'com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }
    private static function text(string $value,int $min,int $max):string
    {
        $value=trim(strip_tags($value));
        if(mb_strlen($value)<$min||mb_strlen($value)>$max
            ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$value))
            throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
        return $value;
    }

    public function create():void
    {
        $this->check('core.create');
        $app=Factory::getApplication();$post=$app->input->post;
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try{
            $subject=self::text($post->getString('subject',''),3,180);
            $message=self::text($post->getString('message',''),3,4000);
            $userId=$post->getInt('user_id',0);
            $orderId=$post->getInt('order_id',0);
            if($userId<0||$orderId<0)throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
            $db->transactionStart();
            try{
                if($orderId>0){
                    $db->setQuery('SELECT user_id FROM '.$db->quoteName('#__minimarket_orders')
                        .' WHERE id='.$orderId.' AND deleted_at IS NULL FOR UPDATE');
                    $row=$db->loadAssoc();
                    if(!$row||($userId>0&&(int)$row['user_id']!==$userId))
                        throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
                    $userId=(int)$row['user_id'];
                }
                if($userId>0){
                    $db->setQuery('SELECT id FROM '.$db->quoteName('#__users')
                        .' WHERE id='.$userId);
                    if((int)$db->loadResult()!==$userId)
                        throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
                }
                $actor=(int)$app->getIdentity()->id;
                $table=$db->quoteName('#__minimarket_support_tickets');
                $q=$db->getQuery(true)->insert($table)
                    ->columns($db->quoteName(['order_id','user_id','subject','status','created_by','created_at']))
                    ->values(implode(',',[$orderId>0?(string)$orderId:'NULL',(string)$userId,
                        $db->quote($subject),$db->quote('new'),(string)$actor,'UTC_TIMESTAMP()']));
                $db->setQuery($q);$db->execute();
                $id=(int)$db->insertid();
                if($id<1)throw new \RuntimeException('Ticket insert failed');
                $this->recordMessage($db,$id,$message,$actor);
                $db->transactionCommit();
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            $app->enqueueMessage(Text::_('COM_MINIMARKET_TICKET_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view=ticket&id='.$id);
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=ticket&layout=create');
        }
    }

    public function update():void
    {
        $this->check('core.edit');
        $app=Factory::getApplication();$post=$app->input->post;
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $id=$post->getInt('id',0);
        try {
            $status=$post->getCmd('status','');
            $message=trim($post->getString('message',''));
            if($id<1||!in_array($status,['new','in_progress','closed'],true))
                throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
            $message=$message===''?'':self::text($message,3,4000);
            $db->transactionStart();
            try{
                $db->setQuery('SELECT status FROM '.$db->quoteName('#__minimarket_support_tickets')
                    .' WHERE id='.$id.' FOR UPDATE');
                $row=$db->loadAssoc();
                if(!$row)throw new \DomainException('COM_MINIMARKET_TICKET_INVALID');
                if($status!==(string)$row['status']
                    &&!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
                    throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_support_tickets'))
                    ->set('status='.$db->quote($status))
                    ->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
                $db->setQuery($q);$db->execute();
                if($message!=='')
                    $this->recordMessage($db,$id,$message,(int)$app->getIdentity()->id);
                $db->transactionCommit();
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            $app->enqueueMessage(Text::_('COM_MINIMARKET_TICKET_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=ticket&id='.$id);
    }

    private function recordMessage(DatabaseInterface $db,int $id,string $body,int $author):void
    {
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_support_messages'))
            ->columns($db->quoteName(['ticket_id','body','internal','author_user_id','created_at']))
            ->values(implode(',',[(string)$id,$db->quote($body),'1',(string)$author,'UTC_TIMESTAMP()']));
        $db->setQuery($q);$db->execute();
    }
}
