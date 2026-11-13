<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

final class MarketingflowsController extends BaseController
{
    public function add():void
    {
        if(!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->setRedirect('index.php?option=com_minimarket&view=marketingflow&layout=edit');
    }
}
