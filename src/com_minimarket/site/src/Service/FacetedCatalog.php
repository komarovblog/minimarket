<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Published-only bulk catalog facts. Different facets use AND; choices within
 * one facet use OR. SKU options must coexist on one active SKU combination.
 */
final class FacetedCatalog
{
    private static function query(DatabaseInterface $db, string $table, array $columns,
        string $where = '', string $order = ''): array
    {
        $q = $db->getQuery(true)->select($columns)->from($db->quoteName($table));
        if ($where !== '') $q->where($where);
        if ($order !== '') $q->order($order);
        $db->setQuery($q);
        return (array)$db->loadAssocList();
    }

    private static function ids(array $ids): string
    {
        return implode(',', array_map('intval', $ids));
    }

    private static function key(string $type, string $value): string
    {
        if ($type === 'boolean') return 'b:'.($value === '0' ? '0' : '1');
        if ($type === 'number' && is_numeric($value)) {
            $decimal = rtrim(rtrim(sprintf('%.4F', (float)$value), '0'), '.');
            return 'n:'.($decimal === '-0' || $decimal === '' ? '0' : $decimal);
        }
        return 't:'.$value;
    }

    private static function add(array &$facts, array &$facets, int $productId,
        string $facetId, string $token, string $label, string $hex = ''): void
    {
        if (!isset($facts[$productId],$facets[$facetId])) return;
        $facet =& $facets[$facetId];
        if (in_array($facet['mode'], ['range','slider'], true)) {
            if (!is_numeric($label)) return;
            $number=(float)$label;
            $facts[$productId][$facetId][]=$number;
            $facet['min']=min($facet['min']??$number,$number);
            $facet['max']=max($facet['max']??$number,$number);
        } else {
            $facts[$productId][$facetId][]=$token;
            if ($facet['mode'] !== 'text' && !isset($facet['choices'][$token])) {
                $facet['choices'][$token]=[
                    'value'=>$token,'label'=>$label,
                    'hex'=>preg_match('/^#[a-fA-F0-9]{6}$/D',$hex)?$hex:'',
                    'count'=>0
                ];
            }
        }
        unset($facet);
    }

