<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** MySQL-backed feed definitions, isolated from Joomla core data. */
final class FeedConfig
{
    public const PROVIDERS = [
        'yandex_direct' => 'COM_MINIMARKET_FEED_YANDEX_DIRECT',
        'yandex_market' => 'COM_MINIMARKET_FEED_YANDEX_MARKET',
        'yandex_business' => 'COM_MINIMARKET_FEED_YANDEX_BUSINESS',
        'vk_yml' => 'COM_MINIMARKET_FEED_VK',
    ];

    private static function table(DatabaseInterface $db): string
    {
        return $db->quoteName('#__minimarket_feeds');
    }

    public static function all(DatabaseInterface $db): array
    {
        $db->setQuery('SELECT * FROM '.self::table($db).' ORDER BY id DESC');
        return (array)$db->loadAssocList();
    }

    public static function find(DatabaseInterface $db, int $id): ?array
    {
        if ($id<1) return null;
        $db->setQuery('SELECT * FROM '.self::table($db).' WHERE id='.$id);
        $row=$db->loadAssoc();
        return $row ? (array)$row : null;
    }

    public static function catalog(DatabaseInterface $db): array
    {
        $db->setQuery('SELECT id,parent_id,title,state,sort_order FROM '
            .$db->quoteName('#__minimarket_categories')
            .' ORDER BY sort_order ASC,id ASC');
        $categories=(array)$db->loadAssocList();
        $db->setQuery('SELECT id,category_id,title,sku,product_type,state,stock,option_stock_mode FROM '
            .$db->quoteName('#__minimarket_products')
            .' ORDER BY category_id ASC,title ASC,id ASC');
        return [$categories,(array)$db->loadAssocList()];
    }

    public static function settings(?array $feed): array
    {
        if (!$feed) return [
            'category_ids'=>[], 'include_product_ids'=>[], 'exclude_product_ids'=>[],
            'selection_mode'=>'auto','include_descendants'=>1,'include_bundles'=>1
        ];
        $result=$feed;
        foreach(['category_ids','include_product_ids','exclude_product_ids'] as $field){
            $value=json_decode((string)($feed[$field.'_json']??'[]'),true);
            $result[$field]=FeedSelection::ids($value);
        }
        return $result;
    }

    public static function save(DatabaseInterface $db, array $input, array $categories, array $products): int
    {
        $id=(int)($input['id']??0);
        $existing=$id?self::find($db,$id):null;
        if($id && !$existing) throw new \InvalidArgumentException('COM_MINIMARKET_FEED_NOT_FOUND');
        $name=trim((string)($input['title']??''));
        $company=trim((string)($input['company']??''));
        if($name===''||mb_strlen($name)>200||mb_strlen($company)>200) {
            throw new \InvalidArgumentException('COM_MINIMARKET_FEED_INVALID');
        }
        $provider=(string)($input['provider']??'yandex_direct');
        if(!isset(self::PROVIDERS[$provider])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_FEED_INVALID');
        }
        $selected=FeedSelection::ids($input['categories']??[]);
        $visible=array_fill_keys(FeedSelection::visibleCategories($categories),true);
        $selected=array_values(array_filter($selected,static fn(int $id)=>isset($visible[$id])));
        $mode=(string)($input['selection_mode']??'auto');
        if(!in_array($mode,['auto','manual'],true)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_FEED_INVALID');
        }
        $descendants=(int)($input['include_descendants']??0)===1?1:0;
        $bundles=(int)($input['include_bundles']??0)===1?1:0;
        $checked=FeedSelection::ids($input['products']??[]);
        $present=array_fill_keys(array_map(
            static fn($p)=>(int)((array)$p)['id'],$products),true);
        $checked=array_values(array_filter($checked,static fn(int $pid)=>isset($present[$pid])));
        if ($mode==='manual') {
            $included=$checked;
            $excluded=[];
        } else {
            $defaults=FeedSelection::productIds($categories,$products,[
                'category_ids'=>$selected,'include_descendants'=>$descendants,
                'include_bundles'=>$bundles,'selection_mode'=>'auto'
            ]);
            $included=array_values(array_diff($checked,$defaults));
            $excluded=array_values(array_diff($defaults,$checked));
        }
        $state=(int)($input['state']??0)===1?1:0;
        $key=$existing ? (string)$existing['access_key'] : bin2hex(random_bytes(20));
        $values=[
            'title'=>$name,'company'=>$company,'provider'=>$provider,'state'=>$state,
            'selection_mode'=>$mode,'include_descendants'=>$descendants,
            'include_bundles'=>$bundles,'category_ids_json'=>json_encode($selected),
            'include_product_ids_json'=>json_encode($included),
            'exclude_product_ids_json'=>json_encode($excluded),
            'access_key'=>$key,
        ];
        $columns=array_keys($values);
        $names=implode(',',array_map([$db,'quoteName'],$columns));
        $data=implode(',',array_map(static fn($value)=>is_int($value)
            ?(string)$value:$db->quote((string)$value),array_values($values)));
        if ($id) {
            $updates=[];
            foreach($values as $column=>$value) {
                $updates[]=$db->quoteName($column).'='.
                    (is_int($value)?(string)$value:$db->quote((string)$value));
            }
            $db->setQuery('UPDATE '.self::table($db).' SET '.implode(',',$updates)
                .',updated_at=UTC_TIMESTAMP() WHERE id='.$id);
        } else {
            $db->setQuery('INSERT INTO '.self::table($db).' ('.$names
                .',created_at,updated_at) VALUES ('.$data.',UTC_TIMESTAMP(),UTC_TIMESTAMP())');
        }
        $db->execute();
        return $id ?: (int)$db->insertid();
    }

    public static function delete(DatabaseInterface $db,int $id):void
    {
        if ($id<1) return;
        $db->setQuery('DELETE FROM '.self::table($db).' WHERE id='.$id);
        $db->execute();
    }
}
