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

/** Only POST+Joomla CSRF token may mutate the visitor's session cart. */
final class CartController extends BaseController
{
    public function add(): void { $this->change('add'); }
    public function update(): void { $this->change('update'); }
    public function remove(): void { $this->change('remove'); }

    private function change(string $mode): void
    {
        $app = Factory::getApplication();
        if ($app->input->getMethod() !== 'POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        $session = $app->getSession();
        $cart = CartState::normalize($session->get(CartState::SESSION_KEY,[]));
        $productId = $app->input->post->getInt('product_id');
        $variantId = $app->input->post->getInt('variant_id',0);
        $quantity = $mode === 'remove' ? 0 : $app->input->post->getInt('quantity',1);
        try {
            if ($mode === 'add') {
                $next = CartState::add($cart,$productId,$variantId,$quantity);
            } else {
                $next = CartState::put($cart,$productId,$variantId,$quantity);
            }
            // A remove must always succeed even when old products are gone.
            if ($mode !== 'remove' && $next) {
                $catalog = new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class));
                $inventory = (int)ComponentHelper::getParams('com_minimarket')
                    ->get('inventory_enabled',1) === 1;
                $catalog->assertAvailable($next,$inventory);
            }
            $session->set(CartState::SESSION_KEY,$next);
            $app->enqueueMessage(Text::_($mode === 'add'
                ? 'COM_MINIMARKET_CART_ADDED' : 'COM_MINIMARKET_CART_UPDATED'));
        } catch (\DomainException $exception) {
            // Never persist the invalid attempted mutation.
            $app->enqueueMessage(Text::_($exception->getMessage()),'warning');
        }
        $this->setRedirect(RouteHelper::cart());
    }
}
