<?php
/** Public SKU variant retrieval: no database/network required. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\PublicVariantsSmoke {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/site/src/Service/VariantCatalog.php';
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\VariantCatalog;
    final class Query {
        public string $from='';
        public array $where=[];
        public function select($x):self{return $this;}
        public function from($x):self {$this->from=(string)$x;return $this;}
        public function where($x):self {$this->where[]=(string)$x;return $this;}
        public function order($x):self{return $this;}
    }
    final class Db implements DatabaseInterface {
        public ?Query $q=null;public array $queries=[];
        public bool $empty=false;
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($value):string{return (string)$value;}
        public function setQuery($q):void{$this->q=$q;$this->queries[]=$q;}
        public function loadAssocList():array {
            return match ($this->q->from) {
                '#__minimarket_product_variants' => $this->empty ? [] : [
                    ['id'=>1,'selection_json'=>'{"10":"d:100","11":"t:S"}',
                        'sku'=>'RED-S','price'=>'1590.00','stock'=>'5',
                        'image'=>'images/red-s.webp','image_alt'=>'Red S'],
                    ['id'=>2,'selection_json'=>'{"10":"d:100","11":"t:M"}',
                        'sku'=>'RED-M','price'=>'1690.00','stock'=>'0',
                        'image'=>'images/red-m.webp','image_alt'=>'Red M'],
                    ['id'=>3,'selection_json'=>'{"10":"d:999","11":"t:S"}',
                        'sku'=>'FAKE','price'=>'1','stock'=>'3','image'=>'','image_alt'=>''],
                ],
                '#__minimarket_category_attributes' => [
                    ['category_id'=>1,'attribute_id'=>10,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>11,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>12,'enabled'=>1],
                    ['category_id'=>2,'attribute_id'=>12,'enabled'=>0],
                ],
                '#__minimarket_attributes' => [
                    ['id'=>10,'title'=>'Цвет','input_type'=>'color','input_mode'=>'auto','text_options'=>''],
                    ['id'=>11,'title'=>'Размер','input_type'=>'text','input_mode'=>'presets',
                        'text_options'=>'["S","M","L"]'],
                ],
                '#__minimarket_attribute_values' => [
                    ['attribute_id'=>10,'id'=>100,'title'=>'Красный'],
                    ['attribute_id'=>10,'id'=>101,'title'=>'Синий'],
                ],
                '#__minimarket_product_variant_images' => [
                    ['variant_id'=>1,'path'=>'images/red-s-back.webp','alt'=>'Back'],
                    ['variant_id'=>2,'path'=>'images/../../evil.webp','alt'=>'Unsafe'],
                ],
                default => throw new \RuntimeException('Unknown query '.$this->q->from),
            };
        }
    }
    $count=0;
    function check(bool $yes,string $why):void{
        global $count;++$count;
        if(!$yes)throw new \RuntimeException('PUBLIC_VARIANTS_FAIL: '.$why);
    }
    $db=new Db();
    $db->empty=true;
    check(VariantCatalog::load($db,1,[1,2])===['axes'=>[],'items'=>[]],
        'legacy product no variants returns empty');
    check(count($db->queries)===1,'legacy product uses one variant existence query');
    $db=new Db();
    $result=VariantCatalog::load($db,1,[1,2]);
    check(count($result['axes'])===2,'two published, enabled variant axes');
    check(count($result['items'])===2,'unpublished or invalid option references hidden');
    check($result['items'][0]['sku']==='RED-S','in stock default SKU');
    check($result['items'][1]['stock']===0,'out-of-stock SKU remains selectable');
    check($result['items'][0]['photos'][0]['path']==='images/red-s-back.webp',
        'per-variant photo read');
    check($result['items'][1]['photos']===[],'unsafe gallery paths rejected');
    check($result['axes'][11]['options'][1]['value']==='t:M','size text preset available');
    check(in_array('state = 1', $db->queries[0]->where, true),
        'only published variant rows queried');
    check(count($db->queries)===5,'batched reads independent of SKU count');
    $site=file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php');
    check(str_contains($site,'data-mm-variant-price')
        && str_contains($site,'data-mm-variant-stock'),
        'public price and stock update hooks present');
    echo 'PUBLIC_VARIANTS_OK: '.$count." cases\n";
}
