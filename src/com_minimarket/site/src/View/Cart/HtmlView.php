<?php
namespace MiniMarket\Component\Minimarket\Site\View\Cart;
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
    public array $cart = [];
    public bool $inventoryEnabled = true;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->inventoryEnabled = (int)ComponentHelper::getParams('com_minimarket')
            ->get('inventory_enabled',1) === 1;
        $state = CartState::normalize($app->getSession()->get(CartState::SESSION_KEY,[]));
        $catalog = new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class));
        $this->cart = $catalog->details($state,$this->inventoryEnabled);
        $document = $this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_CART_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        $assets=$document->getWebAssetManager();
        $assets->registerAndUseStyle('com_minimarket.cart',
                'media/com_minimarket/css/cart.css')
            ->registerAndUseScript('com_minimarket.cart',
                'media/com_minimarket/js/cart.js',[],['defer'=>true]);
        parent::display($tpl);
    }
}
