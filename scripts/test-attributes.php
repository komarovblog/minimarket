<?php
/** Canonical descriptive attributes and dictionary value identity guards. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new=true);
        public function quoteName($name);
        public function quote($value);
        public function setQuery($query);
        public function loadResult();
    }
}
namespace Joomla\CMS\Language {
    final class Text {public static function _(string $key):string{return $key;}}
}
namespace Joomla\CMS\Table {
    use Joomla\Database\DatabaseInterface;
    class Table {
        public int $id=0;
        public string $title='';public string $code='';
        public string $input_type='text';public string $value_source='manual';
        public string $color_display='both';public string $filter_mode='none';
        public int $filter_multiple=0;public int $is_multiple=0;
        public int $is_filterable=0;public int $state=0;
        public int $sort_order=0;public int $attribute_id=0;
        public string $number_unit='';public $number_min=null;
        public $number_max=null;public $number_step=null;
        public array $columnAliases=[];public string $error='';
        public function __construct(protected string $table,protected string $idName,
            protected DatabaseInterface $db){}
        public function setColumnAlias(string $alias,string $name):void{
            $this->columnAliases[$alias]=$name;
        }
        public function setError(string $error):void{$this->error=$error;}
        public function getDbo():DatabaseInterface{return $this->db;}
        public function check(){return true;}
    }
}
namespace MiniMarket\Tests\CanonicalAttributes {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Table\AttributeTable;
    use MiniMarket\Component\Minimarket\Administrator\Table\ValueTable;
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Table/AttributeTable.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Table/ValueTable.php';
    $checks=0;
    function check(bool $ok,string $message):void{
        global $checks;$checks++;
        if(!$ok)throw new \RuntimeException('CANONICAL_ATTRIBUTE_FAIL: '.$message);
    }
    final class Query {
        public string $from='';public string $selected='';
        public array $where=[];public bool $having=false;
        public function select($v):self {
            $this->selected=is_array($v)?implode(',',$v):(string)$v;return $this;
        }
        public function from($t):self{$this->from=(string)$t;return $this;}
        public function where($s):self{$this->where[]=(string)$s;return $this;}
        public function group($s):self{return $this;}
        public function having($s):self{$this->having=true;return $this;}
    }
    final class DB implements DatabaseInterface{
        public ?Query $q=null;public bool $duplicateAttribute=false;
        public bool $duplicateValue=false;public bool $hasSavedValues=false;
        public bool $hasMultiple=false;public string $existingType='text';
        public string $existingSource='preset';public string $parentSource='preset';
        public array $queries=[];
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($x):string{return (string)$x;}
        public function quote($x):string{return "'".str_replace("'","''",(string)$x)."'";}
        public function setQuery($q):void{$this->q=$q;$this->queries[]=$q;}
        public function loadAssoc():array {
            return ['input_type'=>$this->existingType,'value_source'=>$this->existingSource];
        }
        public function loadResult():int|string{
            $q=$this->q;
            if($q?->from==='#__minimarket_attributes'){
                if($q->selected==='value_source')return $this->parentSource;
                return $this->duplicateAttribute?1:0;
            }
            if($q?->from==='#__minimarket_attribute_values')return $this->duplicateValue?1:0;
            if(in_array($q?->from,[
                '#__minimarket_product_attribute_scalars',
                '#__minimarket_product_attribute_options',
                '#__minimarket_product_attribute_free_values'
            ],true)) return $q->having?($this->hasMultiple?1:0):($this->hasSavedValues?1:0);
            throw new \RuntimeException('Unrecognized SQL: '.$q?->from);
        }
    }
    $db=new DB();
    $item=new AttributeTable($db);
    $item->id=10;$item->title=' Материал ';$item->code='MATERIAL';
    $item->input_type='text';$item->value_source='preset';
    $item->is_multiple=1;$item->is_filterable=1;$item->state=1;
    $item->filter_mode='checkboxes';
    check($item->check()===true,'predefined text characteristic');
    check($item->title==='Материал'&&$item->code==='material','name and code normalized');
    check($item->columnAliases['published']==='state','native publish alias');
    $db->hasMultiple=true;
    check($item->check()===true && $item->is_multiple===0,
        'legacy multi rows do not block fixed single-value definition');
    $db->hasMultiple=false;$item->is_multiple=1;
    check($item->check()===true && $item->is_multiple===0,
        'legacy multiple form values are normalized to zero');
    $item->is_multiple=3;
    check($item->check()===false,'invalid multiplicity rejected');
    $item->is_multiple=1;
    $item->code='bad$';
    check($item->check()===false,'invalid ASCII code rejected');
    $item->code='material';$db->duplicateAttribute=true;
    check($item->check()===false,'duplicate characteristic code rejected');
    $db->duplicateAttribute=false;
    $item->input_type='number';$item->value_source='manual';$item->number_unit='Вт';
    $item->number_min='0';$item->number_max='10';$item->number_step='5';
    $item->filter_mode='slider';
    check($item->check()===true,'number and slider accepted');
    $item->number_min='20';
    check($item->check()===false,'invalid numeric range rejected');
    $item->number_min='0';$item->number_step='0';
    check($item->check()===false,'zero numeric step rejected');
    $item->number_step='5';$item->filter_mode='radio';
    check($item->check()===true && $item->filter_mode==='checkboxes',
        'legacy radio mode normalizes to canonical checkbox display for numbers');
    $item->filter_mode='slider';$db->hasSavedValues=true;
    check($item->check()===false,'cannot change type/source when product values exist');
    $db->hasSavedValues=false;
    $item->input_type='text';$item->value_source='preset';
    $item->filter_mode='checkboxes';$item->number_min=null;
    $item->number_max=null;$item->number_step=null;
    $item->input_type='url';
    check($item->check()===false,'removed URL attribute type rejected');
    $item->input_type='list';
    check($item->check()===false,'removed List type rejected');
    $item->input_type='date';
    check($item->check()===false,'removed Date type rejected');
    $item->input_type='textarea';
    check($item->check()===false,'removed Textarea type rejected');
    $item->input_type='text';$item->value_source='legacy';
    check($item->check()===false,'legacy value source cannot be saved');
    $item->value_source='preset';$item->sort_order=-1;
    check($item->check()===false,'negative sort order blocked');
    $item->sort_order=0;$item->state=9;
    check($item->check()===false,'unknown published state rejected');
    $item->state=1;$item->id=0;
    foreach(['text','number','editor','boolean','document','color'] as $type){
        $item->input_type=$type;$item->is_filterable=0;
        $item->filter_mode='none';$item->value_source=$type==='boolean'?'manual':'preset';
        check($item->check()===true,'can create canonical '.$type);
    }
    $value=new ValueTable($db);$value->attribute_id=10;
    $value->title=' Красный ';$value->code='RED';$value->state=1;
    check($value->check()===true,'dictionary entry under preset attribute');
    check($value->title==='Красный'&&$value->code==='red',
        'dictionary code/title normalization');
    check($value->columnAliases['published']==='state','value published alias');
    $db->duplicateValue=true;
    check($value->check()===false,'duplicate dictionary value code');
    $db->duplicateValue=false;$db->parentSource='manual';
    check($value->check()===false,'manual attribute cannot own dictionary value');
    $db->parentSource='preset';$value->attribute_id=0;
    check($value->check()===false,'zero attribute id rejected');
    $value->attribute_id=10;$value->code='кириллица';
    check($value->check()===false,'non-ascii value code rejected');
    echo "ATTRIBUTES_TEST_OK: $checks cases\n";
}
