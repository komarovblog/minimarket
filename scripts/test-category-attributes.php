<?php
/** Category attribute inheritance and SQL write contract; pure PHP, no Joomla DB. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name);
        public function setQuery($query);
        public function loadAssocList();
        public function loadColumn();
        public function execute();
    }
}
namespace MiniMarket\CategoryAttributeSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryAttributes.php';
    $count = 0;
    function verify(bool $ok, string $label): void
    {
        global $count;
        ++$count;
        if (!$ok) {
            throw new \RuntimeException('CATEGORY_ATTRIBUTES_FAIL: ' . $label);
        }
    }

    // Root 1 includes color and size; child 2 excludes size, grandchild 3
    // re-enables it. Sibling 4 inherits unchanged; unrelated root 5 empty.
    $parents = [1=>0,2=>1,3=>2,4=>1,5=>0,6=>7,7=>6];
    $rules = [1=>[10=>1,11=>1],2=>[11=>0,12=>1],3=>[11=>1],5=>[]];
    verify(CategoryAttributes::resolve(1,$parents,$rules) === [10=>1,11=>1],
        'root enables two attributes');
    verify(CategoryAttributes::resolve(2,$parents,$rules) === [10=>1,12=>2],
        'child inherited color, excluded size, added material');
    verify(CategoryAttributes::resolve(3,$parents,$rules) === [10=>1,12=>2,11=>3],
        'grandchild can re-enable previously blocked attribute');
    verify(CategoryAttributes::resolve(4,$parents,$rules) === [10=>1,11=>1],
        'sibling inherits without duplicate rules');
    verify(CategoryAttributes::resolve(5,$parents,$rules) === [], 'unrelated root isolated');
    verify(CategoryAttributes::resolve(404,$parents,$rules) === [], 'orphan isolated');
    verify(CategoryAttributes::resolve(6,$parents,$rules) === [], 'cycle isolated');

    $requiredRules = [1=>[10=>2,11=>1],2=>[10=>1,12=>2],3=>[10=>2]];
    verify(CategoryAttributes::resolve(2,$parents,$requiredRules) === [10=>2,11=>1,12=>2],
        'required and optional attributes are both eligible');
    verify(CategoryAttributes::resolveModes(1,$parents,$requiredRules) === [10=>2,11=>1],
        'root required vs optional modes');
    verify(CategoryAttributes::requiredForCategory(1,$parents,$requiredRules) === [10],
        'required inherited root property');
    verify(CategoryAttributes::requiredForCategory(2,$parents,$requiredRules) === [12],
        'child makes inherited required optional and another required');
    verify(CategoryAttributes::requiredForCategory(3,$parents,$requiredRules) === [10,12],
        'grandchild overrides inherited optional back to required');
    verify(CategoryAttributes::requiredForCategory(6,$parents,$requiredRules) === [],
        'cycles produce no required properties');

    $all = [10,11,12];
    verify(CategoryAttributes::normalizeRules(['10'=>'1','11'=>'-1','12'=>'0'],$all)
        === [10=>1,12=>0], 'inherit removes direct override and explicit block persists');
    verify(CategoryAttributes::normalizeRules(['10'=>'2','11'=>'1','12'=>'0'],$all)
        === [10=>2,11=>1,12=>0], 'required/optional/excluded saved independently');
    verify(CategoryAttributes::normalizeRules([], $all) === [], 'clear-all reverts to inherit');
    verify(CategoryAttributes::normalizeRules(null, $all) === [], 'legacy form absent harmless');
    foreach ([
        [99=>1], [10=>3], [10=>'invalid'], ['a'=>1],
        [10=>['1']], [0=>1], ['10'=>'1','404'=>'0'],
    ] as $bad) {
        try {
            CategoryAttributes::normalizeRules($bad, $all);
            verify(false, 'invalid assignment rejected');
        } catch (\InvalidArgumentException $e) {
            verify(true, 'invalid assignment rejected');
        }
    }

    final class Query
    {
        public string $mode = '';
        public string $table = '';
        public array $rows = [];
        public function select($x): self { $this->mode = 'select'; return $this; }
        public function from($x): self { $this->table = $x; return $this; }
        public function where($x): self { return $this; }
        public function delete($x): self { $this->mode = 'delete'; $this->table = $x; return $this; }
        public function insert($x): self { $this->mode = 'insert'; $this->table = $x; return $this; }
        public function columns($x): self { return $this; }
        public function values($x): self { $this->rows[] = $x; return $this; }
    }
    final class Db implements DatabaseInterface
    {
        public ?Query $q = null;
        public array $writes = [];
        public function getQuery($new = true): Query { return new Query(); }
        public function quoteName($name): mixed { return $name; }
        public function setQuery($query): void { $this->q = $query; }
        public function loadAssocList(): array {
            return match ($this->q->table) {
                '#__minimarket_category_attributes' => [
                    ['category_id'=>1,'attribute_id'=>10,'enabled'=>1,'required'=>0],
                    ['category_id'=>2,'attribute_id'=>11,'enabled'=>0,'required'=>0],
                    ['category_id'=>2,'attribute_id'=>12,'enabled'=>1,'required'=>1],
                ],
                '#__minimarket_categories' => [
                    ['id'=>1,'parent_id'=>0],
                    ['id'=>2,'parent_id'=>1],
                ],
                default => [],
            };
        }
        public function loadColumn(): array { return [10, 11, 12]; }
        public function execute(): void { $this->writes[] = [$this->q->mode,$this->q->table,$this->q->rows]; }
    }
    $db = new Db();
    verify(CategoryAttributes::allowedIds($db) === [10,11,12], 'all valid ids checked on server');
    verify(CategoryAttributes::loadDirect($db,2) === [10=>1,11=>0,12=>2],
        'explicit rules use same category-only lookup');
    $hierarchy = CategoryAttributes::loadHierarchy($db);
    verify($hierarchy['parents'] === [1=>0,2=>1], 'one all-category query');
    verify($hierarchy['rules'] === [1=>[10=>1],2=>[11=>0,12=>2]], 'one all-rules query');
    CategoryAttributes::replaceDirect($db,2,[10=>1,11=>0,12=>2]);
    verify($db->writes[0][0]==='delete' && $db->writes[1][0]==='insert',
        'writes delete and replace only direct links');
    verify(count($db->writes[1][2])===3, 'only three direct rules inserted');
    verify(str_ends_with($db->writes[1][2][2], ',1,1'),
        'required mode is persisted as enabled=1 required=1');
    $db->writes = [];
    CategoryAttributes::replaceDirect($db,2,[]);
    verify(count($db->writes)===1 && $db->writes[0][0]==='delete',
        'clear all removes links only');
    verify(CategoryAttributes::loadDirect($db,0) === [], 'new category has no direct links');

    $form = simplexml_load_file(__DIR__ . '/../src/com_minimarket/admin/forms/category.xml');
    verify($form !== false, 'form parse');
    verify((string) ($form->xpath("//fieldset[@name='attributes']/field[@name='attribute_rules']")[0]['type'] ?? '')
        === 'Categoryattributes', 'category has real attribute selection field');
    verify((string) ($form->xpath("//fieldset[@name='attributes']/field[@name='attributes_present']")[0]['default'] ?? '')
        === '1', 'removing all overrides is intentional');
    verify(str_contains(file_get_contents(__DIR__ . '/../src/com_minimarket/admin/tmpl/category/edit.php'),
        "'attributes' => 'COM_MINIMARKET_CATEGORY_TAB_ATTRIBUTES'"), 'category attributes visible as tab');
    echo 'CATEGORY_ATTRIBUTES_OK: ' . $count . " cases\n";
}
