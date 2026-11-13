<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartCatalog;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore;

/** Only POST+Joomla CSRF token may mutate the visitor's session cart. */
final class CartController extends BaseController
{
    public function add(): void { $this->change('add'); }
    public function update(): void { $this->change('update'); }
    public function remove(): void { $this->change('remove'); }

    /** Remember a code from cart; actual eligibility is repriced on checkout. */
    public function promo(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $code=strtoupper(trim($app->input->post->getString('promo_code','')));
        $session=$app->getSession();
        if($code==='') {
            $session->set('com_minimarket.promo_code','');
        }elseif(preg_match('/^[A-Z0-9-]{3,64}$/D',$code)){
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('id')
                ->from($db->quoteName('#__minimarket_promo_codes'))
                ->where('code='.$db->quote($code))->where('state=1'));
            if((int)$db->loadResult()<1){
                $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMO_INVALID'),'warning');
                $this->setRedirect(RouteHelper::cart());return;
            }
            $session->set('com_minimarket.promo_code',$code);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMO_SAVED'));
        }else{
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMO_INVALID'),'warning');
        }
        $this->setRedirect(RouteHelper::cart());
    }

    private function change(string $mode): void
    {
        $app = Factory::getApplication();
        if ($app->input->getMethod() !== 'POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        $ajax = $app->input->server->getString('HTTP_X_REQUESTED_WITH','')==='XMLHttpRequest'
            && str_contains((string)$app->input->server->getString('HTTP_ACCEPT',''), 'application/json');
        $session = $app->getSession();
        $cart = CartState::normalize($session->get(CartState::SESSION_KEY,[]));
        $productId = $app->input->post->getInt('product_id');
        $variantId = $app->input->post->getInt('variant_id',0);
        $quantity = $mode === 'remove' ? 0 : $app->input->post->getInt('quantity',1);
        $message='';
        $success=true;
        try {
            if ($mode !== 'remove') {
                $requested=max(1,(int)$quantity);
                $catalog=new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class));
                $inventory=(int)ComponentHelper::getParams('com_minimarket')
                    ->get('inventory_enabled',1)===1;
                $max=$catalog->maxQuantity($cart,$productId,$variantId,$inventory);
                if ($max<1) throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                $target=$mode==='add'
                    ?($cart[CartState::key($productId,$variantId)]??0)+$requested
                    :$requested;
                if ($target>$max) {
                    $quantity=$mode==='add'?$max-($cart[CartState::key($productId,$variantId)]??0):$max;
                    $message=Text::sprintf('COM_MINIMARKET_CART_CLAMPED',$max);
                } else $quantity=$requested;
                // Existing line was already at its available maximum.
                if ($mode==='add' && $quantity<=0) {
                    throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                }
            }
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
            // Best-effort first-party cart analytics; must not interrupt the purchase.
            try{AbandonedCartStore::record(Factory::getContainer()->get(DatabaseInterface::class),
                $session,(int)$app->getIdentity()->id,$next);}catch(\Throwable){}
            if ($message==='') $message=Text::_($mode === 'add'
                ? 'COM_MINIMARKET_CART_ADDED' : 'COM_MINIMARKET_CART_UPDATED');
        } catch (\DomainException $exception) {
            $success=false;
            // Never persist the invalid attempted mutation.
            $message=Text::_($exception->getMessage());
        }
        if ($ajax && in_array($mode,['update','remove'],true)) {
            try {
                $catalog=new CartCatalog(Factory::getContainer()->get(DatabaseInterface::class));
                $inventory=(int)ComponentHelper::getParams('com_minimarket')
                    ->get('inventory_enabled',1)===1;
                $state=CartState::normalize($session->get(CartState::SESSION_KEY,[]));
                $details=$catalog->details($state,$inventory);
                $lines=[];
                foreach ($details['lines'] as $line) {
                    $lines[$line['key']]=[
                        'quantity'=>(int)$line['quantity'],
                        'line_cents'=>(int)$line['line_cents'],
                        'unit_cents'=>(int)$line['unit_cents'],
                        'max_quantity'=>(int)$line['max_quantity'],
                        'valid'=>(bool)$line['valid'],
                    ];
                }
                $data=['lines'=>$lines,'total_cents'=>(int)$details['total_cents'],
                    'units'=>(int)$details['units'],'available'=>(bool)$details['available'],
                    'empty'=>!$state,'message'=>$message];
                $app->setHeader('Cache-Control','private, no-store',true);
                $app->setHeader('Content-Type','application/json; charset=utf-8',true);
                echo new JsonResponse($data,$message,!$success);
                $app->close();
            } catch (\Throwable $e) {
                // A failed quote is never turned into a false success.
                $app->setHeader('Content-Type','application/json; charset=utf-8',true);
                echo new JsonResponse(null,Text::_('COM_MINIMARKET_CART_NOT_AVAILABLE'),true);
                $app->close();
            }
            return;
        }
        $app->enqueueMessage($message,$success?'message':'warning');
        $this->setRedirect(RouteHelper::cart());
    }
}
