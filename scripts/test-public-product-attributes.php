<?php
/**
 * Public product attribute regression: category inheritance, published values,
 * all six current types, multiple material values, image/file/url safety.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new=true);
        public function quoteName($name, $as=null);
        public function setQuery($query);
        public function loadObjectList();
    }
}

namespace MiniMarket\PublicAttributeSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\CategoryTree;
    use MiniMarket\Component\Minimarket\Site\Service\ProductAttributeCatalog as S;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/site/src/Service/CategoryTree.php';
    require __DIR__ . '/../src/com_minimarket/site/src/Service/ProductAttributeCatalog.php';

    final class Query {
        public string $from='';
        public array $conditions=[];
        public array $joins=[];
        public function select($x): self{return $this;}
        public function from($x): self {$this->from=(string)$x;return $this;}
        public function where($x): self {$this->conditions[]=(string)$x;return $this;}
        public function join($type,$x): self {$this->joins[]=(string)$x;return $this;}
        public function order($x): self{return $this;}
    }
    final class FakeDb implements DatabaseInterface {
        public ?Query $current=null;
        public array $queries=[];
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($name,$as=null):string{
            return (string)$name . ($as!==null?' AS '.$as:'');
        }
        public function setQuery($query):void{
            $this->current=$query;
            $this->queries[]=$query;
        }
        public function loadObjectList():array{
            $table=$this->current->from;
            $rows=match($table){
                '#__minimarket_category_attributes'=>[
                    ['category_id'=>1,'attribute_id'=>1,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>2,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>3,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>4,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>5,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>6,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>7,'enabled'=>1],
                    ['category_id'=>1,'attribute_id'=>9,'enabled'=>1],
                    ['category_id'=>2,'attribute_id'=>9,'enabled'=>0],
                    ['category_id'=>2,'attribute_id'=>10,'enabled'=>1],
                ],
                '#__minimarket_attributes'=>[
                    ['id'=>1,'title'=>'Материал','input_type'=>'text','number_unit'=>'',
                     'text_options'=>'[{"value":"Камень","image":"images/samples/stone.webp"},{"value":"Металл","image":""}]'],
                    ['id'=>2,'title'=>'Цвет','input_type'=>'color','number_unit'=>'','text_options'=>''],
                    ['id'=>3,'title'=>'Мощность','input_type'=>'number','number_unit'=>'Вт','text_options'=>''],
                    ['id'=>4,'title'=>'Bluetooth','input_type'=>'boolean','number_unit'=>'','text_options'=>''],
                    ['id'=>5,'title'=>'Описание свойства','input_type'=>'editor','number_unit'=>'','text_options'=>''],
                    ['id'=>6,'title'=>'Инструкция','input_type'=>'document','number_unit'=>'','text_options'=>''],
                    ['id'=>7,'title'=>'Сайт','input_type'=>'url','number_unit'=>'','text_options'=>''],
                    ['id'=>10,'title'=>'Особенность','input_type'=>'text','number_unit'=>'','text_options'=>''],
                ],
                '#__minimarket_product_attribute_scalars'=>[
                    ['attribute_id'=>3,'value_text'=>null,'value_number'=>'1400.0000','value_boolean'=>null],
                    ['attribute_id'=>4,'value_text'=>null,'value_number'=>null,'value_boolean'=>0],
                    ['attribute_id'=>5,'value_text'=>'<p>Описание</p>','value_number'=>null,'value_boolean'=>null],
                    ['attribute_id'=>6,'value_text'=>'images/docs/instruction.pdf','value_number'=>null,'value_boolean'=>null],
                    ['attribute_id'=>7,'value_text'=>'https://example.org','value_number'=>null,'value_boolean'=>null],
                    ['attribute_id'=>10,'value_text'=>'Жаростойкий','value_number'=>null,'value_boolean'=>null],
                ],
                '#__minimarket_product_attribute_options AS o'=>[
                    ['attribute_id'=>2,'title'=>'Красный','color_hex'=>'#EE0000','sample_image'=>''],
                    ['attribute_id'=>2,'title'=>'Камень','color_hex'=>'','sample_image'=>'images/samples/marble.webp'],
                ],
                '#__minimarket_product_attribute_multi_values'=>[
                    ['attribute_id'=>1,'value_text'=>'Камень'],
                    ['attribute_id'=>1,'value_text'=>'Металл'],
                ],
                default=>throw new \RuntimeException('Unexpected query table: '.$table),
            };
            return array_map(static fn(array $row):(object) $row, $rows);
        }
    }
    $cases=0;
    function check(bool $good,string $message):void{
        global $cases;
        ++$cases;
        if(!$good)throw new \RuntimeException('PUBLIC_ATTRIBUTES_FAIL: '.$message);
    }

    $tree=new CategoryTree([
        (object)['id'=>1,'parent_id'=>0,'state'=>1,'sort_order'=>0,'title'=>'Root'],
        (object)['id'=>2,'parent_id'=>1,'state'=>1,'sort_order'=>0,'title'=>'Child'],
        (object)['id'=>3,'parent_id'=>2,'state'=>0,'sort_order'=>0,'title'=>'Draft'],
    ]);
    check($tree->ancestorIds(2)===[1,2],'published root-to-child inheritance');
    check($tree->ancestorIds(3)===[],'unpublished category is hidden');
    check($tree->ancestorIds(404)===[],'unknown category is hidden');
    $db=new FakeDb();
    $attrs=S::load($db,50,$tree->ancestorIds(2));
    check(count($db->queries)===5,'five queries regardless of attribute count');
    check(array_keys($attrs)===[1,2,3,4,5,6,7,10], 'all and only enabled populated attributes shown');
    check(!isset($attrs[9]),'child category overrides inherited enabled attribute');
    check($attrs[1]['values'][0]['text']==='Камень'
        && $attrs[1]['values'][1]['text']==='Металл',
        'multiple preset materials are separate public values');
    check($attrs[1]['values'][0]['image']==='images/samples/stone.webp',
        'text preset image resolved to chosen value');
    check($attrs[2]['values'][0]['hex']==='#EE0000',
        'color has validated HEX swatch');
    check($attrs[2]['values'][1]['image']==='images/samples/marble.webp',
        'color/texture image resolves from dictionary');
    check($attrs[3]['unit']==='Вт' && $attrs[3]['values'][0]['text']==='1400.0000',
        'number retains unit and precision for template');
    check($attrs[4]['values'][0]['text']==='0','No/false is displayed and never treated as empty');
    check($attrs[5]['type']==='editor','rich text preserved for HTML filtering at output');
    check($attrs[6]['values'][0]['text']==='images/docs/instruction.pdf','document reference read');
    check(S::safeDocument('images/docs/instruction.pdf')!=='','valid file link');
    check(S::safeDocument('images/../../configuration.php')==='','unsafe file path rejected');
    check(S::safeDocument('images/evil.php')==='','executable document rejected');
    check(S::safeImage('images/preset.jpg')==='images/preset.jpg','legitimate preview accepted');
    check(S::safeImage('images/../../secret.webp')==='','traversal image rejected');
    check(S::safeUrl('javascript:alert(1)')==='','unsafe external URL rejected');
    check(S::safeUrl('https://example.org')==='https://example.org','valid HTTPS link');
    check(S::load($db,50,[])===[],'no query when category is not public');
    check(($db->queries[1]->conditions[1] ?? '')==='state = 1',
        'attribute definition query explicitly filters unpublished');
    check(in_array('v.state = 1',$db->queries[3]->conditions,true),
        'option join explicitly excludes unpublished dictionary entries');

    $template=file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php');
    $view=file_get_contents(__DIR__.'/../src/com_minimarket/site/src/View/Product/HtmlView.php');
    check(str_contains($template,'COM_MINIMARKET_PRODUCT_ATTRIBUTES')
        && str_contains($template,'safeDocument')
        && str_contains($template,'InputFilter::getInstance()->clean'),
        'template shows heading, validates files and sanitizes HTML');
    check(str_contains($view,'getProductAttributes('),
        'public view loads product attributes from repository');
    echo 'PUBLIC_ATTRIBUTES_OK: '.$cases." cases\n";
}
