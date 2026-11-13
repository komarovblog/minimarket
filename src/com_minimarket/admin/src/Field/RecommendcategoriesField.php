<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

/** Native Joomla multiple category selector, hierarchically labelled. */
final class RecommendcategoriesField extends ListField
{
    protected $type='Recommendcategories';
    protected function getOptions()
    {
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->getQuery(true)
            ->select(['id','parent_id','title','alias','sort_order','state'])
            ->from($db->quoteName('#__minimarket_categories')));
        $options=parent::getOptions();
        foreach(CategoryAdminTree::flatten((array)$db->loadObjectList()) as $row){
            $label=str_repeat('— ',min(20,(int)$row['depth']))
                .(string)$row['item']->title;
            $options[]=HTMLHelper::_('select.option',(int)$row['item']->id,$label);
        }
        return $options;
    }
}
