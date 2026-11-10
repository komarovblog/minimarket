<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartCatalog;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutData;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\OrderStorage;

/** Customer POST only: validate nonce, CSRF, inventory and fresh server prices. */
final class CheckoutController extends BaseController
{
    public function submit(): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        $session=$app->getSession();
        $params=ComponentHelper::getParams('com_minimarket');
        if ((string)$params->get('shop_type','physical')!=='physical') {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_CART_NOT_AVAILABLE'),403);
        }
        // SMS mode must never silently save unverified orders. Until a real
        // provider and Joomla account flow exist it remains fail-closed.
        if (CheckoutSettings::mode($params)==='sms' && (int)$app->getIdentity()->id<1) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CHECKOUT_SMS_PENDING'),'warning');
            $this->setRedirect(RouteHelper::checkout());
            return;
        }
        // Guest mode is used to test the final checkout UX without integrations.
        $identity=$app->getIdentity();
        if ((int)$identity->id>0 && !empty($identity->block)) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $expected=(string)$session->get('com_minimarket.checkout.key','');
        $provided=(string)$app->input->post->get('checkout_key','','string');
        if (!preg_match('/^[a-f0-9]{32}$/D',$expected) || !hash_equals($expected,$provided)) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CHECKOUT_EXPIRED'),'warning');
            $this->setRedirect(RouteHelper::checkout());
            return;
        }
        // Honeypot is only a secondary anti-spam measure, never a replacement
        // for Joomla's CSRF token and single-use checkout key.
        if ($app->input->post->getString('website','')!=='') {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_CHECKOUT_INVALID'),400);
        }
        $cart=CartState::normalize($session->get(CartState::SESSION_KEY,[]));
        if (!$cart) {
            $this->setRedirect(RouteHelper::cart());
            return;
        }
        try {
            $policies=CheckoutSettings::fields($params);
            $contact=CheckoutData::normalize([
                'name'=>$app->input->post->get('customer_name','','string'),
                'phone'=>$app->input->post->get('phone','','string'),
                'email'=>$app->input->post->get('email','','string'),
                'comment'=>$app->input->post->get('comment','','string'),
            ],$policies);
            $delivery=CheckoutDelivery::quote($params,[
                'delivery_method'=>$app->input->post->get('delivery_method','','string'),
                'delivery_city'=>$app->input->post->get('delivery_city','','string'),
                'delivery_address'=>$app->input->post->get('delivery_address','','string'),
                'payment_method'=>$app->input->post->get('payment_method','','string'),
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $inventory=(int)$params->get('inventory_enabled',1)===1;
            $quote=(new CartCatalog($db))->details($cart,$inventory);
            if (!$quote['available']) throw new \DomainException($quote['error']?:'COM_MINIMARKET_CHECKOUT_CART_INVALID');
            $id=OrderStorage::create($db,$contact,$quote,$expected,(int)$identity->id,$delivery);
            $session->set('com_minimarket.checkout.key','');
            $session->set(CartState::SESSION_KEY,[]);
            $session->set('com_minimarket.checkout.last_order',$id);
            $this->setRedirect(RouteHelper::orderComplete());
        } catch (\DomainException $e) {
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
            $this->setRedirect(RouteHelper::checkout());
        } catch (\Throwable $e) {
            // Do not leak database errors or customer PII to site visitors.
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CHECKOUT_SAVE_ERROR'),'error');
            $this->setRedirect(RouteHelper::checkout());
        }
    }
}