    /**
     * Product array must already be filtered through CatalogRepository.
     * @return array{facets:array,facts:array,skus:array}
     */
    public static function build(DatabaseInterface $db, array $products,
        bool $inventoryEnabled=true, array $systemFilters=[]): array
    {
        $byId=[];$facts=[];$skus=[];
        foreach ($products as $product) {
            $id=(int)($product->id??0);
            if ($id<1) continue;
            $byId[$id]=$product;
            $facts[$id]=[];$skus[$id]=[];
        }
        if (!$byId) return ['facets'=>[], 'facts'=>[], 'skus'=>[]];
        $productSql=self::ids(array_keys($byId));
        // System facets are configured globally with category overrides.
        $priceMode=(string)($systemFilters['filter_price_mode']??'slider');
        if (!in_array($priceMode,['slider','range'],true)) $priceMode='none';
        $skuMode=(string)($systemFilters['filter_sku_mode']??'none');
        $stockMode=!empty($systemFilters['filter_stock_enabled']);
        $prices=array_values(array_filter(array_map(
            static fn($p):float => (float)($p->price??0),$byId
        ),static fn(float $price):bool=>$price>=0));
        if ($prices && $priceMode!=='none') {
            $low=min($prices);$high=max($prices);
            $priceFacet=['id'=>'price','title'=>'Цена','type'=>'number',
                'mode'=>$priceMode,'unit'=>'','multiple'=>false,'search'=>false,
                'choices'=>[], 'min'=>$low,'max'=>$high];
        }
        $cats=[];$parents=[];
        foreach (self::query($db,'#__minimarket_categories',
            ['id','parent_id','state']) as $cat) {
            $id=(int)$cat['id'];
            $parents[$id]=(int)$cat['parent_id'];
            $cats[$id]=(int)$cat['state']===1;
        }
        $rules=[];
        foreach (self::query($db,'#__minimarket_category_attributes',
            ['category_id','attribute_id','enabled']) as $row) {
            $rules[(int)$row['category_id']][(int)$row['attribute_id']]=(int)$row['enabled']===1;
        }
        $allowed=[];
        foreach ($byId as $id=>$product) {
            $category=(int)($product->category_id??0);$chain=[];$seen=[];
            while ($category>0 && ($cats[$category]??false) && !isset($seen[$category])) {
                $seen[$category]=true;$chain[]=$category;
                $category=$parents[$category]??0;
            }
            if ($category!==0) continue;
            foreach (array_reverse($chain) as $ancestor) {
                foreach ($rules[$ancestor]??[] as $attribute=>$enabled) {
                    $allowed[$id][$attribute]=$enabled;
                }
            }
        }
        $facets=isset($priceFacet)?['price'=>$priceFacet]:[];
        if (isset($priceFacet)) {
            foreach ($byId as $pid=>$product) {
                $facts[$pid]['price']=[(float)($product->price??0)];
            }
        }
        if ($skuMode==='text') {
            $facets['sku']=['id'=>'sku','title'=>'Артикул','type'=>'text',
                'mode'=>'text','unit'=>'','multiple'=>false,'search'=>false,'choices'=>[]];
            foreach ($byId as $pid=>$product) {
                $sku=trim((string)($product->sku??''));
                if ($sku!=='') $facts[$pid]['sku']=[$sku];
            }
        }
        if ($stockMode) {
            $facets['stock']=['id'=>'stock','title'=>'Наличие','type'=>'boolean',
                'mode'=>'dropdown','unit'=>'','multiple'=>false,'search'=>false,
                'choices'=>[
                    'b:1'=>['value'=>'b:1','label'=>'Есть в наличии','hex'=>'','count'=>0],
                    'b:0'=>['value'=>'b:0','label'=>'Нет в наличии','hex'=>'','count'=>0]]];
            foreach ($byId as $pid=>$product) {
                $facts[$pid]['stock']=[(int)($product->stock??0)>0?'b:1':'b:0'];
            }
        }
        $definitions=[];
        foreach (self::query($db,'#__minimarket_attributes',
            ['id','title','input_type','filter_mode','filter_multiple',
             'filter_search','number_unit','is_filterable','state'],
            'state=1 AND is_filterable=1 AND filter_mode<>'.$db->quote('none'),
            'sort_order ASC,id ASC') as $row) {
            $mode=(string)$row['filter_mode'];
            $type=(string)$row['input_type'];
            if (!in_array($mode,['text','checkboxes','radio','dropdown','values','swatches',
                'range','slider','boolean'],true)) continue;
            if (in_array($type,['editor','document','textarea','url'],true)) continue;
            $id=(int)$row['id'];$key='a'.$id;
            $definitions[$id]=$type;
            $facets[$key]=[
                'id'=>$key,'title'=>(string)$row['title'],'type'=>$type,
                'mode'=>$mode,'unit'=>(string)$row['number_unit'],
                'multiple'=>(int)$row['filter_multiple']===1,
                'search'=>(int)($row['filter_search']??0)===1
                    && in_array($type,['text','color'],true)
                    && in_array($mode,['checkboxes','dropdown','values','swatches'],true),
                'choices'=>[]
            ];
        }
        if ($definitions) {
            $attributeSql=self::ids(array_keys($definitions));
            $visible=static fn(int $pid,int $aid):bool=>($allowed[$pid][$aid]??false)===true;
            foreach (self::query($db,'#__minimarket_product_attribute_scalars',
                ['product_id','attribute_id','value_text','value_number','value_boolean'],
                'product_id IN ('.$productSql.') AND attribute_id IN ('.$attributeSql.')') as $row) {
                $pid=(int)$row['product_id'];$aid=(int)$row['attribute_id'];
                if (!$visible($pid,$aid)) continue;
                $value=$row['value_text']??$row['value_number']??$row['value_boolean'];
                if ($value===null || $value==='') continue;
                $value=(string)$value;$type=$definitions[$aid];
                self::add($facts,$facets,$pid,'a'.$aid,self::key($type,$value),
                    $type==='boolean'?($value==='0'?'No':'Yes'):$value);
            }
            foreach (['#__minimarket_product_attribute_free_values'] as $table) {
                foreach (self::query($db,$table,
                    ['product_id','attribute_id','value_text'],
                    'product_id IN ('.$productSql.') AND attribute_id IN ('.$attributeSql.')') as $row) {
                    $pid=(int)$row['product_id'];$aid=(int)$row['attribute_id'];
                    $value=(string)$row['value_text'];
                    if (!$visible($pid,$aid) || $value==='') continue;
                    self::add($facts,$facets,$pid,'a'.$aid,
                        self::key($definitions[$aid],$value),$value);
                }
            }
            $dictionary=[];
            foreach (self::query($db,'#__minimarket_attribute_values',
                ['id','attribute_id','title','value_payload','color_hex'],
                'attribute_id IN ('.$attributeSql.') AND state=1',
                'sort_order ASC,id ASC') as $row) {
                $dictionary[(int)$row['id']]=$row;
            }
            foreach (self::query($db,'#__minimarket_product_attribute_options',
                ['product_id','attribute_id','value_id'],
                'product_id IN ('.$productSql.') AND attribute_id IN ('.$attributeSql.')') as $row) {
                $pid=(int)$row['product_id'];$aid=(int)$row['attribute_id'];
                $value=$dictionary[(int)$row['value_id']]??null;
                if (!$visible($pid,$aid) || !$value || (int)$value['attribute_id']!==$aid) continue;
                $label=(string)($value['value_payload']?:$value['title']);
                self::add($facts,$facets,$pid,'a'.$aid,'v:'.(int)$row['value_id'],
                    $label,(string)$value['color_hex']);
            }
        }

        // SKU axes are constrained by published global order options.
        $options=[];
        foreach (self::query($db,'#__minimarket_order_options',
            ['id','title'],'state=1','sort_order ASC,id ASC') as $row) {
            $options[(int)$row['id']]=(string)$row['title'];
        }
        $dimensions=[];
        foreach (self::query($db,'#__minimarket_variant_dimensions',
            ['id','product_id','source_option_id'],
            'product_id IN ('.$productSql.')') as $row) {
            $option=(int)$row['source_option_id'];
            if (!isset($options[$option])) continue;
            $id=(int)$row['id'];$fid='s'.$option;
            $dimensions[$id]=['pid'=>(int)$row['product_id'],'option'=>$option];
            if (!isset($facets[$fid])) {
                $facets[$fid]=[
                    'id'=>$fid,'title'=>$options[$option],'type'=>'sku',
                    'mode'=>'checkboxes','unit'=>'','multiple'=>true,'choices'=>[]
                ];
            }
        }
        if ($dimensions) {
            $optionValues=[];
            foreach (self::query($db,'#__minimarket_order_option_values',
                ['id','option_id','title','color_hex'],'state=1') as $v) {
                $optionValues[(int)$v['id']]=$v;
            }
            $values=[];
            foreach (self::query($db,'#__minimarket_variant_dimension_values',
                ['id','dimension_id','source_value_id'],
                'dimension_id IN ('.self::ids(array_keys($dimensions)).')') as $v) {
                $dim=$dimensions[(int)$v['dimension_id']]??null;
                $option=$optionValues[(int)$v['source_value_id']]??null;
                if (!$dim || !$option || (int)$option['option_id']!==$dim['option']) continue;
                $values[(int)$v['id']]=[
                    'pid'=>$dim['pid'],'fid'=>'s'.$dim['option'],
                    'token'=>'v:'.(int)$option['id'],'label'=>(string)$option['title'],
                    'hex'=>(string)$option['color_hex']
                ];
            }
            if ($values) {
                $variantIds=[];
                foreach (self::query($db,'#__minimarket_product_variants',
                    ['id','product_id','stock','sku'],
                    'product_id IN ('.$productSql.') AND state=1') as $variant) {
                    $pid=(int)$variant['product_id'];
                    $product=$byId[$pid]??null;
                    if (!$product || ($product->product_type??'simple')==='bundle') continue;
                    if ($inventoryEnabled && (
                        ((int)($product->option_stock_mode??1)===1 && (int)$variant['stock']<1)
                        || ((int)($product->option_stock_mode??1)===0 && (int)($product->stock??0)<1)
                    )) continue;
                    $id=(int)$variant['id'];$variantIds[$id]=$pid;$skus[$pid][$id]=[];
                    if ($skuMode==='text') {
                        $variantSku=trim((string)($variant['sku']??''));
                        if ($variantSku!=='') $facts[$pid]['sku'][]=$variantSku;
                    }
                }
                if ($variantIds) {
                    foreach (self::query($db,'#__minimarket_variant_value_assignments',
                        ['variant_id','dimension_id','value_id'],
                        'variant_id IN ('.self::ids(array_keys($variantIds)).')') as $link) {
                        $id=(int)$link['variant_id'];$pid=$variantIds[$id]??null;
                        $d=$dimensions[(int)$link['dimension_id']]??null;
                        $v=$values[(int)$link['value_id']]??null;
                        if ($pid===null || !$d || !$v || $d['pid']!==$pid || $v['pid']!==$pid
                            || $v['fid']!=='s'.$d['option']) continue;
                        $skus[$pid][$id][$v['fid']]=$v['token'];
                        $facets[$v['fid']]['choices'][$v['token']]=[
                            'value'=>$v['token'],'label'=>$v['label'],
                            'hex'=>preg_match('/^#[a-fA-F0-9]{6}$/D',$v['hex'])?$v['hex']:'',
                            'count'=>0
                        ];
                    }
                }
            }
        }
        foreach ($skus as &$rows) $rows=array_values(array_filter($rows));
        unset($rows);

        // Values not represented by any published product are never offered.
        foreach ($facets as $fid=>&$facet) {
            if (in_array($facet['mode'],['range','slider'],true)) {
                if (!isset($facet['min'],$facet['max'])) unset($facets[$fid]);
                continue;
            }
            if ($facet['mode']==='text') {
                $found=false;
                foreach ($facts as $values) {
                    if (!empty($values[$fid])) {$found=true;break;}
                }
                if (!$found) unset($facets[$fid]);
                continue;
            }
            foreach ($facet['choices'] as $token=>&$choice) {
                $count=0;
                foreach ($facts as $pid=>$values) {
                    if (in_array($token,$values[$fid]??[],true)) {$count++;continue;}
                    if ($facet['type']==='sku') {
                        foreach ($skus[$pid]??[] as $row) {
                            if (($row[$fid]??null)===$token) {$count++;break;}
                        }
                    }
                }
                $choice['count']=$count;
            }
            unset($choice);
            if (!$facet['choices']) unset($facets[$fid]);
            else {
                $facet['choices']=array_values($facet['choices']);
                usort($facet['choices'],static fn(array $a,array $b):int=>
                    strnatcasecmp($a['label'],$b['label']));
            }
        }
        unset($facet);
        return ['facets'=>$facets,'facts'=>$facts,'skus'=>$skus];
    }

