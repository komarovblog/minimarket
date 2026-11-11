<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\PromoCodeData;

final class PromocodeController extends BaseController
{
    private function guard(string $acl): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod() !== 'POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        if (!$app->getIdentity()->authorise($acl,'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
    }

    public function create(): void { $this->save(); }

    public function save(): void
    {
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);
        $this->guard($id>0?'core.edit':'core.create');
        $redirect='index.php?option=com_minimarket&view=promocode'.($id>0?'&id='.$id:'&layout=create');
        try {
            $post=$app->input->post;
            $data=PromoCodeData::normalize([
                'code'=>$post->get('code','','string'),
                'discount_type'=>$post->get('discount_type','fixed','cmd'),
                'amount'=>$post->get('amount','0','string'),
                'percent_value'=>$post->get('percent_value','0','string'),
                'starts_at'=>$post->get('starts_at','','string'),
                'ends_at'=>$post->get('ends_at','','string'),
                'min_cart'=>$post->get('min_cart','0','string'),
                'usage_limit'=>$post->get('usage_limit','','string'),
                'per_customer_limit'=>$post->get('per_customer_limit','','string'),
                'applies_to'=>$post->get('applies_to','all','cmd'),
                'target_ids'=>$post->get('target_ids','','string'),
                'combinable'=>$post->getInt('combinable',0)
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            // Do not accept arbitrary IDs as scope targets.
            if ($data['applies_to'] !== 'all') {
                $ids=json_decode($data['target_ids_json'],true,512,JSON_THROW_ON_ERROR);
                $table=$data['applies_to']==='product'?'#__minimarket_products':'#__minimarket_categories';
                $q=$db->getQuery(true)->select('COUNT(DISTINCT id)')->from($db->quoteName($table))
                    ->where('id IN ('.implode(',',array_map('intval',$ids)).')');
                $db->setQuery($q);
                if ((int)$db->loadResult() !== count($ids)) {
                    throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
                }
            }
            $quote=static fn($value) => $value===null?'NULL':$db->quote((string)$value);
            $columns=array_keys($data);
            if ($id>0) {
                $db->setQuery($db->getQuery(true)->select('id')
                    ->from($db->quoteName('#__minimarket_promo_codes'))->where('id='.(int)$id));
                if ((int)$db->loadResult() !== $id) {
                    throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
                }
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_promo_codes'));
                foreach($columns as $column) {
                    $q->set($db->quoteName($column).'='.$quote($data[$column]));
                }
                $q->set('updated_at=UTC_TIMESTAMP()')->where('id='.(int)$id);
            } else {
                $columns[]='state';$columns[]='created_by';$columns[]='created_at';
                $values=array_map($quote,array_values($data));
                $values[]='0';$values[]=(string)(int)$app->getIdentity()->id;$values[]='UTC_TIMESTAMP()';
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_promo_codes'))
                    ->columns($db->quoteName($columns))->values(implode(',',$values));
            }
            $db->setQuery($q);$db->execute();
            if ($id===0)$id=(int)$db->insertid();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_SAVED'));
            $this->setRedirect($post->getInt('save_close',0)===1
                ?'index.php?option=com_minimarket&view=promocodes'
                :'index.php?option=com_minimarket&view=promocode&id='.$id);
        } catch (\DomainException $e) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),'warning');
            $this->setRedirect($redirect);
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),'error');
            $this->setRedirect($redirect);
        }
    }

    public function state(): void
    {
        $this->guard('core.edit.state');
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);
        $state=$app->input->post->getInt('state',-1);
        if ($id<1 || !in_array($state,[0,1],true)) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_PROMOCODES_INVALID'),400);
        }
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_promo_codes'))
            ->set('state='.(int)$state)->where('id='.(int)$id);
        $db->setQuery($q);$db->execute();
        $app->enqueueMessage(Text::_('COM_MINIMARKET_PROMOCODES_SAVED'));
        $this->setRedirect('index.php?option=com_minimarket&view=promocodes');
    }
}
