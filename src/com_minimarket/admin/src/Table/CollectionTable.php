<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;
\defined('_JEXEC') or die;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MediaPath;
final class CollectionTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_collections','id',$db);
        $this->setColumnAlias('published','state');
    }
    public function check()
    {
        $this->title=trim((string)($this->title??''));
        $this->alias=OutputFilter::stringURLSafe(trim((string)($this->alias??''))?:$this->title);
        $order=trim((string)($this->sort_order??'0'));
        $this->state=(int)($this->state??0);
        if ($this->title==='' || mb_strlen($this->title)>255
            || preg_match('/[\x00-\x1f<>]/u',$this->title)
            || $this->alias==='' || mb_strlen($this->alias)>255
            || !preg_match('/^[a-z0-9][a-z0-9-]*$/D',$this->alias)
            || !in_array($this->state,[0,1],true)
            || !preg_match('/^(0|[1-9][0-9]*)$/D',$order)
            || strlen($order)>10
            || (strlen($order)===10 && strcmp($order,'4294967295')>0)) {
            $this->setError(Text::_('COM_MINIMARKET_COLLECTION_INVALID')); return false;
        }
        $this->sort_order=(int)$order;
        $db=$this->getDbo();
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_collections'))
            ->where('alias='.$db->quote($this->alias))
            ->where('id <> '.(int)($this->id??0));
        $db->setQuery($q);
        if ((int)$db->loadResult()>0) {
            $this->setError(Text::_('COM_MINIMARKET_COLLECTION_DUPLICATE')); return false;
        }
        foreach (['image','og_image'] as $field) {
            $value=MediaPath::normalize((string)($this->$field??''));
            if ($value===null) { $this->setError(Text::_('COM_MINIMARKET_COLLECTION_INVALID')); return false; }
            $this->$field=$value;
        }
        foreach (['image_alt'=>255,'og_image_alt'=>255,'meta_title'=>255,
            'meta_description'=>320,'og_title'=>255,'og_description'=>320] as $field=>$limit) {
            $value=trim((string)($this->$field??''));
            if (mb_strlen($value)>$limit || preg_match('/[\x00-\x1f<>]/u',$value)) {
                $this->setError(Text::_('COM_MINIMARKET_COLLECTION_INVALID')); return false;
            }
            $this->$field=$value;
        }
        $this->meta_robots=trim((string)($this->meta_robots??''));
        if (!in_array($this->meta_robots,['','index,follow','noindex,follow','noindex,nofollow'],true)) {
            $this->setError(Text::_('COM_MINIMARKET_COLLECTION_INVALID')); return false;
        }
        
        return parent::check();
    }
}
