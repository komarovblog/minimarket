<?php
/**
 * Isolated test of the MiniMarket component Options menu selector.
 * Run: php scripts/test-shopmenu.php
 */
declare(strict_types=1);

namespace Joomla\CMS\Form\Field {
    abstract class ListField
    {
        protected function getOptions()
        {
            return [(object) ['value' => 0, 'text' => 'Automatic']];
        }
    }
}

namespace Joomla\CMS\HTML {
    final class HTMLHelper
    {
        public static function _(string $method, $value, string $label): object
        {
            return (object) ['value' => $value, 'text' => $label];
        }
    }
}

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
        public function quote($value);
        public function setQuery($query);
        public function loadObjectList();
    }
}

namespace Joomla\CMS {
    final class Factory
    {
        public static object $container;

        public static function getContainer(): object
        {
            return self::$container;
        }
    }
}

namespace MiniMarket\ShopMenuTest {
    use Joomla\CMS\Factory;
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Field\ShopmenuField;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Field/ShopmenuField.php';

    final class FakeQuery
    {
        public array $conditions = [];

        public function select($value): self { return $this; }
        public function from($value): self { return $this; }
        public function join($type, $value): self { return $this; }
        public function order($value): self { return $this; }
        public function where($value): self { $this->conditions[] = (string) $value; return $this; }
    }

    final class FakeDb implements DatabaseInterface
    {
        public ?FakeQuery $query = null;

        public function getQuery($new = true) { return new FakeQuery(); }
        public function quoteName($name, $as = null) { return (string) $name; }
        public function quote($value) { return "'" . (string) $value . "'"; }
        public function setQuery($query) { $this->query = $query; }
        public function loadObjectList() {
            return [
                (object) ['id' => 31, 'title' => 'Catalog', 'path' => 'catalog', 'home' => 0,
                    'link' => 'index.php?option=com_minimarket&view=catalog', 'language' => '*'],
                (object) ['id' => 45, 'title' => 'Home', 'path' => 'home', 'home' => 1,
                    'link' => 'index.php?view=catalog&option=com_minimarket', 'language' => '*'],
                (object) ['id' => 46, 'title' => 'Not storefront', 'path' => 'bad', 'home' => 0,
                    'link' => 'index.php?option=com_minimarket&view=product&id=1', 'language' => '*'],
            ];
        }
    }

    final class TestingField extends ShopmenuField
    {
        public function optionsForTest(): array { return $this->getOptions(); }
    }

    $db = new FakeDb();
    Factory::$container = new class($db) {
        public function __construct(private FakeDb $db) {}
        public function get(string $type): FakeDb { return $this->db; }
    };

    $options = (new TestingField())->optionsForTest();
    $errors = [];
    $tests = 0;
    $check = static function (bool $ok, string $label) use (&$tests, &$errors): void {
        ++$tests;
        if (!$ok) { $errors[] = $label; }
    };

    $check(count($options) === 3, 'Automatic and two storefront links only');
    $check($options[0]->value === 0, 'Automatic is first choice');
    $check($options[1]->value === 31, 'Catalog ID chosen');
    $check(str_contains($options[1]->text, '/catalog'), 'Catalog path visible');
    $check($options[2]->value === 45, 'Home ID chosen');
    $check(str_contains($options[2]->text, '(/,'), 'Home path displays /');
    $sql = implode(' ', $db->query->conditions);
    $check(str_contains($sql, 'm.published = 1'), 'Unpublished records excluded by SQL');
    $check(str_contains($sql, "m.type = 'component'"), 'Only ordinary menu type');
    $check(str_contains($sql, 'm.client_id = 0'), 'Only site menu');
    $check(str_contains($sql, "e.element = 'com_minimarket'"), 'Only MiniMarket component');
    $check(!ShopmenuField::isStorefrontLink('index.php?option=com_content&view=category'),
        'Foreign link rejected');
    $check(!ShopmenuField::isStorefrontLink('index.php?option=com_minimarket&view=category'),
        'Category link rejected');
    $check(ShopmenuField::isStorefrontLink('index.php?view=catalog&option=com_minimarket'),
        'Parameter order independent');

    if ($errors) {
        fwrite(STDERR, 'SHOPMENU_TEST_FAIL: ' . implode('; ', $errors) . PHP_EOL);
        exit(1);
    }

    echo 'SHOPMENU_TEST_OK: ' . $tests . ' cases' . PHP_EOL;
}