    /** Query validation is independent of database APIs and easy to regression-test. */
    public static function selection(array $raw, array $facets): array
    {
        $out=[];
        foreach ($facets as $id=>$facet) {
            if (!array_key_exists($id,$raw)) continue;
            $data=$raw[$id];$mode=$facet['mode'];
            if ($mode==='text') {
                if (is_string($data) && mb_strlen($data)<=100 && trim($data)!=='') {
                    $out[$id]=['text'=>trim($data)];
                }
            } elseif (in_array($mode,['range','slider'],true)) {
                if (!is_array($data)) continue;
                $range=[];
                foreach (['min','max'] as $bound) {
                    $value=$data[$bound]??null;
                    if (is_scalar($value) && $value!=='' && is_numeric((string)$value)
                        && is_finite((float)$value)) $range[$bound]=(float)$value;
                }
                if ($range) $out[$id]=$range;
            } else {
                $items=is_array($data)?array_values($data):[$data];
                $choices=array_fill_keys(array_column($facet['choices'],'value'),true);
                $chosen=[];
                foreach (array_slice($items,0,50) as $value) {
                    if (!is_scalar($value)) continue;
                    $value=(string)$value;
                    if (isset($choices[$value])) $chosen[$value]=$value;
                    elseif ($value!=='') $chosen['__invalid__']='__invalid__';
                }
                if (!$facet['multiple'] && count($chosen)>1) {
                    $chosen=array_slice($chosen,0,1,true);
                }
                if ($chosen) $out[$id]=['values'=>array_values($chosen)];
            }
        }
        return $out;
    }

