<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

/** Native select: whole product or an exact physical variant/SKU. */
final class BundlecomponentField extends ListField
{
    protected $type='Bundlecomponent';

    protected function getOptions()
    {
        $options=parent::getOptions();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title','sku','state','product_type'])
            ->from($db->quoteName('#__minimarket_products'))->order('title ASC,id ASC');
        $db->setQuery($q);
        $products=(array)$db->loadAssocList();
        $variantsByProduct=[]; $allVariants=[];
        $q=$db->getQuery(true)->select(['id','product_id','sku','state','stock'])
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->order('product_id ASC,sort_order ASC,id ASC');
        $db->setQuery($q);
        foreach((array)$db->loadAssocList() as $v) {
            $variantsByProduct[(int)$v['product_id']][]=$v;
            $allVariants[(int)$v['product_id']]=true;
        }
        foreach($products as $p) {
            $id=(int)$p['id'];
            $suffix=(int)$p['state']===1?'':' [не опубликован]';
            if ($p['product_type']==='bundle') {
                $options[]=HTMLHelper::_('select.option','product:'.$id,
                    'Комплект: '.$p['title'].' (#'.$id.')'.$suffix);
            } elseif (!isset($allVariants[$id])) {
                $options[]=HTMLHelper::_('select.option','product:'.$id,
                    'Товар: '.$p['title'].' — '.$p['sku'].' (#'.$id.')'.$suffix);
            }
            foreach($variantsByProduct[$id]??[] as $v) {
                $options[]=HTMLHelper::_('select.option','variant:'.(int)$v['id'],
                    'SKU: '.$p['title'].' — '.$v['sku'].' ('.(int)$v['stock'].' шт.)'.
                    ((int)$v['state']===1?'':' [неактивен]'));
            }
        }
        return $options;
    }
}
