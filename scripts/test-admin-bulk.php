<?php
/** Pure PHP Joomla 6 bulk list and bidirectional category assignment contracts. */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name);
        public function setQuery($query);
        public function loadResult();
        public function loadAssocList();
        public function execute();
    }
}
namespace MiniMarket\BulkSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
    use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/BulkDeleteGuard.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryAttributes.php';

    $total = 0;
    function verify(bool $ok, string $label): void
    {
        global $total;
        ++$total;
        if (!$ok) { throw new \RuntimeException('ADMIN_BULK_FAIL: ' . $label); }
    }
    final class Query
    {
        public string $type = '';
        public string $table = '';
        public array $where = [];
        public array $values = [];
        public function select($x): self { $this->type = 'select'; return $this; }
        public function from($x): self { $this->table = (string) $x; return $this; }
        public function where($x): self { $this->where[] = (string) $x; return $this; }
        public function delete($x): self { $this->type = 'delete'; $this->table = (string) $x; return $this; }
        public function insert($x): self { $this->type = 'insert'; $this->table = (string) $x; return $this; }
        public function columns($x): self { return $this; }
        public function values($x): self { $this->values[] = $x; return $this; }
    }
    final class Db implements DatabaseInterface
    {
        public ?Query $query = null;
        public array $blocked = [];
        public array $operations = [];
        public function getQuery($new = true): Query { return new Query(); }
        public function quoteName($name): mixed { return $name; }
        public function setQuery($query): void { $this->query = $query; }
        public function loadResult(): int { return (int) ($this->blocked[$this->query->table] ?? 0); }
        public function loadAssocList(): array {
            return [
                ['category_id' => 3, 'enabled' => 1],
                ['category_id' => 5, 'enabled' => 0],
            ];
        }
        public function execute(): void {
            $this->operations[] = [$this->query->type, $this->query->table, $this->query->values];
        }
    }

    verify(BulkDeleteGuard::normalizeIds([1,'1','002','0',-2,'junk',3])
        === [1,2,3], 'only unique positive selected IDs');
    verify(BulkDeleteGuard::normalizeIds('1') === [], 'non-array IDs are invalid');
    $db = new Db();
    BulkDeleteGuard::assertNoDependencies($db,'category',[1,2]);
    verify(str_contains(implode(' ', $db->query->where), '1,2'),
        'selected IDs remain numerical in dependency query');
    $db->blocked['#__minimarket_products'] = 2;
    try {
        BulkDeleteGuard::assertNoDependencies($db,'category',[1]);
        verify(false,'category with products is protected');
    } catch (\RuntimeException $error) {
        verify(true,'category with products is protected');
    }
    $db->blocked = ['#__minimarket_categories' => 1];
    try {
        BulkDeleteGuard::assertNoDependencies($db,'category',[1,2]);
        verify(false,'category with descendants is protected');
    } catch (\RuntimeException $error) {
        verify(true,'category with descendants is protected');
    }
    $db->blocked = ['#__minimarket_category_attributes' => 1];
    try {
        BulkDeleteGuard::assertNoDependencies($db,'attribute',[8]);
        verify(false,'attribute assigned in a category is protected');
    } catch (\RuntimeException $error) {
        verify(true,'attribute assigned in a category is protected');
    }
    $db->blocked = ['#__minimarket_attribute_values' => 1];
    try {
        BulkDeleteGuard::assertNoDependencies($db,'attribute',[8]);
        verify(false,'attribute with values is protected');
    } catch (\RuntimeException $error) {
        verify(true,'attribute with values is protected');
    }

    $db = new Db();
    verify(CategoryAttributes::loadForAttribute($db,8) === [3=>1,5=>0],
        'reverse editor reads category assignment modes');
    CategoryAttributes::replaceForAttribute($db,8,[3=>1,5=>0]);
    verify($db->operations[0][0] === 'delete'
        && $db->operations[0][1] === '#__minimarket_category_attributes',
        'reverse save deletes only entries in relationship table');
    verify(str_contains(implode(' ',$db->query->values), ',8,'),
        'reverse insert contains current attribute id');
    verify(count($db->operations[1][2]) === 2, 'both selected direct modes stored');
    $db->operations = [];
    CategoryAttributes::replaceForAttribute($db,8,[]);
    verify(count($db->operations) === 1, 'inherit all deletes only direct rules');
    verify(CategoryAttributes::loadForAttribute($db,0) === [],
        'new attribute has no existing category links');

    $root = __DIR__ . '/../src/com_minimarket/admin/';
    $xml = simplexml_load_file($root . 'forms/attribute.xml');
    verify($xml !== false, 'reverse attribute form is valid XML');
    verify(count($xml->xpath("//fieldset[@name='categories']/field[@name='category_rules' and @type='Attributecategories']")) === 1,
        'reverse category selection exists');
    verify(count($xml->xpath("//fieldset[@name='categories']/field[@name='categories_present']")) === 1,
        'explicit empty form marker exists');

    foreach ([
        'categories' => 'Category',
        'products' => 'Product',
        'attributes' => 'Attribute',
        'values' => 'Value',
    ] as $plural => $singular) {
        $controller = file_get_contents($root . 'src/Controller/' . ucfirst($plural) . 'Controller.php');
        $view = file_get_contents($root . 'src/View/' . ucfirst($plural) . '/HtmlView.php');
        $layout = file_get_contents($root . 'tmpl/' . $plural . '/default.php');
        $table = file_get_contents($root . 'src/Table/' . $singular . 'Table.php');
        verify(str_contains($controller, 'extends AdminController')
            && str_contains($controller, "getModel("), $plural . ' uses native bulk controller');
        verify(str_contains($view, "ToolbarHelper::publishList")
            && str_contains($view, "ToolbarHelper::unpublishList")
            && str_contains($view, "ToolbarHelper::deleteList"), $plural . ' has bulk actions');
        verify(str_contains($layout, "grid.checkall")
            && str_contains($layout, "grid.id")
            && str_contains($layout, 'name="boxchecked"')
            && str_contains($layout, 'COM_MINIMARKET_BULK_PUBLISHED'),
            $plural . ' has select all, select row and published column');
        verify(str_contains($table, "setColumnAlias('published', 'state')"),
            $plural . ' maps built-in Joomla publication to state');
    }

    echo 'ADMIN_BULK_OK: ' . $total . " cases\n";
}
