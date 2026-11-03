<?php
/** Regression: Joomla attribute list must not query a dropped SQL column. */
declare(strict_types=1);
namespace Joomla\CMS\MVC\Model {
    class ListModel {
        public function getDatabase(): object { return \MiniMarket\AttributeListRegression\TestDb::$db; }
        protected function getState(string $key, mixed $default = null): mixed { return $default; }
    }
}
namespace MiniMarket\AttributeListRegression {
    \define('_JEXEC', 1);
    final class Query {
        public array $columns = [];
        public array $orders = [];
        public string $table = '';
        public function select(array|string $columns): self {
            array_push($this->columns,...(array)$columns);
            return $this;
        }
        public function from(string $table): self { $this->table = $table; return $this; }
        public function where(string $condition): self { return $this; }
        public function order(string $order): self { $this->orders[] = $order; return $this; }
    }
    final class TestDb {
        public static self $db;
        public function getQuery(bool $new = true): Query { return new Query(); }
        public function quoteName(string $name, ?string $alias = null): string {
            return $name . ($alias === null ? '' : ' AS ' . $alias);
        }
        public function quote(string $value, bool $escape = true): string {
            return "'" . str_replace("'","''",$value) . "'";
        }
        public function escape(string $value, bool $extra = false): string { return $value; }
    }
    TestDb::$db = new TestDb();
    require __DIR__ . '/../src/com_minimarket/admin/src/Model/AttributesModel.php';
    $model = new \MiniMarket\Component\Minimarket\Administrator\Model\AttributesModel();
    $query = (new \ReflectionMethod($model, 'getListQuery'))->invoke($model);
    $expected = [
        'a.id','a.title','a.code','a.input_type','a.is_filterable','a.state','a.sort_order'
    ];
    if ($query->columns !== $expected || $query->table !== '#__minimarket_attributes AS a') {
        throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: unexpected selected columns');
    }
    $install = file_get_contents(__DIR__ . '/../src/com_minimarket/admin/sql/install.mysql.sql');
    if (!preg_match('~CREATE TABLE IF NOT EXISTS \x60#__minimarket_attributes\x60\s*\((.*?)\)\s*ENGINE=~s',
        $install, $matches)) {
        throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: missing fresh-install table');
    }
    preg_match_all('/^\s*\x60([a-z_]+)\x60\s+/m',$matches[1],$found);
    $known = array_flip($found[1]);
    foreach ($query->columns as $quoted) {
        $column = substr($quoted,2);
        if (!isset($known[$column])) {
            throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: SQL asks for dropped '.$column);
        }
    }
    if (isset($known['is_variant'])) {
        throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: old SKU flag still in fresh schema');
    }
    $view=file_get_contents(__DIR__.'/../src/com_minimarket/admin/tmpl/attributes/default.php');
    if (str_contains($view,'is_variant') || str_contains($view,'ATTRIBUTE_VARIANT')) {
        throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: orphaned column in Joomla list');
    }
    preg_match_all('~<th\b~',$view,$heads);
    preg_match_all('~<td\b~',$view,$cells);
    if (count($heads[0]) !== 7 || count($cells[0]) !== 8
        || !str_contains($view,'colspan="7"')) {
        throw new \RuntimeException('ATTRIBUTE_LIST_QUERY_FAIL: table columns inconsistent');
    }
    echo "ATTRIBUTE_LIST_QUERY_PASS: model selects 7 existing columns, template matches\n";
}
