<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\DeliveryZoneData;

final class DeliveryzoneController extends BaseController
{
    public function save(): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $post=$app->input->post;$id=$post->getInt('id',0);
        if(!$app->getIdentity()->authorise($id?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $back='index.php?option=com_minimarket&view=deliveryzone'.($id?'&id='.$id:'&layout=create');
        try {
            $fields=[
                'title','country_code','region','cities','postcode_prefixes','rate_mode',
                'base_fee','rate_per_unit','free_from','min_cart','max_weight_grams',
                'max_volume_cm3','estimated_days_min','estimated_days_max','priority','state'
            ];
            $input=[];
            foreach($fields as $name)$input[$name]=$post->get($name,'','string');
            $data=DeliveryZoneData::normalize($input);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            if($id>0){
                $db->setQuery($db->getQuery(true)->select('id')
                    ->from($db->quoteName('#__minimarket_delivery_zones'))->where('id='.(int)$id));
                if((int)$db->loadResult()!==$id)throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_delivery_zones'));
                foreach($data as $name=>$value)
                    $q->set($db->quoteName($name).'='.$db->quote((string)$value));
                $q->set('updated_at=UTC_TIMESTAMP()')->where('id='.(int)$id);
            }else{
                $columns=array_keys($data);
                $values=array_map(static fn($v)=>$db->quote((string)$v),array_values($data));
                $columns[]='created_at';$values[]='UTC_TIMESTAMP()';
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_delivery_zones'))
                    ->columns($db->quoteName($columns))->values(implode(',',$values));
            }
            $db->setQuery($q);$db->execute();
            if($id===0)$id=(int)$db->insertid();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_ZONE_SAVED'));
            $embedded=$post->getCmd('return_to','')==='deliverymethods';
            if($embedded){
                $base='index.php?option=com_minimarket&view=deliverymethods&method=courier&tab=zones';
                $this->setRedirect($post->getInt('save_close',0)?$base:$base.'&zone_id='.$id);
            }else{
                $this->setRedirect($post->getInt('save_close',0)
                    ?'index.php?option=com_minimarket&view=deliveryzones'
                    :'index.php?option=com_minimarket&view=deliveryzone&id='.$id);
            }
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_ZONE_INVALID'),'warning');
            if($post->getCmd('return_to','')==='deliverymethods'){
                $base='index.php?option=com_minimarket&view=deliverymethods&method=courier&tab=zones';
                $this->setRedirect($base.($id>0?'&zone_id='.$id:'&zone_new=1'));
            }else{$this->setRedirect($back);}
        }
    }
}