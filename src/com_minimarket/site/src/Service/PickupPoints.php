<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;
final class PickupPoints
{
    public static function all(DatabaseInterface $db,bool $active=true):array
    {
        $q=$db->getQuery(true)->select(['id','title','address','schedule','map_url',
            'fee_cents','state','ordering'])->from($db->quoteName('#__minimarket_pickup_points'));
        if($active)$q->where('state=1');
        $q->order('ordering ASC,title ASC,id ASC');
        $db->setQuery($q,0,500);
        return (array)$db->loadObjectList();
    }

    public static function select(array $points,int $id):?object
    {
        if(!$points)return null;
        if($id===0&&count($points)===1)return $points[0];
        foreach($points as $point)if((int)$point->id===$id&&(int)$point->state===1)return $point;
        throw new \DomainException('COM_MINIMARKET_PICKUP_POINT_REQUIRED');
    }

    public static function validate(array $input):array
    {
        $title=trim(strip_tags((string)($input['title']??'')));
        $address=trim(strip_tags((string)($input['address']??'')));
        $schedule=trim(strip_tags((string)($input['schedule']??'')));
        $map=trim((string)($input['map_url']??''));
        $fee=trim((string)($input['fee']??''));
        $state=(string)($input['state']??'1');
        if(mb_strlen($title)<2||mb_strlen($title)>150
            ||mb_strlen($address)<5||mb_strlen($address)>512||mb_strlen($schedule)>1000
            ||!in_array($state,['0','1'],true)
            ||preg_match('/[\x00-\x1f<>]/u',$title.$address.$schedule)
            ||!preg_match('/^(0|[1-9][0-9]{0,8})(?:[.,]([0-9]{1,2}))?$/D',$fee,$m)
            ||($map!==''&&(strlen($map)>1024||!filter_var($map,FILTER_VALIDATE_URL)
                ||strtolower((string)parse_url($map,PHP_URL_SCHEME))!=='https'
                ||parse_url($map,PHP_URL_USER)!==null||parse_url($map,PHP_URL_PASS)!==null)))
            throw new \DomainException('COM_MINIMARKET_PICKUP_POINT_INVALID');
        return ['title'=>$title,'address'=>$address,'schedule'=>$schedule,
            'map_url'=>$map,'fee_cents'=>(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0'),
            'state'=>(int)$state];
    }
}
