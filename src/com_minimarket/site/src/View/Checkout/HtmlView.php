<?php
namespace MiniMarket\Component\Minimarket\Site\View\Checkout;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\CartCatalog;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutExtraFields;

final class HtmlView extends BaseHtmlView
{
    public array $cart=[];
    public string $checkoutKey='';
    public string $customerName='';
    public string $customerEmail='';
    public bool $enabled=false;
    public array $fields=[];
    public array $customFields=[];
    public bool $showLoginButton=true;
    public bool $skipBuyer=false;
    public bool $skipDelivery=false;
    public bool $skipPayment=false;
    public string $buyerDescription='';
    public string $deliveryDescription='';
    public string $paymentDescription='';
    public string $confirmationDescription='';
    public bool $requireTerms=false;
    public int $termsItemid=0;
    public string $phoneMask='';
    public array $deliveryMethods=[];
    public array $paymentMethods=[];
    public array $cityRates=[];
    public string $pickupAddress='';
    public string $pickupSchedule='';
    public string $pickupMapUrl='';
    public string $blockedMessage='';
    public bool $cdekPending=false;
    public string $promoCode='';
    public bool $needsPhysical=true;
    public bool $hasDigital=false;
    public bool $zonesMode=false;
    public array $zoneCities=[];

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $params=ComponentHelper::getParams('com_minimarket');
        $this->enabled=true;
        $session=$app->getSession();
        $state=CartState::normalize($session->get(CartState::SESSION_KEY,[]));
        $this->cart=$state?(new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class)))
            ->details($state,(int)$params->get('inventory_enabled',1)===1)
            :['lines'=>[],'available'=>false,'total_cents'=>0,'units'=>0];
        $this->needsPhysical=false;
        foreach($this->cart['lines'] as $line){
            if(($line['delivery_type']??'physical')==='digital')$this->hasDigital=true;
            else $this->needsPhysical=true;
        }
        $this->zonesMode=(string)$params->get('delivery_rate_source','directory')==='zones';
        if($this->zonesMode){
            $this->zoneCities=\MiniMarket\Component\Minimarket\Site\Service\DeliveryZones::cities(
                Factory::getContainer()->get(DatabaseInterface::class));
        }
        $this->cdekPending=(int)$params->get('delivery_courier_enabled',0)===1
            && (string)$params->get('delivery_rate_source','directory')==='cdek';
        try {
            $this->fields=CheckoutSettings::fields($params);
            $this->customFields=CheckoutExtraFields::definitions($params);
            $this->showLoginButton=(int)$params->get('checkout_show_login_button',1)===1;
            $this->buyerDescription=(string)$params->get('checkout_buyer_description','');
            $this->deliveryDescription=(string)$params->get('checkout_delivery_description','');
            $this->paymentDescription=(string)$params->get('checkout_payment_description','');
            $this->confirmationDescription=(string)$params->get('checkout_confirmation_description','');
            $this->requireTerms=(int)$params->get('checkout_require_terms',0)===1;
            $this->termsItemid=max(0,(int)$params->get('checkout_terms_itemid',0));
            if($this->requireTerms&&$this->termsItemid<1)
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_TERMS_CONFIG_MISSING');
            $this->phoneMask=(string)$params->get('checkout_phone_mask','');
            $this->deliveryMethods=CheckoutDelivery::methods($params,$this->needsPhysical);
            $this->paymentMethods=CheckoutDelivery::payments($params,!$this->needsPhysical);
            $this->cityRates=CheckoutDelivery::rates($params);
            $this->skipBuyer=CheckoutSettings::skipBuyer($params,$app->getIdentity(),$this->customFields);
            // No manual skipping of mandatory steps.
            $this->skipDelivery=count($this->deliveryMethods)===1
                &&(isset($this->deliveryMethods['pickup'])||isset($this->deliveryMethods['digital']));
            $this->skipPayment=count($this->paymentMethods)===1;
            $this->pickupAddress=(string)($this->deliveryMethods['pickup']['address']??'');
            $this->pickupSchedule=(string)($this->deliveryMethods['pickup']['schedule']??'');
            $this->pickupMapUrl=(string)($this->deliveryMethods['pickup']['map_url']??'');
            if (in_array(CheckoutSettings::mode($params),['email','sms'],true)
                && (int)$app->getIdentity()->id<1) {
                $pending=CheckoutSettings::mode($params)==='email'
                    ?'COM_MINIMARKET_CHECKOUT_EMAIL_PENDING':'COM_MINIMARKET_CHECKOUT_SMS_PENDING';
                $this->blockedMessage=Text::_($pending);
            } elseif ($this->hasDigital && (int)$app->getIdentity()->id<1) {
                $this->blockedMessage=Text::_('COM_MINIMARKET_DIGITAL_LOGIN_REQUIRED');
            } elseif (!$this->deliveryMethods || !$this->paymentMethods) {
                $this->blockedMessage=Text::_('COM_MINIMARKET_CHECKOUT_METHODS_MISSING');
            }
        } catch (\DomainException $e) {
            $this->blockedMessage=Text::_($e->getMessage());
        }
        $this->promoCode=(string)$session->get('com_minimarket.promo_code','');
        $key=(string)$session->get('com_minimarket.checkout.key','');
        if (!preg_match('/^[a-f0-9]{32}$/D',$key)) {
            $key=bin2hex(random_bytes(16));
            $session->set('com_minimarket.checkout.key',$key);
        }
        $this->checkoutKey=$key;
        $identity=$app->getIdentity();
        $this->customerName=(int)$identity->id>0?(string)$identity->name:'';
        $this->customerEmail=(int)$identity->id>0?(string)$identity->email:'';
        $document=$this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_CHECKOUT_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        $document->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.checkout','media/com_minimarket/css/checkout.css')
            ->registerAndUseScript('com_minimarket.checkout','media/com_minimarket/js/checkout.js',[],['defer'=>true]);
        parent::display($tpl);
    }
}
