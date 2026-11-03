<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Local SKU axes and value IDs, written inside ProductModel's transaction.
 * Global category attributes are deliberately not consulted.
 */
final class ProductLocalDimensions
{
    public static function load(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $q=$db->getQuery(true)->select(['id','code','title'])
            ->from($db->quoteName('#__minimarket_variant_dimensions'))
            ->where($db->quoteName('product_id').' = '.$productId)
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $rows=(array)$db->loadAssocList();
        if (!$rows) return [];
        $ids=array_map('intval',array_column($rows,'id'));
        $q=$db->getQuery(true)->select(['dimension_id','code','title'])
            ->from($db->quoteName('#__minimarket_variant_dimension_values'))
            ->where($db->quoteName('dimension_id').' IN ('.implode(',',$ids).')')
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $valueRows=(array)$db->loadAssocList();
        $photos=VariantValueGallery::load($db,array_column($valueRows,'id'));
        $values=[];
        foreach ($valueRows as $r) {
            $entry=['code'=>(string)$r['code'],'title'=>(string)$r['title']];
            $entry['photos']=$photos[(int)$r['id']]??[];
            $values[(int)$r['dimension_id']][]=$entry;
        }
        $result=[];
        foreach ($rows as $row) {
            $result[]=['code'=>(string)$row['code'],'title'=>(string)$row['title'],
                'values'=>$values[(int)$row['id']]??[]];
        }
        return $result;
    }

    /**
     * Native Joomla nested Subform data. Return row-keyed arrays so + / remove /
     * move works exactly as in the existing gallery.
     */
    public static function formRows(DatabaseInterface $db,int $productId): array
    {
        $axes=self::load($db,$productId);
        $rows=[];
        foreach ($axes as $i=>$axis) {
            $valueRows=[];
            foreach ($axis['values'] as $j=>$value) {
                $entry=$value;
                $entry['photos']=[];
                foreach ($value['photos']??[] as $photoIndex=>$photo) {
                    $entry['photos']['row'.$photoIndex]=$photo;
                }
                $valueRows['row'.$j]=$entry;
            }
            $rows['row'.$i]=[
                'code'=>$axis['code'], 'title'=>$axis['title'], 'values'=>$valueRows
            ];
        }
        return $rows;
    }

