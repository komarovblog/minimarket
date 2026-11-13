<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

/** Only effective attributes of this category; no foreign dictionary values. */
final class RecommendattributesField extends ListField
{
    protected $type='Recommendattributes';
    protected function getOptions()
    {
        $options=parent::getOptions();
        $cat=(int)($this->form?->getValue('id')??0);
        if($cat<1)return $options;
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $h=CategoryAttributes::loadHierarchy($db);
        $effective=CategoryAttributes::resolve($cat,$h['parents'],$h['rules']);
        if(!$effective)return $options;
        $db->setQuery($db->getQuery(true)->select(['id','title'])
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where('id IN ('.implode(',',array_map('intval',array_keys($effective))).')')
            ->order('title ASC,id ASC'));
        foreach((array)$db->loadObjectList() as $row)
            $options[]=HTMLHelper::_('select.option',(int)$row->id,(string)$row->title);
        return $options;
    }
}
