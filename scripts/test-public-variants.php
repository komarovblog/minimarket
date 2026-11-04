<?php
/** Public SKU combinations must be based only on the product's own order options. */
declare(strict_types=1);

namespace Joomla\Database { interface DatabaseInterface {} }
namespace Joomla\CMS\Filter {
    final class InputFilter {
        public static function getInstance(): self { return new self(); }
        public function clean(string $html,string $type): string {
            return preg_replace('~<script\b[^>]*>.*?</script>~is','',$html)??'';
        }
    }
}
namespace MiniMarket\PublicVariantsSmoke {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/site/src/Service/VariantCatalog.php';
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\VariantCatalog;

    final class Query {
        public string $from='';
        public array $where=[];
        public function select($v):self{return $this;}
        public function from($v):self{$this->from=(string)$v;return $this;}
        public function where($v):self{$this->where[]=(string)$v;return $this;}
        public function order($v):self{return $this;}
    }
    final class Db implements DatabaseInterface {
        public Query $q;
        public array $queries=[];
        public bool $emptySkus=false;
        public bool $missingOptions=false;
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($name):string{return (string)$name;}
        public function setQuery($q):void{$this->q=$q;$this->queries[]=$q;}
        public function loadAssocList():array {
            return match ($this->q->from) {
                '#__minimarket_product_variants' => $this->emptySkus ? [] : [
                    ['id'=>20,'selection_json'=>'{"color":"v:red","size":"v:s"}',
                        'sku'=>'RED-S','price'=>'1590.00','stock'=>5,
                        'image'=>'','image_alt'=>'','video_mode'=>0],
                    ['id'=>21,'selection_json'=>'{"color":"v:red","size":"v:m"}',
                        'sku'=>'RED-M','price'=>'1690.00','stock'=>0,
                        'image'=>'','image_alt'=>'','video_mode'=>2],
                    ['id'=>22,'selection_json'=>'{"color":"v:blue","size":"v:m"}',
                        'sku'=>'BLUE-M','price'=>'1790.00','stock'=>7,
                        'image'=>'images/blue-m.webp','image_alt'=>'Blue M',
                        'description_mode'=>1,'introtext_override'=>'<p>Blue intro</p>',
                        'description_override'=>'<p>Blue description</p>','video_mode'=>1],
                    ['id'=>23,'selection_json'=>'{"10":"d:100","11":"t:S"}',
                        'sku'=>'INVALID-OLD','price'=>'1','stock'=>1,
                        'image'=>'','image_alt'=>'','video_mode'=>0]
                ],
                '#__minimarket_variant_dimensions' => $this->missingOptions ? [] : [
                    ['id'=>10,'code'=>'color','title'=>'Цвет'],
                    ['id'=>11,'code'=>'size','title'=>'Размер']
                ],
                '#__minimarket_variant_dimension_values' => [
                    ['id'=>100,'dimension_id'=>10,'code'=>'red','title'=>'Красный'],
                    ['id'=>101,'dimension_id'=>10,'code'=>'blue','title'=>'Синий'],
                    ['id'=>200,'dimension_id'=>11,'code'=>'s','title'=>'S'],
                    ['id'=>201,'dimension_id'=>11,'code'=>'m','title'=>'M'],
                    ['id'=>202,'dimension_id'=>11,'code'=>'l','title'=>'L']
                ],
                '#__minimarket_variant_value_images' => [
                    ['value_id'=>100,'path'=>'images/red.webp','alt'=>'Red gallery'],
                    ['value_id'=>100,'path'=>'images/red-back.webp','alt'=>'Red back'],
                    ['value_id'=>101,'path'=>'images/blue-color.webp','alt'=>'Blue gallery']
                ],
                '#__minimarket_variant_value_assignments' => [
                    ['variant_id'=>20,'dimension_id'=>10,'value_id'=>100],
                    ['variant_id'=>20,'dimension_id'=>11,'value_id'=>200],
                    ['variant_id'=>21,'dimension_id'=>10,'value_id'=>100],
                    ['variant_id'=>21,'dimension_id'=>11,'value_id'=>201],
                    ['variant_id'=>22,'dimension_id'=>10,'value_id'=>101],
                    ['variant_id'=>22,'dimension_id'=>11,'value_id'=>201]
                ],
                '#__minimarket_product_variant_images' => [
                    ['variant_id'=>22,'path'=>'images/blue-back.webp','alt'=>'Blue back'],
                    ['variant_id'=>20,'path'=>'images/../../evil.webp','alt'=>'Unsafe']
                ],
                '#__minimarket_product_images' => [
                    ['id'=>81,'path'=>'images/global.webp','alt'=>'All',
                        'visibility_mode'=>0],
                    ['id'=>82,'path'=>'images/blue-only.webp','alt'=>'Blue only',
                        'visibility_mode'=>1],
                ],
                '#__minimarket_product_image_variants' => [
                    ['image_id'=>82,'variant_id'=>22,'is_cover'=>1],
                ],
                '#__minimarket_product_variant_videos' => [
                    ['variant_id'=>22,'title'=>'Blue video',
                        'source'=>'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'poster'=>'images/blue.webp'],
                    ['variant_id'=>22,'title'=>'Bad',
                        'source'=>'javascript:alert(1)','poster'=>'']
                ],
                default => throw new \RuntimeException('Unexpected source '.$this->q->from)
            };
        }
    }

