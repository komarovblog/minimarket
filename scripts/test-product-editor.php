<?php
/**
 * Product 0.5.0 source form + ProductTable validation, no Joomla or DB needed.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
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

namespace Joomla\CMS\Filter {
    final class OutputFilter
    {
        public static function stringURLSafe(string $text): string
        {
            return strtolower(trim($text));
        }
    }
}

namespace Joomla\CMS\Table {
    use Joomla\Database\DatabaseInterface;

    class Table
    {
        public int $id = 0;
        public string $error = '';
        public function __construct($table, $key, protected DatabaseInterface $db) {}
        public function setError(string $error): void { $this->error = $error; }
        public function getDbo(): DatabaseInterface { return $this->db; }
        public function check() { return true; }
    }
}

namespace MiniMarket\ProductSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Table\ProductTable;
    use MiniMarket\Component\Minimarket\Administrator\Table\CategoryTable;
    use MiniMarket\Component\Minimarket\Administrator\Service\MediaPath;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/MediaPath.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Table/ProductTable.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Table/CategoryTable.php';

    final class FakeQuery
    {
        public string $from = '';
        public array $conditions = [];
        public function select($columns): self { return $this; }
        public function from($table): self { $this->from = (string) $table; return $this; }
        public function where($condition): self { $this->conditions[] = (string) $condition; return $this; }
    }
    final class FakeDb implements DatabaseInterface
    {
        public ?FakeQuery $query = null;
        public function getQuery($new = true): FakeQuery { return new FakeQuery(); }
        public function quoteName($name, $as = null): string { return (string) $name; }
        public function quote($value): string { return "'" . str_replace("'", "''", (string) $value) . "'"; }
        public function setQuery($query): void { $this->query = $query; }
        public function loadResult(): int
        {
            return $this->query?->from === '#__minimarket_categories'
                && str_contains(implode(' ', $this->query->conditions), 'id = 1') ? 1 : 0;
        }
    }

    $checks = 0;
    function verify(bool $ok, string $label): void
    {
        global $checks;
        ++$checks;
        if (!$ok) { throw new \RuntimeException('PRODUCT_EDITOR_FAIL: ' . $label); }
    }

    $t = new ProductTable(new FakeDb());
    $t->title = 'Product';
    $t->alias = 'product';
    $t->sku = 'TEST-001';
    $t->price = '1990.50';
    $t->stock = '3';
    $t->category_id = 1;
    $t->state = 1;
    $t->sort_order = '0';
    $t->image = 'images/cover.jpg';
    $t->image_alt = 'Cover';
    $t->og_image = 'images/social.jpg';
    $t->meta_title = 'Title';
    $t->meta_description = 'Search description';
    $t->meta_robots = 'noindex,follow';
    $t->og_title = 'Social title';
    $t->og_description = 'Social description';
    $t->og_image_alt = 'Social cover';
    verify($t->check() === true, 'valid editor attributes accepted');
    verify($t->price === '1990.50' && $t->stock === 3, 'legacy trading values preserved');

    // Joomla Media's suffix must never reach category/product/OG database fields.
    $rich = 'images/powered_by.png#joomlaImage://local-images/powered_by.png?width=294&height=44';
    verify(MediaPath::normalize($rich) === 'images/powered_by.png', 'Joomla rich field gives one clean path');
    verify(MediaPath::normalize('images/powered_by.png') === 'images/powered_by.png', 'clean paths are unchanged');
    verify(MediaPath::normalize('') === '', 'empty selection stays empty');
    foreach ([
        'images/image.png#other://meta',
        'https://example.com/image.png',
        'images/../../configuration.php',
        'images/image.png?width=294',
        'images\image.png',
        'images/img.png#',
    ] as $invalidMedia) {
        verify(MediaPath::normalize($invalidMedia) === null, 'unsupported or unsafe media rejected');
    }

    $t->image = $rich;
    $t->og_image = $rich;
    verify($t->check() === true, 'product accepts native Joomla Media selection');
    verify($t->image === 'images/powered_by.png' && $t->og_image === 'images/powered_by.png',
        'product image and OG write one clean path, without dimensions');

    $category = new CategoryTable(new FakeDb());
    $category->title = 'Category';
    $category->alias = 'category';
    $category->parent_id = 0;
    $category->sort_order = 0;
    $category->show_children = -1;
    $category->show_products = -1;
    $category->image = $rich;
    $category->og_image = $rich;
    verify($category->check() === true, 'category accepts Joomla Media selection');
    verify($category->image === 'images/powered_by.png'
        && $category->og_image === 'images/powered_by.png',
        'category image and OG are normalized too');
    $category->image = 'images/picture.jpg#untrusted';
    verify(!$category->check(), 'unrecognized category image metadata rejected');


    $t->image = '../configuration.php';
    verify(!$t->check(), 'image traversal rejected');
    $t->image = 'images/cover.jpg';
    $t->og_image = 'https://example.org/a.jpg';
    verify(!$t->check(), 'external OG image rejected');
    $t->og_image = 'images/social.jpg';
    $t->meta_robots = 'none,all';
    verify(!$t->check(), 'unsupported robots rejected');
    $t->meta_robots = '';
    $t->sort_order = '4294967296';
    verify(!$t->check(), 'order overflow rejected');
    $t->sort_order = 0;
    $t->meta_title = str_repeat('a', 256);
    verify(!$t->check(), 'SEO title length rejected');
    $t->meta_title = 'Title';
    $t->price = '-1';
    verify(!$t->check(), 'negative price rejected');
    $t->price = '1990.50';
    $t->stock = '-1';
    verify(!$t->check(), 'negative stock rejected');
    $t->stock = '3';
    $t->category_id = 0;
    verify(!$t->check(), 'missing category rejected');
    $t->category_id = 1;
    verify($t->check(), 'validity restored after rejected values');

    $form = file_get_contents(__DIR__ . '/../src/com_minimarket/admin/forms/product.xml');
    verify($form !== false, 'product form exists');
    foreach (['introtext', 'description', 'image', 'image_alt', 'meta_title', 'meta_description',
        'meta_robots', 'og_title', 'og_description', 'og_image', 'og_image_alt'] as $field) {
        verify(str_contains($form, 'name="' . $field . '"'), 'form field ' . $field);
    }
    verify(substr_count($form, 'type="editor"') === 2, 'two Joomla visual editors');
    verify(str_contains($form, 'type="Productcategory"'), 'hierarchical product category picker');
    echo 'PRODUCT_EDITOR_OK: ' . $checks . " cases\n";
}
