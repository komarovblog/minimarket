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
    /** Native Joomla single-selection Edit action for a mixed settings/provider list.
     * Methods are configuration, not deletable catalog entities; never invent bulk
     * publish/delete operations over third-party plugin extension records. */
    public function editSelected(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        $scope=$post->getCmd('scope','');
        $allow=['delivery'=>['pickup','courier'],
                'payment'=>['on_receipt','bank_transfer'],
                'registration'=>['email'],
                'notification'=>['email']];
        if(!isset($allow[$scope]))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
        $selected=$post->get('cid',[],'array');
        if(!is_array($selected)||count($selected)!==1)
            throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
        $method=(string)$selected[0];
        if(!in_array($method,$allow[$scope],true)){
            if(!preg_match('/^plugin_[1-9][0-9]*$/D',$method))
                throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
            $pluginId=(int)substr($method,7);
            $plugins=\MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins::list(
                Factory::getContainer()->get(DatabaseInterface::class),$scope);
            $found=false;
            foreach($plugins as $plugin)if((int)$plugin->extension_id===$pluginId){$found=true;break;}
            if(!$found)throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),404);
        }
        $view=$scope.'methods';
        $this->setRedirect('index.php?option=com_minimarket&view='.$view.'&method='.$method);
    }

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
            ||($scope==='payment'&&in_array($method,['on_receipt','bank_transfer'],true))
            ||($scope==='registration'&&$method==='email');
        if(!$valid)throw new \RuntimeException(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),400);
        $view=match($scope){'delivery'=>'deliverymethods','registration'=>'registrationmethods',default=>'paymentmethods'};
        try {
            if($scope==='registration'&&$method==='email'
                &&$post->getString('primary','0')==='1'
                &&(int)\Joomla\CMS\Component\ComponentHelper::getParams('com_users')
                    ->get('allowUserRegistration',0)!==1)
                throw new \DomainException('COM_MINIMARKET_REGISTRATION_EMAIL_DISABLED');
            $changes=MethodSettings::normalize($scope,$method,[
                'enabled'=>$post->getString('enabled','0'),
                'primary'=>$post->getString('primary','0'),
                'address'=>$post->getString('address',''),
                'fee'=>$post->getString('fee','0'),
                'schedule'=>$post->get('schedule','','raw'),
                'map_url'=>$post->getString('map_url',''),
                'points_mode'=>$post->getCmd('points_mode','single'),
                'rate_source'=>$post->getCmd('rate_source','directory'),
                'city_rates'=>$post->get('city_rates','','raw'),
                'instructions'=>$post->get('instructions','','raw'),
                ...($scope==='payment'?['allowed_deliveries'=>$post->get('allowed_deliveries',[],'array')]:[])
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
    public function saveDeliveryCompatibility():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.options','com_minimarket')
            && !$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $delivery=$app->input->post->getCmd('delivery_method','');
        $selected=$app->input->post->get('allowed_payments',[],'array');
        try{
            if(!is_array($selected))throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            MethodSettings::saveDeliveryMirror(
                Factory::getContainer()->get(DatabaseInterface::class),$delivery,$selected);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=deliverymethods&method='
            .(in_array($delivery,['pickup','courier'],true)?$delivery:'pickup').'&tab=compatibility');
    }
}