    $n=0;
    function check(bool $condition,string $name):void {
        global $n;
        $n++;
        if (!$condition) throw new \RuntimeException('PUBLIC_SKU_LOCAL_FAIL: '.$name);
    }
    $empty=new Db();
    $empty->emptySkus=true;
    check(VariantCatalog::load($empty,1,[1])===['axes'=>[],'items'=>[]],
        'product with no SKU returns empty collection');
    check(count($empty->queries)===1,'no extra queries for a simple product');

    $noOptions=new Db();
    $noOptions->missingOptions=true;
    check(VariantCatalog::load($noOptions,1,[1])===['axes'=>[],'items'=>[]],
        'SKU rows without explicit order options cannot be shown');
    check(count($noOptions->queries)===2,
        'invalid old SKU format never triggers category attribute fallback');

    $db=new Db();
    $result=VariantCatalog::load($db,1,[1]);
    check(count($result['axes'])===2,'two product-local order options');
    check(array_keys($result['axes'])===['color','size'],'axes use stable option codes');
    check(count($result['items'])===3,'only genuine option combinations are public');
    check(!in_array('INVALID-OLD',array_column($result['items'],'sku'),true),
        'old attribute-based SKU selections are ignored');
    check(count($db->queries)===9,
        'batched local SKU, gallery permissions and video queries');
    check(!in_array('#__minimarket_category_attributes',
        array_map(static fn($q)=>$q->from,$db->queries),true),
        'informational category attributes never consulted by SKU engine');
    $bySku=[];
    foreach ($result['items'] as $item) $bySku[$item['sku']]=$item;
    check($bySku['RED-S']['stock']===5 && $bySku['RED-M']['stock']===0,
        'each size has its own stock, including sold-out published SKUs');
    check($bySku['RED-S']['image']==='images/red.webp',
        'sizes with no own media inherit color gallery');
    check($bySku['RED-S']['photos'][0]['path']==='images/red-back.webp',
        'color gallery referenced without duplicating files');
    check($bySku['RED-M']['image']==='images/red.webp',
        'Red M uses same gallery as Red S');
    check($bySku['BLUE-M']['image']==='images/blue-m.webp',
        'individual SKU gallery overrides color gallery');
    check($bySku['BLUE-M']['photos'][0]['path']==='images/blue-back.webp',
        'individual SKU gallery preserved');
    check(count($bySku['RED-S']['gallery'])===1 &&
        $bySku['RED-S']['gallery'][0]['path']==='images/global.webp',
        'red SKU never exposes images restricted to blue');
    check(count($bySku['BLUE-M']['gallery'])===2 &&
        $bySku['BLUE-M']['gallery'][1]['cover']===true,
        'blue SKU gets shared plus unique primary image');
    check($bySku['BLUE-M']['description_override']==='<p>Blue description</p>',
        'individual SKU text can override product text');
    check(count($bySku['BLUE-M']['videos'])===1 &&
        $bySku['BLUE-M']['videos'][0]['kind']==='embed',
        'only sanitized individual videos are published');
    check($bySku['RED-M']['video_mode']===2,'SKU can suppress inherited video');
    check(in_array('state = 1',$db->queries[0]->where,true),
        'unpublished SKU rows excluded on server');

    $template=file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php');
    check(str_contains($template,'data-mm-variant-price') &&
        str_contains($template,'data-mm-variant-stock'),
        'public product retains one price and selected SKU stock');
    echo 'PUBLIC_SKU_LOCAL_PASS: '.$n." cases\n";
}
