<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
final class PromocodeController extends BaseController
{
    private function guard(string $acl):void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise($acl,'com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }
    public function create():void
    {
        $this->guard('core.create');
        $app=Factory::getApplication();
        $code=strtoupper(trim($app->input->post->getString('code','')));
        $raw=trim((string)$app->input->post->get('amount','','string'));
        $redirect='index.php?option=com_minimarket&view=promocode&layout=create';
        if(!preg_match('/^[A-Z0-9-]{3,64}$/D',$code)
          ||!preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$raw)){
          $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),'warning');
          $this->setRedirect($redirect);return;
        }
        $v=explode('.',str_replace(',','.',$raw),2);
        $cents=(int)$v[0]*100+(int)str_pad($v[1]??'',2,'0');
        if($cents<1){$app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),'warning');
           $this->setRedirect($redirect);return;}
        try{
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_promo_codes'))
               ->columns($db->quoteName(['code','amount_cents','state','created_by','created_at']))
               ->values(implode(',',[$db->quote($code),(string)$cents,'0',
                 (string)(int)$app->getIdentity()->id,'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view=promocodes');
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),'warning');
            $this->setRedirect($redirect);
        }
    }
    public function state():void
    {
        $this->guard('core.edit.state');
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);$state=$app->input->post->getInt('state',-1);
        if($id<1||!in_array($state,[0,1],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_promo_codes'))
            ->set('state='.(int)$state)->where('id='.(int)$id);
        $db->setQuery($q);$db->execute();
        $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_SAVED'));
        $this->setRedirect('index.php?option=com_minimarket&view=promocodes');
    }
}
