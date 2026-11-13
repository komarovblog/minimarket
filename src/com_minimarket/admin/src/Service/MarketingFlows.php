<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class MarketingFlows
{
    public const TRIGGERS=[
        'cart_abandoned'=>'COM_MINIMARKET_FLOW_CART_ABANDONED',
        'order_delivered'=>'COM_MINIMARKET_FLOW_ORDER_DELIVERED',
        'order_created'=>'COM_MINIMARKET_FLOW_ORDER_CREATED',
    ];
    public const MAX_STEPS=10;
    public const MAX_DELAY_MINUTES=43200; // 30 days

    public static function normalize(array $data):array
    {
        $title=trim((string)($data['title']??''));
        $trigger=(string)($data['trigger_code']??'');
        $stop=(string)($data['stop_on_order']??'1');
        $steps=$data['steps']??[];
        if($title===''||mb_strlen($title)>160
          ||!isset(self::TRIGGERS[$trigger])||!in_array($stop,['0','1'],true)
          ||!is_array($steps)||count($steps)<1||count($steps)>self::MAX_STEPS)
            throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
        $valid=[];$last=-1;
        foreach($steps as $step){
            if(!is_array($step))
                throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
            $delay=$step['delay_minutes']??null;
            $id=$step['template_id']??null;
            if(!is_scalar($delay)||!preg_match('/^(0|[1-9][0-9]{0,4})$/D',(string)$delay)
              ||(int)$delay>self::MAX_DELAY_MINUTES
              ||!is_scalar($id)||!preg_match('/^[1-9][0-9]{0,9}$/D',(string)$id))
                throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
            $delay=(int)$delay;$id=(int)$id;
            // Delays are elapsed since triggering event, must strictly increase.
            if($delay<=$last||$id<1)
                throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
            $valid[]=['delay_minutes'=>$delay,'template_id'=>$id];
            $last=$delay;
        }
        return ['title'=>$title,'trigger_code'=>$trigger,
            'stop_on_order'=>(int)$stop,'state'=>'draft','steps'=>$valid];
    }

    /** Atomic draft save. Deliberately no publishing/sending until consent & worker exist. */
    public static function save(DatabaseInterface $db,int $flowId,int $actor,array $data):int
    {
        if($actor<1||$flowId<0)throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
        $data=self::normalize($data);
        $db->transactionStart();
        try{
            if($flowId){
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_marketing_flows')
                    .' WHERE id='.$flowId.' FOR UPDATE');
                if((int)$db->loadResult()!==$flowId)
                    throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
            }
            $ids=array_unique(array_column($data['steps'],'template_id'));
            $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_message_templates')
                .' WHERE audience='.$db->quote('marketing')
                .' AND channel_code='.$db->quote('email')
                .' AND state=1 AND event_code='.$db->quote($data['trigger_code'])
                .' AND id IN ('.implode(',',array_map('intval',$ids)).')');
            $found=array_map('intval',(array)$db->loadColumn());
            if(count($found)!==count($ids))
                throw new \DomainException('COM_MINIMARKET_FLOW_INVALID');
            $table=$db->quoteName('#__minimarket_marketing_flows');
            if($flowId){
                $db->setQuery('UPDATE '.$table.' SET title='.$db->quote($data['title'])
                    .',trigger_code='.$db->quote($data['trigger_code'])
                    .',stop_on_order='.$data['stop_on_order']
                    .",state='draft',updated_at=UTC_TIMESTAMP() WHERE id=".$flowId);
            }else{
                $db->setQuery('INSERT INTO '.$table
                    .' (title,trigger_code,state,stop_on_order,created_by,created_at)'
                    .' VALUES('.$db->quote($data['title']).','.$db->quote($data['trigger_code'])
                    .",'draft',".$data['stop_on_order'].','.$actor.',UTC_TIMESTAMP())');
            }
            $db->execute();
            if(!$flowId)$flowId=(int)$db->insertid();
            $stepTable=$db->quoteName('#__minimarket_marketing_flow_steps');
            $db->setQuery('DELETE FROM '.$stepTable.' WHERE flow_id='.$flowId);
            $db->execute();
            foreach($data['steps'] as $i=>$step){
                $db->setQuery('INSERT INTO '.$stepTable
                    .' (flow_id,step_no,delay_minutes,template_id) VALUES ('.$flowId.','
                    .($i+1).','.$step['delay_minutes'].','.$step['template_id'].')');
                $db->execute();
            }
            $db->transactionCommit();
            return $flowId;
        }catch(\Throwable $e){$db->transactionRollback();throw $e;}
    }
}
