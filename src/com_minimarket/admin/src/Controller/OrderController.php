<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

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
            $delivery=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::quote($params,[
                'delivery_method'=>$app->input->post->get('delivery_method','','string'),
                'delivery_city'=>$app->input->post->get('delivery_city','','string'),
                'delivery_address'=>$app->input->post->get('delivery_address','','string'),
                'payment_method'=>$app->input->post->get('payment_method','','string'),
            ],(int)$quote['total_cents']);
            $id=\MiniMarket\Component\Minimarket\Site\Service\OrderStorage::create(
                $db,$contact,$quote,bin2hex(random_bytes(16)),0,$delivery,'manual');
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
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
            ->set($db->quoteName('deleted_at').'='.($remove?'UTC_TIMESTAMP()':'NULL'))
            ->where($db->quoteName('id').'='.(int)$id);
        if($remove)$q->where($db->quoteName('status').'='.$db->quote('cancelled'))
                      ->where($db->quoteName('deleted_at').' IS NULL');
        else $q->where($db->quoteName('deleted_at').' IS NOT NULL');
        $db->setQuery($q);$db->execute();
        if ($db->getAffectedRows()<1)
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_TRASH_DENIED'),400);
        $app->enqueueMessage(Text::_($remove?'COM_MINIMARKET_ORDER_TRASHED':'COM_MINIMARKET_ORDER_RESTORED'));
        $this->setRedirect('index.php?option=com_minimarket&view=orders&filter_trash='.($remove?'1':'0'));
    }

    public function status(): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        if (!$app->getIdentity()->authorise('core.edit.state','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $id=$app->input->post->getInt('id',0);
        $status=$app->input->post->getCmd('status','');
        if ($id<1 || !in_array($status,['new','confirmed','completed','cancelled'],true)) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_STATUS_INVALID'),400);
        }
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
            ->set($db->quoteName('status').'='.$db->quote($status))
            ->where($db->quoteName('id').'='.(int)$id)->where($db->quoteName('deleted_at').' IS NULL');
        $db->setQuery($q);$db->execute();
        $app->enqueueMessage(Text::_('COM_MINIMARKET_ORDER_STATUS_SAVED'));
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }
}
