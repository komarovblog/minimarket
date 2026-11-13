<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\SalesPlan;

final class PlanfactreportController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        $year=$post->getInt('year',0);
        $month=$post->getInt('month',0);
        try{
            $target=SalesPlan::cents($post->getString('target',''));
            $changed=SalesPlan::save(
                Factory::getContainer()->get(DatabaseInterface::class),
                $year,$month,$target,(int)$app->getIdentity()->id);
            $app->enqueueMessage(Text::_($changed
                ?'COM_MINIMARKET_PLANFACT_REPORT_SAVED'
                :'COM_MINIMARKET_PLANFACT_REPORT_UNCHANGED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PLANFACT_REPORT_INVALID'),'warning');
        }
        $year=max(2020,min(2100,$year));
        $month=max(1,min(12,$month));
        $this->setRedirect('index.php?option=com_minimarket&view=planfactreport'
            .'&year='.$year.'&month='.$month);
    }
}
