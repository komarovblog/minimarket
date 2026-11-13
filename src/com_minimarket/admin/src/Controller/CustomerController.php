<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
final class CustomerController extends BaseController
{
    public function note():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $body=trim(strip_tags((string)$app->input->post->getString('body','')));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try{
            if($id<1||mb_strlen($body)<3||mb_strlen($body)>4000
                ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$body))
                throw new \DomainException('COM_MINIMARKET_CRM_NOTE_INVALID');
            $db->setQuery('SELECT id FROM '.$db->quoteName('#__users').' WHERE id='.$id);
            if((int)$db->loadResult()!==$id)
                throw new \DomainException('COM_MINIMARKET_CRM_NOTE_INVALID');
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_customer_notes'))
                ->columns($db->quoteName(['user_id','body','author_user_id','created_at']))
                ->values(implode(',',[(string)$id,$db->quote($body),
                    (string)(int)$app->getIdentity()->id,'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_CRM_NOTE_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=customer&id='.$id);
    }
}
