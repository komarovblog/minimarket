<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
final class CollectionsModel extends ListModel
{
    protected function getListQuery()
    {
        $db=$this->getDatabase();
        $q=$db->getQuery(true)->select(['t.id','t.title','t.alias','t.image','t.state','t.sort_order',
            '(SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_product_collections')
                .' AS p WHERE p.collection_id=t.id) AS product_count'
        ])->from($db->quoteName('#__minimarket_collections','t'));
        $search=trim((string)$this->getState('filter.search'));
        if ($search!=='') {
            $like=$db->quote('%'.$db->escape($search,true).'%',false);
            $q->where('(t.title LIKE '.$like.' OR t.alias LIKE '.$like
                .(ctype_digit($search)?' OR t.id='.(int)$search:'').')');
        }
        $state=(string)$this->getState('filter.published');
        if (in_array($state,['0','1'],true)) $q->where('t.state='.(int)$state);
        $ordering=(string)$this->getState('list.ordering','t.sort_order').' '
            .(string)$this->getState('list.direction','ASC');
        $allowed=['t.sort_order ASC','t.title ASC','t.title DESC','t.id ASC','t.id DESC'];
        $q->order(in_array($ordering,$allowed,true)?$ordering:'t.sort_order ASC')
            ->order('t.id ASC');
        return $q;
    }
    protected function populateState($ordering='t.sort_order',$direction='ASC')
    { parent::populateState($ordering,$direction); }
}
