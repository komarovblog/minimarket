<?php
/**
 * MiniMarket 0.5.9 dictionary ordering contract.
 * No Joomla site or live DB is altered. Real browser save still needs smoke.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new = true);
        public function quoteName($name);
        public function setQuery($query);
        public function loadColumn();
        public function execute();
    }
}
namespace MiniMarket\AttributeOrderingSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueOrdering;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeValueOrdering.php';

    $count = 0;
    function ok(bool $condition, string $message): void {
        global $count;
        ++$count;
        if (!$condition) throw new \RuntimeException('ATTRIBUTE_ORDER_FAIL: ' . $message);
    }

    final class Query {
        public string $table = '';
        public string $update = '';
        public array $where = [];
        public function select($v): self {return $this;}
        public function from($v): self {$this->table=(string)$v;return $this;}
        public function where($v): self {$this->where[]=(string)$v;return $this;}
        public function update($v): self {$this->table=(string)$v;return $this;}
        public function set($v): self {$this->update=(string)$v;return $this;}
    }
    final class Db implements DatabaseInterface {
        public ?Query $q=null;
        public int $writes=0;
        public function getQuery($new = true): Query {return new Query();}
        public function quoteName($v): mixed {return $v;}
        public function setQuery($q): void {$this->q=$q;}
        public function loadColumn(): array {return [8,2,5];}
        public function execute(): void {$this->writes++;}
    }
    $db=new Db();
    ok(AttributeValueOrdering::ids($db,0)===[], 'new attribute has no values');
    ok(AttributeValueOrdering::ids($db,3)===[8,2,5], 'value IDs fetched from current parent');
    ok(str_contains(implode(' ', $db->q->where), 'attribute_id = 3'), 'select restricted to parent');

    $normal=AttributeValueOrdering::normalize(
        ['5'=>'40','2'=>'10','8'=>'0'], [8,2,5]
    );
    ok($normal===[5=>40,2=>10,8=>0], 'values accepted regardless of input order');
    ok(AttributeValueOrdering::normalize([],[])===[], 'empty list accepted');
    ok(AttributeValueOrdering::normalize([8=>4294967295],[8])===[8=>4294967295],
        'max unsigned integer accepted');
    foreach ([
        [[2=>5,8=>9],[2,8,5]], // missing ID in stale edit
        [[2=>5,8=>9,5=>2,11=>3],[2,8,5]], // injected ID
        [[2=>5,8=>9,5=>'-1'],[2,8,5]],
        [[2=>5,8=>9,5=>'4294967296'],[2,8,5]],
        [[2=>5,8=>9,5=>'1e4'],[2,8,5]],
        [[2=>5,8=>9,5=>['invalid']],[2,8,5]],
        ['not an array',[2,8,5]],
        [null,[2,8,5]],
    ] as [$raw,$allowed]) {
        try {
            AttributeValueOrdering::normalize($raw,$allowed);
            ok(false,'bad ordering rejected');
        } catch (\InvalidArgumentException $expected) {
            ok(true,'bad ordering rejected');
        }
    }

    AttributeValueOrdering::apply($db, 3, $normal);
    ok($db->writes===1,'batch update executes once, not once per value');
    ok(str_contains($db->q->update, 'CASE id WHEN') && str_contains($db->q->update, 'WHEN 2 THEN 10'),
        'case statement uses validated IDs and positions');
    ok(str_contains(implode(' ', $db->q->where),'attribute_id = 3'),
        'update is restricted to selected attribute');
    ok(str_contains(implode(' ', $db->q->where),'id IN (5,2,8)'),
        'update is restricted to current value IDs');
    AttributeValueOrdering::apply($db, 3, []);
    ok($db->writes===1,'empty values do not write');
    try {
        AttributeValueOrdering::apply($db, 0, $normal);
        ok(false,'invalid parent rejected');
    } catch (\InvalidArgumentException $expected) {
        ok(true,'invalid parent rejected');
    }

    $dir=__DIR__.'/../src/com_minimarket/';
    $form=simplexml_load_file($dir.'admin/forms/attribute.xml');
    ok($form!==false,'attribute XML parses');
    ok(count($form->xpath("//field[@name='values_order' and @filter='raw']"))===1,
        'Joomla accepts posted map field');
    ok(count($form->xpath("//field[@name='values_order_present']"))===1,
        'posted ordering sentinel registered');
    $edit=file_get_contents($dir.'admin/tmpl/attribute/edit.php');
    ok(str_contains($edit, 'jform[values_order]') && str_contains($edit, 'data-mm-values-sort'),
        'inline list holds editable order and view sorting');
    ok(!str_contains($edit, 'COM_MINIMARKET_EDIT_VALUES'),
        'duplicate navigation button removed');
    ok(str_contains($edit, '&return='), 'add/edit links preserve attribute return context');
    $valueForm=file_get_contents($dir.'admin/tmpl/value/edit.php');
    ok(str_contains($valueForm, 'name="return"'), 'save and cancel preserve parent return URL');
    $standalone=file_get_contents($dir.'admin/tmpl/values/default.php');
    ok(str_contains($standalone,'COM_MINIMARKET_BACK_ATTRIBUTE'),
        'standalone list returns to exact attribute');
    ok(str_contains($standalone,'sort=title') && str_contains($standalone,'sort=order'),
        'standalone list also supports alphabetic and manual sorting');
    $model=file_get_contents($dir.'admin/src/Model/AttributeModel.php');
    ok(str_contains($model,'AttributeValueOrdering::apply'),
        'manual value positions saved within attribute transaction');
    $client=file_get_contents($dir.'media/js/attribute-values-sort.js');
    ok(str_contains($client,'localeCompare') && str_contains($client, 'data-mm-values-sort'),
        'alpha sorting is client-side and locale-aware');

    echo 'ATTRIBUTE_VALUE_ORDER_OK: ' . $count . " cases\n";
}
