<?php
/** Category eligibility, typed values and transactional relation contract. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new = true);
        public function quoteName($name);
        public function quote($value);
        public function setQuery($query);
        public function loadAssocList();
        public function execute();
    }
}
namespace MiniMarket\ProductAttributesSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryAttributes.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/ProductAttributes.php';
    $checks = 0;
    function verify(bool $yes,string $what): void {
        global $checks;
        ++$checks;
        if (!$yes) { throw new \RuntimeException('PRODUCT_ATTRIBUTES_FAIL: ' . $what); }
    }

    $defs = [
        10=>['id'=>10,'title'=>'Color','type'=>'list','is_variant'=>true,'state'=>1,
            'values'=>[100=>['title'=>'Red','state'=>1],101=>['title'=>'Yellow','state'=>1],
                102=>['title'=>'Black','state'=>0]]],
        11=>['id'=>11,'title'=>'Size','type'=>'list','is_variant'=>true,'state'=>1,
            'values'=>[110=>['title'=>'S','state'=>1],111=>['title'=>'M','state'=>1]]],
        12=>['id'=>12,'title'=>'Material','type'=>'list','is_variant'=>false,'state'=>1,
            'values'=>[120=>['title'=>'Denim','state'=>1],121=>['title'=>'Cotton','state'=>1]]],
        13=>['id'=>13,'title'=>'Width','type'=>'number','is_variant'=>false,'state'=>1,'values'=>[]],
        14=>['id'=>14,'title'=>'Summary','type'=>'text','is_variant'=>false,'state'=>1,'values'=>[]],
        15=>['id'=>15,'title'=>'Eco','type'=>'boolean','is_variant'=>false,'state'=>1,'values'=>[]],
        16=>['id'=>16,'title'=>'Hidden','type'=>'text','is_variant'=>false,'state'=>0,'values'=>[]],
    ];
    $hierarchy=['parents'=>[1=>0,2=>1,3=>1,4=>0],
        'rules'=>[1=>[10=>1,11=>1,12=>1,13=>1,14=>1,15=>1],
            2=>[11=>0],3=>[13=>0],4=>[16=>1]]];
    $map = ProductAttributes::categoryMap($hierarchy,$defs);
    verify($map[1] === [10,11,12,13,14,15], 'parent category includes all published assignments');
    verify($map[2] === [10,12,13,14,15], 'child excludes inherited Size');
    verify($map[3] === [10,11,12,14,15], 'sibling excludes Width only');
    verify($map[4] === [16], 'draft attribute remains editable in admin, hidden publicly later');
    $selection=ProductAttributes::normalize([
        '10'=>['100','101'], '11'=>['110','111'], '12'=>'120',
        '13'=>'-12.5', '14'=>'Heavy cotton', '15'=>'0'
    ],1,$defs,$map);
    verify($selection['options'][10] === [100,101], 'multiple colors selected');
    verify($selection['options'][11] === [110,111], 'multiple sizes selected');
    verify($selection['options'][12] === [120], 'single material selected');
    verify($selection['scalars'][13]['value'] === '-12.5', 'decimal number saved');
    verify($selection['scalars'][14]['value'] === 'Heavy cotton', 'text property saved');
    verify($selection['scalars'][15]['value'] === '0', 'false boolean not lost');
    verify(ProductAttributes::normalize(['10'=>['102']],1,$defs,$map)['options'][10] === [102],
        'unpublished dictionary values remain editable in admin');
    verify(ProductAttributes::normalize([], 1,$defs,$map) === ['scalars'=>[],'options'=>[]],
        'clear-all requires empty tables, not old data');
    verify(ProductAttributes::normalize(['11'=>''],2,$defs,$map)
        === ['scalars'=>[],'options'=>[]], 'cleared excluded field is ignored');

    $invalid=[
        [2,['11'=>['110']], 'inherited excluded size'],
        [4,['10'=>['100']], 'unassigned property'],
        [1,['16'=>'wrong'], 'attribute unassigned in this category'],
        [1,['10'=>['999']], 'unknown dictionary value'],
        [1,['10'=>['120']], 'value belongs to different property'],
        [1,['12'=>['120','121']], 'single-valued list cannot accept two'],
        [1,['10'=>['100','100']], 'repeated list option'],
        [1,['13'=>'1e9'], 'number cannot use exponent'],
        [1,['13'=>'12.12345'], 'number exceeds precision'],
        [1,['14'=>str_repeat('a',1025)], 'oversized text'],
        [1,['15'=>'yes'], 'boolean restricted'],
        [1,['14'=>['forged']], 'array to text invalid'],
        [1,['999'=>'something'], 'unknown property'],
    ];
    foreach($invalid as [$category,$submitted,$label]){
        try {
            ProductAttributes::normalize($submitted,$category,$defs,$map);
            verify(false, $label);
        } catch (\InvalidArgumentException $e) {
            verify(true,$label);
        }
    }

    final class Query {
        public string $op='';
        public string $table='';
        public array $values=[];
        public function select($v): self {$this->op='select';return $this;}
        public function from($x): self {$this->table=(string)$x;return $this;}
        public function order($x): self {return $this;}
        public function where($x): self {return $this;}
        public function delete($x): self {$this->op='delete';$this->table=(string)$x;return $this;}
        public function insert($x): self {$this->op='insert';$this->table=(string)$x;return $this;}
        public function columns($x): self {return $this;}
        public function values($x): self {$this->values[]=$x;return $this;}
    }
    final class Db implements DatabaseInterface {
        public ?Query $q = null;
        public array $calls=[];
        public function getQuery($new = true): Query {return new Query();}
        public function quoteName($v): mixed {return $v;}
        public function quote($v): string {return "'".str_replace("'","''",(string)$v)."'";}
        public function setQuery($q): void {$this->q=$q;}
        public function loadAssocList(): array {
            if ($this->q->table==='#__minimarket_attributes') {
                return [['id'=>10,'title'=>'Color','input_type'=>'list','is_variant'=>1,'state'=>1]];
            }
            if ($this->q->table==='#__minimarket_attribute_values') {
                return [['id'=>100,'attribute_id'=>10,'title'=>'Red','state'=>1]];
            }
            if ($this->q->table==='#__minimarket_product_attribute_scalars') {
                return [['attribute_id'=>15,'value_text'=>null,'value_number'=>null,'value_boolean'=>0]];
            }
            if ($this->q->table==='#__minimarket_product_attribute_options') {
                return [['attribute_id'=>10,'value_id'=>100]];
            }
            return [];
        }
        public function execute(): void {$this->calls[]=[$this->q->op,$this->q->table,$this->q->values];}
    }
    $db = new Db();
    $real = ProductAttributes::definitions($db);
    verify($real[10]['values'][100]['title']==='Red','definitions and matching dictionary loaded in two reads');
    verify(ProductAttributes::load($db,0)===[],'new product has empty attributes');
    $loaded=ProductAttributes::load($db,12);
    verify($loaded[15]==='0' && $loaded[10]===[100],'typed values loaded with boolean false and options');
    ProductAttributes::replace($db,12,$selection);
    verify(count($db->calls)===4,'bulk replace performs two deletes and two inserts');
    verify($db->calls[0][1]==='#__minimarket_product_attribute_scalars'
        && $db->calls[1][1]==='#__minimarket_product_attribute_options',
        'only product attribute relations are deleted');
    verify(count($db->calls[2][2])===3 && count($db->calls[3][2])===5,
        'three scalars and five list selections stored');
    $db->calls=[];
    ProductAttributes::replace($db,12,['scalars'=>[],'options'=>[]]);
    verify(count($db->calls)===2,'clear-all removes only values without touching product');

    $form=simplexml_load_file(__DIR__ . '/../src/com_minimarket/admin/forms/product.xml');
    verify($form!==false,'product form valid');
    verify(count($form->xpath("//fieldset[@name='attributes']/field[@name='attribute_values' and @type='Productattributes']"))===1,
        'attributes visible in product editor');
    verify(count($form->xpath("//fieldset[@name='attributes']/field[@name='attributes_present']"))===1,
        'explicitly clearing all product attributes supported');
    verify(str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/admin/tmpl/product/edit.php'),
        "'attributes' => 'COM_MINIMARKET_PRODUCT_TAB_ATTRIBUTES'"),'attributes have their own product editor tab');
    echo 'PRODUCT_ATTRIBUTES_OK: ' . $checks . " cases\n";
}
