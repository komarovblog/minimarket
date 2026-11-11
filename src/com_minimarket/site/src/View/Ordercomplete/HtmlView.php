<?php
namespace MiniMarket\Component\Minimarket\Site\View\Ordercomplete;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public int $orderId=0;
    public string $bankInstructions='';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        // Never reveal another customer's ID from URL query parameters.
        // Confirmation is accessible only through the originating Joomla
        // session; never read an order ID from a public query parameter.
        $id=(int)$app->getSession()->get('com_minimarket.checkout.last_order',0);
        if ($id>0) {
            $identity=$app->getIdentity();
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->select($db->quoteName(['user_id','payment_method']))
                ->from($db->quoteName('#__minimarket_orders'))
                ->where($db->quoteName('id').'='.(int)$id);
            $db->setQuery($q);
            $order=$db->loadObject();
            $owner=$order->user_id??null;
            if ($owner!==null && $owner!==false
                && (((int)$owner===0 && (int)$identity->id===0)
                    || ((int)$identity->id>0 && empty($identity->block)
                    && (int)$owner===(int)$identity->id))) {
                $this->orderId=$id;
                if((string)($order->payment_method??'')==='bank_transfer'){
                    $params=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
                    $this->bankInstructions=trim(strip_tags((string)$params->get('payment_bank_instructions','')));
                }
            }
        }
        $document=$this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_ORDER_COMPLETE_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        parent::display($tpl);
    }
}
