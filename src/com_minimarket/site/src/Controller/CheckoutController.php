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
use MiniMarket\Component\Minimarket\Site\Service\CheckoutExtraFields;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\OrderStorage;

/** Customer POST only: validate nonce, CSRF, inventory and fresh server prices. */
final class CheckoutController extends BaseController
{
    /** Non-mutating preview; checkout.submit always recalculates under lock. */
    /** Non-mutating preview; checkout.submit always recalculates under lock. */
    public function preview(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $app->setHeader('Cache-Control','private, no-store',true);
        $app->setHeader('Content-Type','application/json; charset=utf-8',true);
        $result=['success'=>false,'discount_cents'=>0,'shipping_cents'=>null,'message'=>''];
        try{
            $params=ComponentHelper::getParams('com_minimarket');
            $session=$app->getSession();
            $cart=CartState::normalize($session->get(CartState::SESSION_KEY,[]));
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $quote=(new CartCatalog($db))->details($cart,(int)$params->get('inventory_enabled',1)===1);
            if(!$quote['available']||!$quote['lines'])
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
            $code=trim($app->input->post->getString('promo_code',''));
            $contact=[
                'phone'=>$app->input->post->getString('phone',''),
                'email'=>$app->input->post->getString('email','')
            ];
            $promo=\MiniMarket\Component\Minimarket\Site\Service\PromotionService::quote(
                $db,$code,$quote['lines'],(int)$quote['total_cents'],
                (int)$app->getIdentity()->id,$contact,false
            );
            $grams=0;$volume=0;
            foreach($quote['lines'] as $line){
                $grams+=(int)($line['shipping_weight_grams']??0)*(int)$line['quantity'];
                $volume+=(int)($line['shipping_volume_cm3']??0)*(int)$line['quantity'];
            }
            $input=$app->input->post;
            $delivery=CheckoutDelivery::quote($params,[
                'delivery_method'=>$input->getString('delivery_method',''),
                'delivery_city'=>$input->getString('delivery_city',''),
                'delivery_country'=>$input->getString('delivery_country','RU'),
                'delivery_region'=>$input->getString('delivery_region',''),
                'delivery_postcode'=>$input->getString('delivery_postcode',''),
                'delivery_address'=>$input->getString('delivery_address',''),
                'payment_method'=>$input->getString('payment_method','')
            ],(int)$quote['total_cents'],$grams,$volume);
            $result=['success'=>true,'discount_cents'=>(int)($promo['discount_cents']??0),
                'shipping_cents'=>(int)$delivery['fee_cents'],
                'message'=>Text::_('COM_MINIMARKET_PROMO_PREVIEW')];
        }catch(\DomainException $error){
            $result['message']=Text::_($error->getMessage());
        }catch(\Throwable $error){
            $result['message']=Text::_('COM_MINIMARKET_PROMO_INVALID');
        }
        echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $app->close();
    }

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
        if (in_array(CheckoutSettings::mode($params),['email','sms'],true)
            && (int)$app->getIdentity()->id<1) {
            $pending=CheckoutSettings::mode($params)==='email'
                ?'COM_MINIMARKET_CHECKOUT_EMAIL_PENDING':'COM_MINIMARKET_CHECKOUT_SMS_PENDING';
            $app->enqueueMessage(Text::_($pending),'warning');
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
            $customFields=CheckoutExtraFields::definitions($params);
            $skipBuyer=CheckoutSettings::skipBuyer($params,$identity,$customFields);
            if($skipBuyer)$policies=['name'=>'required','phone'=>'hidden','email'=>'required','comment'=>'hidden'];
            $contact=CheckoutData::normalize([
                'name'=>$skipBuyer?(string)$identity->name:$app->input->post->get('customer_name','','string'),
                'phone'=>$skipBuyer?'':$app->input->post->get('phone','','string'),
                'email'=>$skipBuyer?(string)$identity->email:$app->input->post->get('email','','string'),
                'comment'=>$skipBuyer?'':$app->input->post->get('comment','','string'),
            ],$policies);
            $mask=(string)$params->get('checkout_phone_mask','');
            if(!$skipBuyer && $mask!=='' && $contact['phone']!==''
               &&!CheckoutExtraFields::maskMatches($mask,$contact['phone']))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_MASK_INVALID');
            $postedExtra=$app->input->post->get('extra',[],'array');
            $contact['custom_fields']=CheckoutExtraFields::validate(
                is_array($postedExtra)?$postedExtra:[],$skipBuyer?[]:$customFields);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $inventory=(int)$params->get('inventory_enabled',1)===1;
            $quote=(new CartCatalog($db))->details($cart,$inventory);
            if (!$quote['available']) throw new \DomainException($quote['error']?:'COM_MINIMARKET_CHECKOUT_CART_INVALID');
            $grams=0;$volume=0;
            foreach($quote['lines'] as $line){
                $grams+=(int)($line['shipping_weight_grams']??0)*(int)$line['quantity'];
                $volume+=(int)($line['shipping_volume_cm3']??0)*(int)$line['quantity'];
            }
            $methods=CheckoutDelivery::methods($params);
            $payments=CheckoutDelivery::payments($params);
            $skipDelivery=(int)$params->get('checkout_delivery_skip',0)===1 &&isset($methods['pickup']);
            $skipPayment=(int)$params->get('checkout_payment_skip',0)===1 &&count($payments)>0;
            $delivery=CheckoutDelivery::quote($params,[
                'delivery_method'=>$skipDelivery?'pickup':$app->input->post->get('delivery_method','','string'),
                'delivery_city'=>$app->input->post->get('delivery_city','','string'),
                'delivery_country'=>$app->input->post->get('delivery_country','RU','string'),
                'delivery_region'=>$app->input->post->get('delivery_region','','string'),
                'delivery_postcode'=>$app->input->post->get('delivery_postcode','','string'),
                'delivery_address'=>$app->input->post->get('delivery_address','','string'),
                'payment_method'=>$skipPayment?(string)array_key_first($payments):$app->input->post->get('payment_method','','string'),
            ],(int)$quote['total_cents'],$grams,$volume);
            $promoCode=strtoupper(trim((string)$app->input->post->get('promo_code',
                (string)$session->get('com_minimarket.promo_code',''),'string')));
            $id=OrderStorage::create($db,$contact,$quote,$expected,(int)$identity->id,$delivery,'site',$promoCode);
            $session->set('com_minimarket.checkout.key','');
            $session->set(CartState::SESSION_KEY,[]);
            $session->set('com_minimarket.promo_code','');
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
