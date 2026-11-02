<?php
/**
 * Isolated attribute/value dictionary validation gate.
 *
 * Run: php scripts/test-attributes.php
 * No Joomla application, database or external services needed.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($value);
        public function quote($value);
        public function setQuery($query);
        public function loadResult();
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string { return $key; }
    }
}

namespace Joomla\CMS\Table {
    use Joomla\Database\DatabaseInterface;

    class Table
    {
        public $id = 0;
        public $title = '';
        public $code = '';
        public $input_type = 'list';
        public $input_mode = 'auto';
        public $filter_mode = 'none';
        public $filter_multiple = 0;
        public $number_options = '';
        public $number_unit = '';
        public $number_min = null;
        public $number_max = null;
        public $number_step = null;
        public $is_variant = 0;
        public $is_multiple = 0;
        public $is_filterable = 0;
        public $sort_order = 0;
        public $state = 0;
        public $attribute_id = 0;
        public string $error = '';

        public function __construct(
            protected string $table,
            protected string $primary,
            protected DatabaseInterface $db
        ) {
        }

        public array $columnAliases = [];
        public function setColumnAlias(string $name, string $alias): void { $this->columnAliases[$name] = $alias; }
        public function setError(string $message): void { $this->error = $message; }
        public function getDbo(): DatabaseInterface { return $this->db; }
        public function check() { return true; }
    }
}

namespace MiniMarket\AttributesSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Table\AttributeTable;
    use MiniMarket\Component\Minimarket\Administrator\Table\ValueTable;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Table/AttributeTable.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Table/ValueTable.php';

    final class Query
    {
        public string $from = '';
        public array $where = [];
        public string $select = '';

        public function select($column): self
        {
            $this->select = (string) $column;
            return $this;
        }
        public function from($name): self
        {
            $this->from = (string) $name;
            return $this;
        }
        public function where($value): self
        {
            $this->where[] = (string) $value;
            return $this;
        }
        public function group($column): self { return $this; }
        public function having($condition): self { return $this; }
    }

    final class DB implements DatabaseInterface
    {
        private ?Query $query = null;
        public bool $duplicateAttribute = false;
        public bool $duplicateValue = false;
        public bool $existingValues = false;
        public bool $multipleProducts = false;
        public string $parentType = 'list';
        public array $queries = [];

        public function getQuery($new = true): Query { return new Query(); }
        public function quoteName($value): string { return (string) $value; }
        public function quote($value): string { return "'" . str_replace("'", "''", (string) $value) . "'"; }
        public function setQuery($query): void
        {
            $this->query = $query;
            $this->queries[] = $query;
        }
        public function loadResult()
        {
            if ($this->query?->select === 'input_type') {
                return $this->parentType;
            }

            if ($this->query?->from === '#__minimarket_attributes') {
                return $this->duplicateAttribute ? 1 : 0;
            }

            if ($this->query?->from === '#__minimarket_product_attribute_options') {
                return $this->multipleProducts ? 2 : 0;
            }

            if ($this->query?->from === '#__minimarket_attribute_values') {
                return ($this->duplicateValue || $this->existingValues) ? 1 : 0;
            }

            throw new \RuntimeException('Unexpected test query');
        }
    }

    $cases = 0;
    $errors = [];
    $check = static function (bool $ok, string $label) use (&$cases, &$errors) {
        ++$cases;

        if (!$ok) { $errors[] = $label; }
    };

    $db = new DB();
    $attribute = new AttributeTable($db);
    $attribute->title = ' Цвет ';
    $attribute->code = 'COLOR';
    $attribute->input_type = 'list';
    $attribute->is_variant = 1;
    $attribute->is_multiple = 1;
    $attribute->is_filterable = 1;
    $attribute->state = 1;
    $check($attribute->check() === true, 'color is a valid variant attribute');
    $check($attribute->columnAliases['published'] === 'state', 'attribute bulk publishing targets state');
    $check($attribute->code === 'color' && $attribute->title === 'Цвет', 'name and code normalized');
    // SKU flag and number of selectable values are independent settings.
    $attribute->is_variant = 0;
    $attribute->is_multiple = 1;
    $check($attribute->check() === true, 'ordinary attribute can offer multiple values');
    $attribute->is_variant = 1;
    $attribute->is_multiple = 0;
    $check($attribute->check() === true, 'variant-defining attribute can allow only one value');
    $attribute->is_multiple = 1;

    $attribute->input_type = 'text';
    $check($attribute->check() === false, 'text cannot define a variant');
    $attribute->input_type = 'number';
    $attribute->is_variant = 0;
    $check($attribute->check() === true, 'number allowed for shared properties');
    $attribute->input_type = 'list';
    $attribute->is_variant = 1;
    $attribute->id = 10;
    $attribute->is_multiple = 0;
    $db->multipleProducts = true;
    $check($attribute->check() === false, 'cannot switch multi list to single with used selections');
    $attribute->is_multiple = 1;
    $db->multipleProducts = false;
    $attribute->input_type = 'number';
    $attribute->is_variant = 0;
    $db->existingValues = true;
    $check($attribute->check() === false, 'cannot change list type with saved dictionary values');
    $db->existingValues = false;
    $attribute->input_type = 'list';
    $attribute->is_variant = 1;

    $attribute->is_multiple = 5;
    $check($attribute->check() === false, 'invalid multiple flag rejected');
    $attribute->is_multiple = 1;
    $attribute->is_variant = 0;
    $attribute->input_type = 'text';
    $check($attribute->check() === true && $attribute->is_multiple === 0,
        'non-list attribute clears hidden cardinality');
    $attribute->input_type = 'list';
    $attribute->is_variant = 1;
    $attribute->is_multiple = 1;

    $attribute->input_type = 'number';
    $attribute->is_variant = 0;
    $attribute->is_multiple = 0;
    $attribute->input_mode = 'presets';
    $attribute->number_options = '1000, 1500, 2000';
    $attribute->number_min = '500';
    $attribute->number_max = '2500';
    $attribute->number_step = '500';
    $attribute->number_unit = 'Вт';
    $attribute->is_filterable = 1;
    $attribute->filter_mode = 'slider';
    $check($attribute->check() === true, 'numeric presets and slider metadata valid');
    $check($attribute->number_options === '1000,1500,2000', 'numeric presets canonicalized');
    $attribute->filter_mode = 'radio';
    $check($attribute->check() === false, 'numeric radio filter not allowed');
    $attribute->filter_mode = 'checkboxes';
    $check($attribute->check() === true && $attribute->filter_multiple === 1,
        'numeric checkboxes imply multiple filter choices');
    $attribute->input_mode = 'radio';
    $check($attribute->check() === false, 'numeric property cannot use list radio input');
    $attribute->input_mode = 'auto';
    $attribute->number_options = '1000,1000.0000';
    $check($attribute->check() === false, 'duplicate numeric presets rejected');
    $attribute->number_options = '1000,1500,2000';
    $attribute->input_type = 'date';
    $attribute->filter_mode = 'date_range';
    $check($attribute->check() === true, 'date and date-range filter compatible');
    $attribute->input_type = 'url';
    $attribute->filter_mode = 'date_range';
    $check($attribute->check() === false, 'URL cannot use a date filter');
    $attribute->filter_mode = 'none';
    $attribute->input_type = 'textarea';
    $check($attribute->check() === true, 'multiline text supported without filter');
    $attribute->input_type = 'list';
    $attribute->is_variant = 1;
    $attribute->is_multiple = 1;
    $attribute->input_mode = 'checkboxes';
    $attribute->filter_mode = 'dropdown';
    $attribute->filter_multiple = 1;
    $check($attribute->check() === true, 'list multi and filter multi independent');
    $attribute->input_mode = 'radio';
    $check($attribute->check() === false, 'radio input cannot store multiple product values');
    $attribute->input_mode = 'auto';

    $attribute->code = 'color&illegal';
    $check($attribute->check() === false, 'invalid attribute code rejected');
    $attribute->code = 'color';
    $db->duplicateAttribute = true;
    $check($attribute->check() === false, 'duplicate attribute code rejected');
    $db->duplicateAttribute = false;

    $attribute->sort_order = -1;
    $check($attribute->check() === false, 'negative order rejected');
    $attribute->sort_order = 0;
    $attribute->state = 7;
    $check($attribute->check() === false, 'bad state rejected');
    $attribute->state = 1;
    $attribute->input_type = 'unknown';
    $check($attribute->check() === false, 'unsupported attribute type rejected');

    $value = new ValueTable($db);
    $value->attribute_id = 4;
    $value->title = ' Красный ';
    $value->code = 'RED';
    $value->state = 1;
    $check($value->check() === true, 'color dictionary entry accepted');
    $check($value->columnAliases['published'] === 'state', 'value bulk publishing targets state');
    $check($value->code === 'red' && $value->title === 'Красный', 'value normalized');
    $check(count($db->queries) > 1 && str_contains(
        implode(' ', $db->queries[count($db->queries) - 1]->where), 'attribute_id = 4'
    ), 'value uniqueness checked within its attribute');

    $db->duplicateValue = true;
    $check($value->check() === false, 'duplicate dictionary code rejected');
    $db->duplicateValue = false;
    $db->parentType = 'text';
    $check($value->check() === false, 'no dictionary values under text attributes');
    $db->parentType = '';
    $check($value->check() === false, 'missing attribute rejected');
    $db->parentType = 'list';
    $value->attribute_id = 0;
    $check($value->check() === false, 'no zero attribute ID');
    $value->attribute_id = 4;
    $value->code = 'красный';
    $check($value->check() === false, 'value code requires ASCII');
    $value->code = 'red';
    $value->sort_order = 4294967296;
    $check($value->check() === false, 'overflowed sort order rejected');

    if ($errors !== []) {
        fwrite(STDERR, 'ATTRIBUTES_TEST_FAIL: ' . implode('; ', $errors) . PHP_EOL);
        exit(1);
    }

    echo 'ATTRIBUTES_TEST_OK: ' . $cases . ' cases' . PHP_EOL;
}
