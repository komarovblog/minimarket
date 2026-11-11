<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings;

final class MethodsettingsController extends BaseController
{
    public function save(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post')){
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        if(!$app->getIdentity()->authorise('core.options','com_minimarket')
            && !$app->getIdentity()->authorise('core.admin','com_minimarket')){
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $post=$app->input->post;
        $scope=$post->getCmd('scope','');
        $method=$post->getCmd('method','');
        $valid=($scope==='delivery'&&in_array($method,['pickup','courier'],true))
            ||($scope==='payment'&&in_array($method,['on_receipt','bank_transfer'],true));
        if(!$valid)throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
        $view=$scope==='delivery'?'deliverymethods':'paymentmethods';
        try {
            $changes=MethodSettings::normalize($scope,$method,[
                'enabled'=>$post->getString('enabled','0'),
                'address'=>$post->getString('address',''),
                'fee'=>$post->getString('fee','0'),
                'schedule'=>$post->get('schedule','','raw'),
                'map_url'=>$post->getString('map_url',''),
                'rate_source'=>$post->getCmd('rate_source','directory'),
                'city_rates'=>$post->get('city_rates','','raw'),
                'instructions'=>$post->get('instructions','','raw')
            ]);
            MethodSettings::save(Factory::getContainer()->get(DatabaseInterface::class),$changes);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view='.$view
                .($post->getInt('save_close',0)===1?'':'&method='.$method));
        } catch(\DomainException $e) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view='.$view.'&method='.$method);
        }
    }
}
