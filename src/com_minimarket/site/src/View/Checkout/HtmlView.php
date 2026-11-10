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

final class HtmlView extends BaseHtmlView
{
    public array $cart=[];
    public string $checkoutKey='';
    public string $customerName='';
    public string $customerEmail='';
    public bool $enabled=false;
    public bool $authenticated=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $params=ComponentHelper::getParams('com_minimarket');
        $this->enabled=(string)$params->get('shop_type','physical')==='physical';
        $state=CartState::normalize($app->getSession()->get(CartState::SESSION_KEY,[]));
        if ($this->enabled && $state) {
            $this->cart=(new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class)))
                ->details($state,(int)$params->get('inventory_enabled',1)===1);
        } else {
            $this->cart=['lines'=>[],'available'=>false,'total_cents'=>0,'units'=>0];
        }
        $key=(string)$app->getSession()->get('com_minimarket.checkout.key','');
        if (!preg_match('/^[a-f0-9]{32}$/D',$key)) {
            $key=bin2hex(random_bytes(16));
            $app->getSession()->set('com_minimarket.checkout.key',$key);
        }
        $this->checkoutKey=$key;
        $identity=$app->getIdentity();
        $this->authenticated=(int)$identity->id>0 && empty($identity->block);
        $this->customerName=(int)$identity->id>0?(string)$identity->name:'';
        $this->customerEmail=(int)$identity->id>0?(string)$identity->email:'';
        $document=$this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_CHECKOUT_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        parent::display($tpl);
    }
}