    public static function active(array $selected): bool
    {
        foreach ($selected as $value) {
            if (!empty($value['values']) || trim((string)($value['text']??''))!==''
                || isset($value['min']) || isset($value['max'])) return true;
        }
        return false;
    }

    /** SKU facets must match the same physical active variant row. */
    public static function matches(array $facts,array $skus,array $facets,array $selected): bool
    {
        $skuRules=[];
        foreach ($selected as $id=>$rule) {
            $facet=$facets[$id]??null;
            if (!$facet) continue;
            if ($facet['type']==='sku') {
                if (!empty($rule['values'])) $skuRules[$id]=$rule['values'];
                continue;
            }
            $values=$facts[$id]??[];
            if ($facet['mode']==='text' && !empty($rule['text'])) {
                $match=false;
                foreach ($values as $value) {
                    if (mb_stripos((string)$value,(string)$rule['text'])!==false) {
                        $match=true;break;
                    }
                }
                if (!$match) return false;
            } elseif (in_array($facet['mode'],['range','slider'],true)
                && (isset($rule['min'])||isset($rule['max']))) {
                $match=false;
                foreach ($values as $value) {
                    if (is_numeric($value)
                        && (!isset($rule['min'])||(float)$value>=$rule['min'])
                        && (!isset($rule['max'])||(float)$value<=$rule['max'])) {
                        $match=true;break;
                    }
                }
                if (!$match) return false;
            } elseif (!empty($rule['values']) &&
                !array_intersect($rule['values'],$values)) return false;
        }
        if ($skuRules) {
            foreach ($skus as $variant) {
                $all=true;
                foreach ($skuRules as $fid=>$allowed) {
                    if (!in_array($variant[$fid]??null,$allowed,true)) {$all=false;break;}
                }
                if ($all) return true;
            }
            return false;
        }
        return true;
    }
}
