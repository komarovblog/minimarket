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
    public string $consentText='';
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
    public bool $needPurchaseVerification=false;
    public string $confirmationMode='guest';
    public string $digitalDescription='';
    public bool $showDigitalStep=false;
    public string $digitalRecipientEmail='';
    public string $verificationEmail='';
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
        $this->zonesMode=CheckoutSettings::step($params,'delivery')
            &&(string)$params->get('delivery_rate_source','directory')==='zones';
        if($this->zonesMode){
            $this->zoneCities=\MiniMarket\Component\Minimarket\Site\Service\DeliveryZones::cities(
                Factory::getContainer()->get(DatabaseInterface::class));
        }
        $this->cdekPending=CheckoutSettings::step($params,'delivery')
            &&(int)$params->get('delivery_courier_enabled',0)===1
            && (string)$params->get('delivery_rate_source','directory')==='cdek';
        try {
            $this->fields=CheckoutSettings::fields($params);
            $this->customFields=CheckoutSettings::step($params,'buyer')
                ?CheckoutExtraFields::definitions($params):[];
            $this->showLoginButton=(int)$params->get('checkout_show_login_button',1)===1;
            $this->buyerDescription=(string)$params->get('checkout_buyer_description','');
            $this->deliveryDescription=(string)$params->get('checkout_delivery_description','');
            $this->paymentDescription=(string)$params->get('checkout_payment_description','');
            $this->consentText=(string)$params->get('checkout_consent_text',
                (string)$params->get('checkout_confirmation_description',''));
            $this->digitalDescription=(string)$params->get('checkout_digital_description','');
            $this->showDigitalStep=$this->hasDigital && CheckoutSettings::step($params,'digital');
            $this->confirmationMode=CheckoutSettings::mode($params);
            $this->needPurchaseVerification=CheckoutSettings::requiresPurchaseCode($params);
            $this->phoneMask=(string)$params->get('checkout_phone_mask','');
            $deliveryEnabled=CheckoutSettings::step($params,'delivery');
            $paymentEnabled=CheckoutSettings::step($params,'payment');
            // A hidden step must not validate unavailable/invalid methods.
            $this->deliveryMethods=$deliveryEnabled
                ?CheckoutDelivery::methods($params,$this->needsPhysical):[];
            $this->paymentMethods=$paymentEnabled
                ?CheckoutDelivery::payments($params,!$this->needsPhysical):[];
            $this->cityRates=$deliveryEnabled?CheckoutDelivery::rates($params):[];
            $this->skipBuyer=CheckoutSettings::skipBuyer(
                $params,$app->getIdentity(),$this->customFields);
            $this->skipDelivery=!$deliveryEnabled
                ||(count($this->deliveryMethods)===1
                    &&(isset($this->deliveryMethods['pickup'])
                        ||isset($this->deliveryMethods['digital'])));
            $this->skipPayment=!$paymentEnabled ||count($this->paymentMethods)===1;
            $this->pickupAddress=(string)($this->deliveryMethods['pickup']['address']??'');
            $this->pickupSchedule=(string)($this->deliveryMethods['pickup']['schedule']??'');
            $this->pickupMapUrl=(string)($this->deliveryMethods['pickup']['map_url']??'');
            if (CheckoutSettings::requiresAccount($params)
                && (int)$app->getIdentity()->id<1) {
                $this->blockedMessage=Text::_('COM_MINIMARKET_CHECKOUT_LOGIN_REQUIRED');
            } elseif (CheckoutSettings::requiresAccount($params)
                && CheckoutSettings::method($params)!=='email'){
                $this->blockedMessage=Text::_('COM_MINIMARKET_CHECKOUT_AUTH_NOT_CONFIGURED');
            } elseif (($deliveryEnabled && !$this->deliveryMethods)
                ||($paymentEnabled && !$this->paymentMethods)) {
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
        $this->digitalRecipientEmail=$this->customerEmail;
        $this->verificationEmail=$this->customerEmail;
        $document=$this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_CHECKOUT_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        $document->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.checkout','media/com_minimarket/css/checkout.css')
            ->registerAndUseScript('com_minimarket.checkout','media/com_minimarket/js/checkout.js',[],['defer'=>true])
            ->registerAndUseScript('com_minimarket.payment-compatibility',
                'media/com_minimarket/js/payment-compatibility.js',[],['defer'=>true]);
        parent::display($tpl);
    }
}
