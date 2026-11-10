<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Insert-only review storage; existing published reviews are not overwritten. */
final class ReviewStorage
{
    public static function create(DatabaseInterface $db,int $productId,array $data,
        int $userId=0,int $createdBy=0,string $source='site',int $state=0):int
    {
        if($productId<1||$userId<0||$createdBy<0||!in_array($source,['site','manual'],true)
           ||!in_array($state,[0,1,2],true)) {
            throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
        }
        $query=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_products'))
            ->where('id='.(int)$productId)->where('state=1');
        $db->setQuery($query);
        if((int)$db->loadResult()!==$productId) {
            throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
        }
        $columns=['review_key','product_id','user_id','created_by','author_name','rating',
           'title','body','photo_path','video_url','source','state','created_at'];
        $values=[$db->quote(bin2hex(random_bytes(16))),(string)$productId,
           (string)$userId,(string)$createdBy,$db->quote($data['author_name']),
           (string)$data['rating'],$db->quote($data['title']),$db->quote($data['body']),
           $db->quote($data['photo_path']),$db->quote($data['video_url']),
           $db->quote($source),(string)$state,$db->quote(gmdate('Y-m-d H:i:s'))];
        $query=$db->getQuery(true)->insert($db->quoteName('#__minimarket_reviews'))
            ->columns($db->quoteName($columns))->values(implode(',',$values));
        $db->setQuery($query);$db->execute();
        return (int)$db->insertid();
    }
}
