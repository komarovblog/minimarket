<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Bounded UTC date range, shared by all financial reports. */
final class CommerceReportRange
{
    public static function normalize(string $from,string $to):array
    {
        $utc=new \DateTimeZone('UTC');
        if($from==='')$from=gmdate('Y-m-d',time()-90*86400);
        if($to==='')$to=gmdate('Y-m-d');
        $start=\DateTimeImmutable::createFromFormat('!Y-m-d',$from,$utc);
        $end=\DateTimeImmutable::createFromFormat('!Y-m-d',$to,$utc);
        if(!$start||!$end||$start->format('Y-m-d')!==$from
            ||$end->format('Y-m-d')!==$to
            ||$end<$start||$end->getTimestamp()-$start->getTimestamp()>5*366*86400)
            throw new \DomainException('COM_MINIMARKET_REPORT_PERIOD_INVALID');
        return [$from,$to,$end->modify('+1 day')->format('Y-m-d')];
    }

    public static function where(DatabaseInterface $db,string $alias,string $from,string $toExclusive):string
    {
        if(!in_array($alias,['o','r'],true))
            throw new \InvalidArgumentException('Invalid report entity');
        return $alias.'.created_at>='.$db->quote($from.' 00:00:00')
            .' AND '.$alias.'.created_at<'.$db->quote($toExclusive.' 00:00:00');
    }
}
