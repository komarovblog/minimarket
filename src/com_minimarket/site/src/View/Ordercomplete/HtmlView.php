<?php
namespace MiniMarket\Component\Minimarket\Site\View\Ordercomplete;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public int $orderId=0;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        // Never reveal another customer's ID from URL query parameters.
        $this->orderId=(int)$app->getSession()->get('com_minimarket.checkout.last_order',0);
        $document=$this->getDocument();
        $document->setTitle(Text::_('COM_MINIMARKET_ORDER_COMPLETE_TITLE'));
        $document->setMetaData('robots','noindex,nofollow');
        $app->setHeader('Cache-Control','private, no-store',true);
        parent::display($tpl);
    }
}