    public static function hasLegacyVariants(DatabaseInterface $db,int $productId): bool
    {
        if ($productId < 1) return false;
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('product_id').' = '.$productId);
        $db->setQuery($q);
        return (int)$db->loadResult()>0 && self::load($db,$productId)===[];
    }

    public static function axesForVariants(array $dimensions): array
    {
        $axes=[];
        foreach ($dimensions as $axis) {
            $options=[];
            foreach ($axis['values'] as $value) {
                $options[]=['value'=>'v:'.$value['code'],'title'=>$value['title'],
                    'image'=>'','hex'=>''];
            }
            $axes[$axis['code']]=['title'=>$axis['title'],'options'=>$options];
        }
        return $axes;
    }

    /**
     * $variantIds matches normalized rows by positional index, including newly
     * inserted IDs returned by ProductVariants::replace.
     * Existing dimension/value IDs are maintained by stable code.
     */
    public static function replace(DatabaseInterface $db,int $productId,array $dimensions,array $variantRows,array $variantIds): void
    {
        if ($productId<1 || count($variantRows)!==count($variantIds)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
        }
        // Remove assignments of only this product before changing values.
        $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_variant_value_assignments'))
            ->where($db->quoteName('variant_id').' IN (SELECT id FROM '.
                $db->quoteName('#__minimarket_product_variants').' WHERE product_id = '.$productId.')');
        $db->setQuery($q);$db->execute();

        $q=$db->getQuery(true)->select(['id','code'])
            ->from($db->quoteName('#__minimarket_variant_dimensions'))
            ->where($db->quoteName('product_id').' = '.$productId);
        $db->setQuery($q);
        $existing=[];
        foreach ((array)$db->loadAssocList() as $r) $existing[$r['code']]=(int)$r['id'];
        $wanted=[];
        $axisIds=[];
        $valueIds=[];
        foreach ($dimensions as $i=>$axis) {
            $code=$axis['code'];$wanted[$code]=true;
            if (isset($existing[$code])) {
                $id=$existing[$code];
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_variant_dimensions'))
                    ->set($db->quoteName('title').' = '.$db->quote($axis['title']))
                    ->set($db->quoteName('sort_order').' = '.(int)$i)
                    ->where($db->quoteName('id').' = '.$id)
                    ->where($db->quoteName('product_id').' = '.$productId);
            } else {
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_variant_dimensions'))
                    ->columns($db->quoteName(['product_id','code','title','sort_order']))
                    ->values(implode(',',[$productId,$db->quote($code),$db->quote($axis['title']),(int)$i]));
            }
            $db->setQuery($q);$db->execute();
            if (!isset($existing[$code])) $id=(int)$db->insertid();
            if ($id<1) throw new \RuntimeException('COM_MINIMARKET_VARIANTS_SAVE_FAILED');
            $axisIds[$code]=$id;
            $q=$db->getQuery(true)->select(['id','code'])
                ->from($db->quoteName('#__minimarket_variant_dimension_values'))
                ->where($db->quoteName('dimension_id').' = '.$id);
            $db->setQuery($q);
            $oldValues=[];
            foreach ((array)$db->loadAssocList() as $row) $oldValues[$row['code']]=(int)$row['id'];
            $keepValues=[];
            foreach ($axis['values'] as $j=>$value) {
                $vcode=$value['code'];$keepValues[$vcode]=true;
                if (isset($oldValues[$vcode])) {
                    $valueId=$oldValues[$vcode];
                    $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_variant_dimension_values'))
                        ->set($db->quoteName('title').' = '.$db->quote($value['title']))
                        ->set($db->quoteName('sort_order').' = '.(int)$j)
                        ->where($db->quoteName('id').' = '.$valueId)
                        ->where($db->quoteName('dimension_id').' = '.$id);
                } else {
                    $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_variant_dimension_values'))
                        ->columns($db->quoteName(['dimension_id','code','title','sort_order']))
                        ->values(implode(',',[$id,$db->quote($vcode),$db->quote($value['title']),(int)$j]));
                }
                $db->setQuery($q);$db->execute();
                if (!isset($oldValues[$vcode])) $valueId=(int)$db->insertid();
                if ($valueId<1) throw new \RuntimeException('COM_MINIMARKET_VARIANTS_SAVE_FAILED');
                $valueIds[$code][$vcode]=$valueId;
                // Explicit photo rows (including an empty subform) replace the
                // gallery; missing photos leaves any existing links untouched.
                if (array_key_exists('photos',$value)) {
                    VariantValueGallery::replace($db,$valueId,$value['photos']);
                }
            }
            foreach ($oldValues as $vcode=>$valueId) {
                if (isset($keepValues[$vcode])) continue;
                $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_variant_dimension_values'))
                    ->where($db->quoteName('id').' = '.$valueId)
                    ->where($db->quoteName('dimension_id').' = '.$id);
                $db->setQuery($q);$db->execute();
            }
        }
        foreach ($existing as $code=>$id) {
            if (isset($wanted[$code])) continue;
            // Remaining values must be removed before the dimension.
            $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_variant_dimension_values'))
                ->where($db->quoteName('dimension_id').' = '.$id);
            $db->setQuery($q);$db->execute();
            $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_variant_dimensions'))
                ->where($db->quoteName('id').' = '.$id)
                ->where($db->quoteName('product_id').' = '.$productId);
            $db->setQuery($q);$db->execute();
        }
        foreach ($variantRows as $i=>$variant) {
            $selection=json_decode($variant['selection_json'],true,512,JSON_THROW_ON_ERROR);
            if (!is_array($selection)||count($selection)!==count($axisIds)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_SELECTION_INVALID');
            }
            foreach ($selection as $axisCode=>$value) {
                $valueCode=is_string($value)&&str_starts_with($value,'v:')?substr($value,2):'';
                if (!isset($axisIds[$axisCode],$valueIds[$axisCode][$valueCode])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_SELECTION_INVALID');
                }
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_variant_value_assignments'))
                    ->columns($db->quoteName(['variant_id','dimension_id','value_id']))
                    ->values(implode(',',[(int)$variantIds[$i],$axisIds[$axisCode],$valueIds[$axisCode][$valueCode]]));
                $db->setQuery($q);$db->execute();
            }
        }
    }
}
