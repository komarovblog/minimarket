<?php
/** Pure PHP 0.5.10 dictionary Subform regression; never touches user DB. */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new = true);
        public function quoteName($name);
        public function quote($value);
        public function setQuery($query);
        public function loadAssocList();
        public function loadResult();
        public function execute();
    }
}
namespace Joomla\CMS\Filter {
    final class OutputFilter {
        public static function stringURLSafe(string $s): string {
            // Joomla stub: verify ASCII code generation without browser/locale.
            return strtolower(str_replace(' ', '-', $s));
        }
    }
}
namespace MiniMarket\AttributeValueRowsSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeValueRows.php';
    $count = 0;
    function ok(bool $value, string $name): void {
        global $count;
        ++$count;
        if (!$value) throw new \RuntimeException('ATTRIBUTE_VALUE_ROWS_FAIL: ' . $name);
    }
    $old=[
        10=>['id'=>10,'title'=>'Красный','code'=>'red','state'=>1,'sort_order'=>0],
        11=>['id'=>11,'title'=>'Жёлтый','code'=>'yellow','state'=>0,'sort_order'=>1],
    ];
    $posted=[
        'row0'=>['id'=>'11','title'=>'Жёлтый','code'=>'yellow','state'=>'0'],
        'row1'=>['id'=>'10','title'=>'Красный','code'=>'red','state'=>'1'],
        'row2'=>['id'=>'0','title'=>'Blue sky','code'=>'','state'=>'1'],
    ];
    $rows=AttributeValueRows::normalize($posted,$old);
    ok(count($rows)===3,'new row appended');
    ok($rows[0]['id']===11 && $rows[0]['sort_order']===0
        && $rows[1]['id']===10 && $rows[1]['sort_order']===1,
        'drag ordering changes position without changing stable ids');
    ok($rows[0]['state']===0,'unpublished existing choice preserved');
    ok($rows[2]['code']==='blue-sky','code derived if missing');
    ok(AttributeValueRows::normalize([], $old)===[], 'remove final row supported');
    ok(AttributeValueRows::normalize(null, $old)===[], 'null field means no rows');
    ok(AttributeValueRows::normalize('{}', $old)===[], 'empty Joomla JSON subform accepted');
    ok(AttributeValueRows::deletedIds($old,$rows)===[], 'existing IDs kept');
    ok(AttributeValueRows::deletedIds($old,[$rows[0]])===[10], 'only absent ID deleted');

    $bad=[
        [['id'=>44,'title'=>'Wrong ID','code'=>'unknown','state'=>1]],
        [['id'=>10,'title'=>'A','code'=>'a','state'=>1],['id'=>10,'title'=>'B','code'=>'b','state'=>1]],
        [['id'=>10,'title'=>'A','code'=>'same','state'=>1],['id'=>11,'title'=>'B','code'=>'same','state'=>1]],
        [['id'=>0,'title'=>'','code'=>'missing','state'=>1]],
        [['id'=>0,'title'=>'Test','code'=>'***','state'=>1]],
        [['id'=>0,'title'=>'Test','code'=>'valid','state'=>3]],
        [['id'=>0,'title'=>'Test','code'=>'valid','state'=>[]]],
        [['id'=>0,'title'=>'Test','code'=>'valid','state'=>1,'extra'=>'ignored'],['id'=>0,'title'=>'Test two','code'=>'valid','state'=>1]],
    ];
    foreach($bad as $i=>$input){
        try {AttributeValueRows::normalize($input,$old);ok(false,'bad row '.$i);}
        catch(\InvalidArgumentException $e){ok(true,'bad row '.$i);}
    }
    $r=AttributeValueRows::normalize([['id'=>0,'title'=>'Красный','code'=>'','state'=>1]],[]);
    ok((bool)preg_match('/^[a-z][a-z0-9_-]+$/',$r[0]['code']),
        'non-Latin name produces stable ASCII fallback');

    final class Query {
        public string $op = '';
        public string $table = '';
        public array $where = [];
        public array $set = [];
        public array $values = [];
        public function select($v): self {$this->op='select'; return $this;}
        public function from($v): self {$this->table=(string)$v; return $this;}
        public function where($v): self {$this->where[]=(string)$v; return $this;}
        public function order($v): self {return $this;}
        public function update($v): self {$this->op='update'; $this->table=(string)$v; return $this;}
        public function set($v): self {$this->set=is_array($v)?$v:[$v]; return $this;}
        public function insert($v): self {$this->op='insert'; $this->table=(string)$v; return $this;}
        public function columns($v): self {return $this;}
        public function values($v): self {$this->values[]=$v; return $this;}
        public function delete($v): self {$this->op='delete'; $this->table=(string)$v; return $this;}
    }
    final class Db implements DatabaseInterface {
        public ?Query $q=null;
        public array $writes=[];
        public int $used=0;
        public function getQuery($new = true): Query {return new Query();}
        public function quoteName($v): mixed {return $v;}
        public function quote($v): string {return "'".str_replace("'","''",(string)$v)."'";}
        public function setQuery($q): void {$this->q=$q;}
        public function loadAssocList(): array {
            return [
                ['id'=>10,'title'=>'Красный','code'=>'red','color_hex'=>'','state'=>1,'sort_order'=>0],
                ['id'=>11,'title'=>'Жёлтый','code'=>'yellow','color_hex'=>'','state'=>0,'sort_order'=>1],
            ];
        }
        public function loadResult(): int {return $this->used;}
        public function execute(): void {$this->writes[]=[$this->q->op,$this->q->table,$this->q->where,$this->q->set,$this->q->values];}
    }
    $db=new Db();
    ok(AttributeValueRows::load($db,0)===[], 'no SQL for unsaved attribute');
    ok(count(AttributeValueRows::formRows($db,3))===2, 'two rows loaded for Joomla Subform');
    $loaded=AttributeValueRows::formRows($db,3);
    ok($loaded['row0']['id']===10 && $loaded['row1']['code']==='yellow',
        'hidden IDs, published state and code roundtrip');
    AttributeValueRows::apply($db,3,$old,$rows);
    ok(count($db->writes)===3, 'two retained IDs updated and new row inserted');
    ok($db->writes[0][0]==='update' && $db->writes[2][0]==='insert',
        'existing records updated in place; new inserted');
    ok(str_contains(implode(' ', $db->writes[0][2]), 'attribute_id = 3'),
        'update scoped by exact parent ID');
    $db->writes=[];
    $reduced=AttributeValueRows::normalize([['id'=>10,'title'=>'Red','code'=>'red','state'=>1]],$old);
    $db->used=1;
    try {AttributeValueRows::apply($db,3,$old,$reduced);ok(false,'used value cannot delete');}
    catch(\RuntimeException $e){ok($e->getMessage()==='COM_MINIMARKET_VALUE_ROWS_IN_USE',
        'used value blocks deletion before writes');}
    ok($db->writes===[],'no write before deletion guard');
    $db->used=0;
    AttributeValueRows::apply($db,3,$old,$reduced);
    ok($db->writes[0][0]==='delete' && count($db->writes)===2,
        'free old row removed, kept record still updated');
    $db->writes=[];
    $swapped=AttributeValueRows::normalize([
        ['id'=>10,'title'=>'Red','code'=>'yellow','state'=>1],
        ['id'=>11,'title'=>'Yellow','code'=>'red','state'=>1],
    ],$old);
    AttributeValueRows::apply($db,3,$old,$swapped);
    ok(count($db->writes)===4 && str_contains(implode(' ', $db->writes[0][3]), '__mm_stage_'),
        'code swaps safely staged before final updates');
    ok(AttributeValueRows::MAX_ROWS>=30,'gallery-scale dictionary supported');

    $root=__DIR__.'/../src/com_minimarket/admin/';
    $form=simplexml_load_file($root.'forms/attribute.xml');
    $sub=simplexml_load_file($root.'forms/attribute_value_row.xml');
    ok($form!==false && $sub!==false,'both Joomla XML forms parse');
    $field=$form->xpath("//fieldset[@name='details']/field[@name='values']");
    ok(count($field)===1 && (string)$field[0]['type']==='subform'
        && (string)$field[0]['multiple']==='true','one native repeatable field in Attribute details');
    ok((string)$field[0]['buttons']==='add,remove,move','native Joomla add/remove/move handles');
    ok((string)$field[0]['showon']==='value_source:legacy[AND]input_type:list',
        'legacy List values remain isolated from unified dictionary');
    ok(count($form->xpath("//field[@name='values_present']"))===1,'empty field sentinel');
    ok(count($sub->xpath("//field[@name='id' and @type='hidden']"))===1,'stable ID hidden in each row');
    ok(count($sub->xpath("//field[@name='title']"))===1,'simple title entry');
    ok(count($sub->xpath("//field[@name='code' and @required='false']"))===1,'code optional');
    ok(count($sub->xpath("//field[@name='state']"))===1,'individual publishing status');
    $edit=file_get_contents($root.'tmpl/attribute/edit.php');
    ok(!str_contains($edit, "'minimarket-attribute', 'values'"),'no separate values tab');
    ok(!str_contains($edit,'data-mm-values-sort'),'old sorting screen removed');
    $list=file_get_contents($root.'tmpl/attributes/default.php');
    ok(!str_contains($list,'view=values&attribute_id='),'list links do not create extra navigation');
    $model=file_get_contents($root.'src/Model/AttributeModel.php');
    ok(str_contains($model,'AttributeValueRows::apply'),'save uses ID-preserving rows');
    ok(str_contains($model,'transactionStart()'),'attribute, categories and values atomic');
    echo 'ATTRIBUTE_VALUE_ROWS_OK: '.$count." cases\n";
}
