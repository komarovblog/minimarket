<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\PluginMethodSettings;

final class PluginmethodController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit','com_plugins')
            && !$app->getIdentity()->authorise('core.admin','com_plugins'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        $scope=$post->getCmd('scope','');
        $id=$post->getInt('plugin_id',0);
        if(!in_array($scope,['delivery','payment','registration'],true)||$id<1)
            throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
        $view=match($scope){'delivery'=>'deliverymethods',
            'payment'=>'paymentmethods',default=>'registrationmethods'};
        $back='index.php?option=com_minimarket&view='.$view.'&method=plugin_'.$id;
        try {
            PluginMethodSettings::save(Factory::getContainer()->get(DatabaseInterface::class),
                $scope,$id,$post->get('plugin',[],'array'));
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_SAVED'));
            $this->setRedirect($post->getInt('save_close',0)===1
                ?'index.php?option=com_minimarket&view='.$view:$back);
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
            $this->setRedirect($back);
        }
    }
}
