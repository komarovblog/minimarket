<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\DigitalFulfillment;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderHistory;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderLifecycle;

/** Operators may change order status, never line snapshots or prices. */
final class OrderController extends BaseController
{

    public function add(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&layout=create');
    }

    /** Create an order entered by phone. Never accept a posted price or SKU. */
    public function create(): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        if (!$app->getIdentity()->authorise('core.create','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $redirect='index.php?option=com_minimarket&view=order&layout=create';
        // Site service classes are installed by this same component; using
        // their validated order writer prevents two price/persistence paths.
        foreach (['CartState','CartStock','CheckoutData','CheckoutSettings',
            'CheckoutDelivery','OrderStorage'] as $name) {
            require_once JPATH_SITE.'/components/com_minimarket/src/Service/'.$name.'.php';
        }
        try {
            $params=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
            $contact=\MiniMarket\Component\Minimarket\Site\Service\CheckoutData::normalize([
                'name'=>$app->input->post->get('customer_name','','string'),
                'phone'=>$app->input->post->get('phone','','string'),
                'email'=>$app->input->post->get('email','','string'),
                'comment'=>$app->input->post->get('comment','','string'),
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $items=$app->input->post->get('items',[],'array');
            $quote=\MiniMarket\Component\Minimarket\Administrator\Service\ManualOrderCatalog::quote(
                $db,is_array($items)?$items:[],
                (int)$params->get('inventory_enabled',1)===1);
            $grams=0;$volume=0;
            foreach($quote['lines'] as $line){
                $grams+=(int)($line['shipping_weight_grams']??0)*(int)$line['quantity'];
                $volume+=(int)($line['shipping_volume_cm3']??0)*(int)$line['quantity'];
            }
            $delivery=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::quote($params,[
                'delivery_method'=>$app->input->post->get('delivery_method','','string'),
                'delivery_city'=>$app->input->post->get('delivery_city','','string'),
                'delivery_country'=>$app->input->post->get('delivery_country','RU','string'),
                'delivery_region'=>$app->input->post->get('delivery_region','','string'),
                'delivery_postcode'=>$app->input->post->get('delivery_postcode','','string'),
                'delivery_address'=>$app->input->post->get('delivery_address','','string'),
                'payment_method'=>$app->input->post->get('payment_method','','string'),
            ],(int)$quote['total_cents'],$grams,$volume);
            // A manually selected Joomla account is linked only by its
            // exact verified ID, never by comparing customer contacts.
            $userId=$app->input->post->getInt('user_id',0);
            if($userId<0)throw new \DomainException('COM_MINIMARKET_ORDER_USER_INVALID');
            if($userId>0){
                $query=$db->getQuery(true)->select('id')
                    ->from($db->quoteName('#__users'))
                    ->where('id='.(int)$userId)->where('block=0');
                $db->setQuery($query);
                if((int)$db->loadResult()!==$userId)
                    throw new \DomainException('COM_MINIMARKET_ORDER_USER_INVALID');
            }
            $id=\MiniMarket\Component\Minimarket\Site\Service\OrderStorage::create(
                $db,$contact,$quote,bin2hex(random_bytes(16)),$userId,$delivery,'manual');
            $app->enqueueMessage(Text::_('COM_MINIMARKET_MANUAL_ORDER_CREATED'));
            $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
        } catch (\DomainException $e) {
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
            $this->setRedirect($redirect);
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CHECKOUT_SAVE_ERROR'),'error');
            $this->setRedirect($redirect);
        }
    }


    /** Manual payment verification only; never claim an external gateway charge. */
    public function payment(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $action=$app->input->post->getCmd('payment_status','');
        if($id<1||!in_array($action,['paid','refunded'],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_STATUS_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $rows=[];$email='';$digitalOnly=false;$buyerUserId=0;
        try{
            // Site file helper shares the same private asset location.
            require_once JPATH_SITE.'/components/com_minimarket/src/Service/DigitalAsset.php';
            require_once JPATH_SITE.'/components/com_minimarket/src/Service/DigitalLicensePool.php';
            $db->transactionStart();
            $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.(int)$id.' AND deleted_at IS NULL FOR UPDATE');
            $order=$db->loadAssoc();
            if(!$order||$order['status']==='cancelled')throw new \DomainException('COM_MINIMARKET_PAYMENT_INVALID');
            $before=$order['payment_status'];
            if($action==='paid'){
                if(!in_array($before,['unpaid','paid'],true))
                    throw new \DomainException('COM_MINIMARKET_PAYMENT_INVALID');
                $rows=DigitalFulfillment::prepare($db,$id);
                // A consciously skipped buyer/recipient step can leave no email.
                // Payment may still be confirmed independently; digital delivery
                // remains pending and no mail is sent until an address is known.
                $email=(string)($order['digital_recipient_email']?:$order['email']);
                $digitalOnly=$order['delivery_method']==='digital';
                $buyerUserId=(int)$order['user_id'];
                $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
                    ." SET payment_status='paid', paid_at=COALESCE(paid_at,UTC_TIMESTAMP()) WHERE id=".(int)$id);
                $db->execute();
            }else{
                if($before!=='paid')throw new \DomainException('COM_MINIMARKET_PAYMENT_INVALID');
                $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
                    ." SET payment_status='refunded' WHERE id=".(int)$id);
                $db->execute();
            }
            if($before!==$action){
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_events'))
                    ->columns($db->quoteName(['order_id','actor_user_id','event_type','old_value','new_value','created_at']))
                    ->values(implode(',',[(string)$id,(string)(int)$app->getIdentity()->id,
                        $db->quote('payment'),$db->quote($before),$db->quote($action),'UTC_TIMESTAMP()']));
                $db->setQuery($q);$db->execute();
            }
            OrderLifecycle::refresh($db,$id,(int)$app->getIdentity()->id);
            $db->transactionCommit();
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_($e instanceof \DomainException?$e->getMessage():'COM_MINIMARKET_PAYMENT_INVALID'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
            return;
        }
        if($action==='paid' && $rows){
            if(DigitalFulfillment::send($db,$id,$rows,$email,$buyerUserId)){
                // Digital-only orders complete after confirmed payment and
                // successful delivery email. Mixed orders await physical shipping.
                if($digitalOnly && DigitalFulfillment::allAutomatic($rows)){
                    $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
                        ." SET fulfillment_status='delivered', fulfilled_at=UTC_TIMESTAMP(), status='completed'"
                        ." WHERE id=".(int)$id." AND payment_status='paid' AND status<>'cancelled'");
                    $db->execute();
                }
                $app->enqueueMessage(Text::_('COM_MINIMARKET_DIGITAL_SENT'));
            }else{
                $app->enqueueMessage(Text::_('COM_MINIMARKET_DIGITAL_SEND_FAILED'),'warning');
            }
        }else{
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PAYMENT_STATUS_SAVED'));
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }

    /** Operator-supplied access instructions. Cloud adapters are not active. */
    public function digitalIssue():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $itemId=$app->input->post->getInt('item_id',0);
        $message=trim(strip_tags($app->input->post->getString('digital_message','')));
        if($id<1||$itemId<1||mb_strlen($message)<3||mb_strlen($message)>2000
            ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$message))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_DIGITAL_MANUAL_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.$id.' AND deleted_at IS NULL FOR UPDATE');
            $order=$db->loadAssoc();
            if(!$order||$order['status']==='cancelled'||$order['payment_status']!=='paid')
                throw new \DomainException('COM_MINIMARKET_DIGITAL_MANUAL_INVALID');
            $q=$db->getQuery(true)->select('*')
                ->from($db->quoteName('#__minimarket_order_items'))
                ->where('id='.$itemId)->where('order_id='.$id);
            $db->setQuery($q);
            $item=$db->loadAssoc();
            if(!$item||$item['delivery_type']!=='digital'
                ||!in_array($item['digital_delivery_mode'],['manual','cloud'],true)
                ||$item['digital_fulfilled_at']!==null)
                throw new \DomainException('COM_MINIMARKET_DIGITAL_MANUAL_INVALID');
            $email=(string)($order['digital_recipient_email']?:$order['email']);
            $subject=Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_TITLE',$id);
            $body=Text::sprintf('COM_MINIMARKET_DIGITAL_MANUAL_MAIL_BODY',(string)$item['title'],$message);
            if(!DigitalFulfillment::mail($email,$subject,$body))
                throw new \DomainException('COM_MINIMARKET_DIGITAL_SEND_FAILED');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_order_items'))
                ->set('digital_fulfilled_at=UTC_TIMESTAMP()')
                ->set('digital_manual_message='.$db->quote($message))
                ->where('id='.$itemId)->where('order_id='.$id);
            $db->setQuery($q);$db->execute();
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_events'))
                ->columns($db->quoteName(['order_id','actor_user_id','event_type','old_value','new_value','created_at']))
                ->values(implode(',',[(string)$id,(string)(int)$app->getIdentity()->id,
                    $db->quote('digital_issued'),$db->quote('pending'),$db->quote('sent'),'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            if($order['delivery_method']==='digital'){
                $q=$db->getQuery(true)->select('COUNT(*)')
                    ->from($db->quoteName('#__minimarket_order_items'))
                    ->where('order_id='.$id)
                    ->where("delivery_type='digital'")
                    ->where("digital_delivery_mode IN ('manual','cloud')")
                    ->where('digital_fulfilled_at IS NULL');
                $db->setQuery($q);
                if((int)$db->loadResult()===0){
                    $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
                        ->set("fulfillment_status='delivered'")
                        ->set('fulfilled_at=UTC_TIMESTAMP()')->set("status='completed'")
                        ->where('id='.$id)->where("payment_status='paid'");
                    $db->setQuery($q);$db->execute();
                }
            }
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DIGITAL_MANUAL_SENT'));
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_($e instanceof \DomainException
                ?$e->getMessage():'COM_MINIMARKET_DIGITAL_MANUAL_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }

    public function fulfillment(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $state=$app->input->post->getCmd('fulfillment_status','');
        if($id<1||!in_array($state,['pending','shipped','delivered'],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_STATUS_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try{
            $db->setQuery('SELECT fulfillment_status,delivery_method,status FROM '
                .$db->quoteName('#__minimarket_orders').' WHERE id='.(int)$id
                .' AND deleted_at IS NULL FOR UPDATE');
            $row=$db->loadAssoc();
            if(!$row||$row['status']==='cancelled'
                ||$row['delivery_method']!=='pickup')
                throw new \DomainException('COM_MINIMARKET_PAYMENT_INVALID');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
                ->set('fulfillment_status='.$db->quote($state))
                ->set('fulfilled_at='.($state==='delivered'?'UTC_TIMESTAMP()':'NULL'))
                ->where('id='.(int)$id);
            $db->setQuery($q);$db->execute();
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_events'))
                ->columns($db->quoteName(['order_id','actor_user_id','event_type','old_value','new_value','created_at']))
                ->values(implode(',',[(string)$id,(string)(int)$app->getIdentity()->id,
                    $db->quote('fulfillment'),$db->quote($row['fulfillment_status']),
                    $db->quote($state),'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            OrderLifecycle::refresh($db,$id,(int)$app->getIdentity()->id);
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PAYMENT_STATUS_SAVED'));
        }catch(\Throwable){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PAYMENT_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }

    public function trash(): void { $this->changeTrash(true); }
    public function restore(): void { $this->changeTrash(false); }

    /** Reversible removal: never delete financial snapshots or live orders. */
    private function changeTrash(bool $remove): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if (!$app->getIdentity()->authorise('core.delete','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        if ($id<1)throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_TRASH_DENIED'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $db->setQuery('SELECT status,deleted_at FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.(int)$id.' FOR UPDATE');
            $before=$db->loadAssoc();
            if(!$before||($remove
                ?($before['status']!=='cancelled'||$before['deleted_at']!==null)
                :($before['deleted_at']===null)))
                throw new \DomainException('COM_MINIMARKET_ORDER_TRASH_DENIED');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
                ->set('deleted_at='.($remove?'UTC_TIMESTAMP()':'NULL'))
                ->where('id='.(int)$id);
            $db->setQuery($q);$db->execute();
            OrderHistory::record($db,$id,(int)$app->getIdentity()->id,
                'trash',$remove?'active':'trashed',$remove?'trashed':'restored');
            $db->transactionCommit();
        }catch(\Throwable $e){
            $db->transactionRollback();
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_TRASH_DENIED'),400);
        }
        $app->enqueueMessage(Text::_($remove?'COM_MINIMARKET_ORDER_TRASHED':'COM_MINIMARKET_ORDER_RESTORED'));
        $this->setRedirect('index.php?option=com_minimarket&view=orders&filter_trash='.($remove?'1':'0'));
    }

    /** Persist gateway metadata only; never mark a real external payment as paid. */
    public function paymentReference(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $reference=trim($app->input->post->getString('payment_reference',''));
        $url=trim($app->input->post->getString('payment_url',''));
        $parts=$url!==''?parse_url($url):[];
        if($id<1||mb_strlen($reference)>128||mb_strlen($url)>2048
            ||preg_match('/[\x00-\x1f<>]/u',$reference.$url)
            ||($url!==''&&(!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https'
                ||empty($parts['host'])||isset($parts['user'])||isset($parts['pass']))))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_PAYMENT_REFERENCE_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $db->setQuery('SELECT payment_reference,payment_url FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.$id.' AND deleted_at IS NULL FOR UPDATE');
            $before=$db->loadAssoc();
            if(!$before)throw new \DomainException('COM_MINIMARKET_ORDER_PAYMENT_REFERENCE_INVALID');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
                ->set($db->quoteName('payment_reference').'='.$db->quote($reference))
                ->set($db->quoteName('payment_url').'='.$db->quote($url))
                ->where('id='.$id);
            $db->setQuery($q);$db->execute();
            if($before['payment_reference']!==$reference||$before['payment_url']!==$url)
                OrderHistory::record($db,$id,(int)$app->getIdentity()->id,'payment_reference',
                    (string)$before['payment_reference'],$reference);
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PAYMENT_STATUS_SAVED'));
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_ORDER_PAYMENT_REFERENCE_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }

    public function status(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $status=$app->input->post->getCmd('status','');
        if($id<1||!in_array($status,['new','confirmed','completed','cancelled'],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_STATUS_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try{
            $db->setQuery('SELECT id,status,payment_status,fulfillment_status'
                .' FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.$id.' AND deleted_at IS NULL FOR UPDATE');
            $before=$db->loadAssoc();
            if(!$before)throw new \DomainException('COM_MINIMARKET_ORDER_STATUS_INVALID');
            OrderLifecycle::validateManual($db,$before,$status);
            if((string)$before['status']!==$status){
                $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
                    .' SET status='.$db->quote($status).' WHERE id='.$id);
                $db->execute();
                OrderHistory::record($db,$id,(int)$app->getIdentity()->id,
                    'status',(string)$before['status'],$status);
            }
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_ORDER_STATUS_SAVED'));
        }catch(\Throwable $e){
            $db->transactionRollback();
            $key=$e instanceof \DomainException?$e->getMessage():'COM_MINIMARKET_ORDER_STATUS_INVALID';
            $app->enqueueMessage(Text::_($key),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }

}
