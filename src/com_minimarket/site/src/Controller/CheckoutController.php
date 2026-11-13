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
use MiniMarket\Component\Minimarket\Site\Service\CheckoutVerification;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutExtraFields;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\OrderStorage;
use MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore;

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
            if(!class_exists(\MiniMarket\Component\Minimarket\Site\Service\AutoDiscount::class))
                require_once dirname(__DIR__).'/Service/AutoDiscount.php';
            $automatic=\MiniMarket\Component\Minimarket\Site\Service\AutoDiscount::quote(
                $db,$quote['lines'],(int)$quote['total_cents']);
            $bestDiscount=max((int)($promo['discount_cents']??0),
                (int)($automatic['discount_cents']??0));
            $autoWins=$automatic!==null
                &&$automatic['discount_cents']>(int)($promo['discount_cents']??0);
            $grams=0;$volume=0;
            foreach($quote['lines'] as $line){
                $grams+=(int)($line['shipping_weight_grams']??0)*(int)$line['quantity'];
                $volume+=(int)($line['shipping_volume_cm3']??0)*(int)$line['quantity'];
            }
            $input=$app->input->post;
            $needsPhysical=false;
            foreach($quote['lines'] as $line){
                if(($line['delivery_type']??'physical')!=='digital')$needsPhysical=true;
            }
            $delivery=CheckoutDelivery::checkoutQuote($params,[
                'delivery_method'=>$input->getString('delivery_method',''),
            'pickup_point_id'=>$input->getInt('pickup_point_id',0),
                'delivery_city'=>$input->getString('delivery_city',''),
                'delivery_country'=>$input->getString('delivery_country','RU'),
                'delivery_region'=>$input->getString('delivery_region',''),
                'delivery_postcode'=>$input->getString('delivery_postcode',''),
                'delivery_address'=>$input->getString('delivery_address',''),
                'payment_method'=>$input->getString('payment_method','')
            ],(int)$quote['total_cents'],$grams,$volume,$needsPhysical);
            $result=['success'=>true,'discount_cents'=>$bestDiscount,
                'shipping_cents'=>(int)$delivery['fee_cents'],
                'message'=>Text::_($autoWins
                    ?'COM_MINIMARKET_AUTO_DISCOUNT_BEST_APPLIED':'COM_MINIMARKET_PROMO_PREVIEW')];
        }catch(\DomainException $error){
            $result['message']=Text::_($error->getMessage());
        }catch(\Throwable $error){
            $result['message']=Text::_('COM_MINIMARKET_PROMO_INVALID');
        }
        echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $app->close();
    }

    /** CSRF/session-guarded request. SMTP never runs on ordinary checkout GET. */
    public function sendCode():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $params=ComponentHelper::getParams('com_minimarket');
        $session=$app->getSession();
        $key=(string)$session->get('com_minimarket.checkout.key','');
        $posted=$app->input->post->get('checkout_key','','string');
        $response=['success'=>false,'message'=>Text::_('COM_MINIMARKET_OTP_INVALID')];
        try{
            if(!preg_match('/^[a-f0-9]{32}$/D',$key)||!hash_equals($key,$posted))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXPIRED');
            if($app->input->post->getString('website','')!=='')
                throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
            CheckoutSettings::method($params);
            $scope=$app->input->post->getCmd('verification_scope','');
            $user=$app->getIdentity();
            $email='';
            if($scope==='purchase'){
                if(!CheckoutSettings::requiresPurchaseCode($params)
                   ||(int)$user->id<1||!empty($user->block))
                    throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
                $email=(string)$user->email;
            }elseif($scope==='recipient'){
                if((int)$user->id<1||!empty($user->block))
                    throw new \DomainException('COM_MINIMARKET_OTP_LOGIN_REQUIRED');
                $email=$app->input->post->getString('digital_recipient_email','');
                $cart=CartState::normalize($session->get(CartState::SESSION_KEY,[]));
                if(!$cart)throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
                $quote=(new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class)))
                    ->details($cart,(int)$params->get('inventory_enabled',1)===1);
                if(!$quote['available']||!count(array_filter($quote['lines'],
                    static fn($l)=>($l['delivery_type']??'physical')==='digital')))
                    throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
            }else throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
            CheckoutVerification::issue($session,$scope,(int)$user->id,$key,$email);
            $response=['success'=>true,'message'=>Text::_('COM_MINIMARKET_OTP_SENT')];
        }catch(\DomainException $error){
            $response['message']=Text::_($error->getMessage());
        }catch(\Throwable){
            $response['message']=Text::_('COM_MINIMARKET_OTP_UNAVAILABLE');
        }
        if(str_contains((string)$app->input->server->getString('HTTP_ACCEPT',''),'application/json')){
            $app->setHeader('Content-Type','application/json; charset=utf-8',true);
            $app->setHeader('Cache-Control','private, no-store',true);
            echo json_encode($response,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $app->close();
        }
        $app->enqueueMessage($response['message'],$response['success']?'message':'warning');
        $this->setRedirect(RouteHelper::checkout());
    }

    public function submit(): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        $session=$app->getSession();
        $params=ComponentHelper::getParams('com_minimarket');
        if(CheckoutSettings::requiresAccount($params)
            &&(int)$app->getIdentity()->id<1){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CHECKOUT_LOGIN_REQUIRED'),'warning');
            $this->setRedirect(RouteHelper::checkout());return;
        }
        if(CheckoutSettings::requiresAccount($params)){
            try{CheckoutSettings::method($params);}
            catch(\DomainException $e){
                $app->enqueueMessage(Text::_($e->getMessage()),'warning');
                $this->setRedirect(RouteHelper::checkout());return;
            }
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
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $inventory=(int)$params->get('inventory_enabled',1)===1;
            $quote=(new CartCatalog($db))->details($cart,$inventory);
            if(!$quote['available'])throw new \DomainException($quote['error']?:'COM_MINIMARKET_CHECKOUT_CART_INVALID');
            $needsPhysical=false;$hasDigital=false;
            foreach($quote['lines'] as $line){
                if(($line['delivery_type']??'physical')==='digital')$hasDigital=true;
                else $needsPhysical=true;
            }
            $buyerEnabled=CheckoutSettings::step($params,'buyer');
            $digitalEnabled=CheckoutSettings::step($params,'digital');
            $policies=CheckoutSettings::fields($params);
            if($hasDigital && $buyerEnabled)$policies['email']='required';
            $customFields=$buyerEnabled
                ?CheckoutExtraFields::definitions($params):[];
            $skipBuyer=CheckoutSettings::skipBuyer($params,$identity,$customFields);
            // The administrator can explicitly hide even a mandatory buyer form.
            // A guest order then stores empty fields, never invented personal data.
            $forceSkipBuyer=!$buyerEnabled;
            if($forceSkipBuyer)
                $policies=['name'=>'optional','phone'=>'optional',
                    'email'=>'optional','comment'=>'hidden'];
            elseif($skipBuyer)
                $policies=['name'=>'required','phone'=>'hidden',
                    'email'=>'required','comment'=>'hidden'];
            $contact=CheckoutData::normalize([
                'name'=>$skipBuyer?(string)($identity->name??''):
                    $app->input->post->get('customer_name','','string'),
                'phone'=>$skipBuyer?'':$app->input->post->get('phone','','string'),
                'email'=>$skipBuyer?(string)($identity->email??''):
                    $app->input->post->get('email','','string'),
                'comment'=>$skipBuyer?'':$app->input->post->get('comment','','string'),
            ],$policies,$forceSkipBuyer);
            $mask=(string)$params->get('checkout_phone_mask','');
            if(!$skipBuyer&&$mask!==''&&$contact['phone']!==''
                &&!CheckoutExtraFields::maskMatches($mask,$contact['phone']))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_MASK_INVALID');
            $postedExtra=$app->input->post->get('extra',[],'array');
            $contact['custom_fields']=CheckoutExtraFields::validate(
                is_array($postedExtra)?$postedExtra:[],
                $skipBuyer?[]:$customFields);
            $recipient=$hasDigital&&$digitalEnabled
                ?trim($app->input->post->getString('digital_recipient_email','')):'';
            if($hasDigital){
                if(!$digitalEnabled||$recipient==='')
                    $recipient=(string)$contact['email'];
                if($recipient!==''
                    &&(filter_var($recipient,FILTER_VALIDATE_EMAIL)===false
                        ||strlen($recipient)>254))
                    throw new \DomainException('COM_MINIMARKET_CHECKOUT_EMAIL_INVALID');
                if($digitalEnabled && $recipient==='')
                    throw new \DomainException('COM_MINIMARKET_CHECKOUT_EMAIL_INVALID');
                if($forceSkipBuyer && $contact['email']==='' && $recipient!=='')
                    $contact['email']=$recipient;
                if($recipient!=='' && strcasecmp($recipient,(string)$contact['email'])!==0){
                    if((int)$identity->id<1)
                        throw new \DomainException('COM_MINIMARKET_OTP_LOGIN_REQUIRED');
                    CheckoutVerification::assertValid($session,'recipient',(int)$identity->id,
                        $expected,$recipient,$app->input->post->getString('recipient_code',''));
                }
            }
            $contact['digital_recipient_email']=$recipient;
            // The purchase code is controlled ONLY by confirmation mode, never
            // by a checkbox for the checkout step or the consent text.
            if(CheckoutSettings::requiresPurchaseCode($params)){
                CheckoutVerification::assertValid($session,'purchase',(int)$identity->id,
                    $expected,(string)$identity->email,
                    $app->input->post->getString('purchase_code',''));
            }
            $grams=0;$volume=0;
            foreach($quote['lines'] as $line){
                $grams+=(int)($line['shipping_weight_grams']??0)*(int)$line['quantity'];
                $volume+=(int)($line['shipping_volume_cm3']??0)*(int)$line['quantity'];
            }
            // An administrator-selected step OFF is always honoured. Unknown
            // delivery and payment are saved as "pending", never mislabelled
            // as free shipping or a successful payment.
            $delivery=CheckoutDelivery::checkoutQuote($params,[
                'delivery_method'=>$app->input->post->get('delivery_method','','string'),
                'pickup_point_id'=>$app->input->post->getInt('pickup_point_id',0),
                'delivery_city'=>$app->input->post->get('delivery_city','','string'),
                'delivery_country'=>$app->input->post->get('delivery_country','RU','string'),
                'delivery_region'=>$app->input->post->get('delivery_region','','string'),
                'delivery_postcode'=>$app->input->post->get('delivery_postcode','','string'),
                'delivery_address'=>$app->input->post->get('delivery_address','','string'),
                'payment_method'=>$app->input->post->get('payment_method','','string'),
            ],(int)$quote['total_cents'],$grams,$volume,$needsPhysical);
            $promoCode=strtoupper(trim((string)$app->input->post->get('promo_code',
                (string)$session->get('com_minimarket.promo_code',''),'string')));
            $id=OrderStorage::create($db,$contact,$quote,$expected,(int)$identity->id,$delivery,'site',$promoCode);
            // Mark the checkout session cart converted only AFTER OrderStorage::create.
            try{AbandonedCartStore::converted($db,$session,$id);}catch(\Throwable){}
            CheckoutVerification::consume($session);
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
