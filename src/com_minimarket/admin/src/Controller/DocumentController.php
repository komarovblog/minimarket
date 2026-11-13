<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderDocuments;

/** Issues non-fiscal immutable commercial summaries only. */
final class DocumentController extends BaseController
{
    public function create():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $orderId=$app->input->post->getInt('order_id',0);
        $redirect='index.php?option=com_minimarket&view=order&id='.$orderId;
        try {
            $id=OrderDocuments::create(
                Factory::getContainer()->get(DatabaseInterface::class),
                $orderId,(int)$app->getIdentity()->id);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DOCUMENT_CREATED'));
            $this->setRedirect('index.php?option=com_minimarket&view=document&id='.$id);
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_ORDER_DOCUMENT_INVALID'),'warning');
            $this->setRedirect($redirect);
        }
    }
}
