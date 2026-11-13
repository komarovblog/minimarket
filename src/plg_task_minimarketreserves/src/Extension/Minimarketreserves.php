<?php
namespace MiniMarket\Plugin\Task\Minimarketreserves\Extension;
\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MiniMarket\Component\Minimarket\Site\Service\ReservationMonitor;

final class Minimarketreserves extends CMSPlugin implements SubscriberInterface,DatabaseAwareInterface
{
    use DatabaseAwareTrait;
    use TaskPluginTrait;

    protected const TASKS_MAP=[
        'minimarket.pickup_reservation_monitor'=>[
            'langConstPrefix'=>'PLG_TASK_MINIMARKETRESERVES_MONITOR',
            'method'=>'runMonitor'
        ]
    ];
    protected $autoloadLanguage=true;

    public static function getSubscribedEvents():array
    {
        return [
            'onTaskOptionsList'=>'advertiseRoutines',
            'onExecuteTask'=>'standardRoutineHandler',
        ];
    }

    private function runMonitor(ExecuteTaskEvent $event):int
    {
        try{
            // The component owns the service and its schema. Scheduler plugin
            // never contains a duplicate copy of the order / stock algorithm.
            $this->getApplication()->bootComponent('com_minimarket');
            if(!class_exists(ReservationMonitor::class))return Status::NO_RUN;
            $result=ReservationMonitor::run($this->getDatabase(),75);
            $this->logTask('MiniMarket pickup reservations checked: '
                .json_encode($result,JSON_UNESCAPED_UNICODE), 'info');
            return Status::OK;
        }catch(\Throwable $error){
            $this->logTask('MiniMarket pickup reservation monitor failed: '
                .get_class($error), 'error');
            return Status::KNOCKOUT;
        }
    }
}
