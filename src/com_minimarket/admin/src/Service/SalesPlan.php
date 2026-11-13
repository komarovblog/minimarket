<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Pure business policy for monthly goods targets in RUB cents. */
final class SalesPlan
{
    public static function period(int $year,int $month):array
    {
        if($year<2020||$year>2100||$month<1||$month>12)
            throw new \DomainException('COM_MINIMARKET_PLANFACT_REPORT_INVALID');
        $start=sprintf('%04d-%02d-01 00:00:00',$year,$month);
        $last=$month===12?sprintf('%04d-01-01 00:00:00',$year+1)
            :sprintf('%04d-%02d-01 00:00:00',$year,$month+1);
        return [$start,$last];
    }

    /** Returns integer cents. Floats never represent money. */
    public static function cents(string $raw):int
    {
        $raw=trim($raw);
        if(!preg_match('/^(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,2}))?$/D',$raw,$m))
            throw new \DomainException('COM_MINIMARKET_PLANFACT_REPORT_INVALID');
        $amount=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        if($amount<1||$amount>99999999999999)
            throw new \DomainException('COM_MINIMARKET_PLANFACT_REPORT_INVALID');
        return $amount;
    }

    /** A percentage with one decimal, without a float money conversion. */
    public static function percentTenths(int $actual,int $plan):?int
    {
        if($plan<1)return null;
        // Limit beyond extreme data so multiplication by 1000 stays in range.
        if($actual>intdiv(PHP_INT_MAX,1000))return null;
        return intdiv($actual*1000+intdiv($plan,2),$plan);
    }

    public static function save(DatabaseInterface $db,int $year,int $month,int $target,int $actor):bool
    {
        self::period($year,$month);
        if($target<1||$actor<1)throw new \DomainException('COM_MINIMARKET_PLANFACT_REPORT_INVALID');
        $table=$db->quoteName('#__minimarket_sales_plans');
        $db->transactionStart();
        try {
            // Atomic create-or-lock avoids two concurrent supervisors creating
            // duplicate targets. A no-op update keeps the original target intact.
            $db->setQuery('INSERT INTO '.$table
                .' (period_year,period_month,target_goods_cents,updated_by,updated_at)'
                .' VALUES('.$year.','.$month.','.$target.','.$actor.',UTC_TIMESTAMP())'
                .' ON DUPLICATE KEY UPDATE target_goods_cents=target_goods_cents');
            $db->execute();
            $created=$db->getAffectedRows()===1;
            $db->setQuery('SELECT target_goods_cents FROM '.$table
                .' WHERE period_year='.$year.' AND period_month='.$month.' FOR UPDATE');
            $previous=(int)$db->loadResult();
            if(!$created&&$previous===$target){
                $db->transactionCommit();
                return false;
            }
            if(!$created){
                $db->setQuery('UPDATE '.$table.' SET target_goods_cents='.$target
                    .',updated_by='.$actor.',updated_at=UTC_TIMESTAMP()'
                    .' WHERE period_year='.$year.' AND period_month='.$month);
                $db->execute();
            }
            $old=$created?'NULL':(string)$previous;
            $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_sales_plan_revisions')
                .' (period_year,period_month,previous_target_cents,target_goods_cents,actor_user_id,created_at)'
                .' VALUES('.$year.','.$month.','.$old.','.$target.','.$actor.',UTC_TIMESTAMP())');
            $db->execute();
            $db->transactionCommit();
            return true;
        }catch(\Throwable $e){
            $db->transactionRollback();
            throw $e;
        }
    }
}
