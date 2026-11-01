<?php
/** Pure PHP gallery contract: Joomla 6 Subform, relational links and safe paths. */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($value);
        public function quote($value);
        public function setQuery($query);
        public function loadAssocList();
        public function execute();
    }
}

namespace MiniMarket\GallerySmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\MediaPath;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductGallery;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/MediaPath.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/ProductGallery.php';

    $checks = 0;
    function verify(bool $ok, string $label): void
    {
        global $checks;
        ++$checks;
        if (!$ok) {
            throw new \RuntimeException('GALLERY_FAIL: ' . $label);
        }
    }

    $rich = 'images/side.png#joomlaImage://local-images/side.png?width=400&height=200';
    $rows = ProductGallery::normalizeRows([
        'row1' => ['path' => $rich, 'alt' => 'Left'],
        'row0' => ['path' => 'images/back.jpg', 'alt' => 'Back'],
    ], 'images/cover.jpg');
    verify(count($rows) === 2, 'two additional photos accepted');
    verify($rows[0]['path'] === 'images/side.png', 'Joomla suffix stripped');
    verify($rows[1]['path'] === 'images/back.jpg' && $rows[1]['sort_order'] === 1,
        'drag order preserved, keys ignored');
    verify($rows[0]['alt'] === 'Left', 'alt independently preserved');
    verify(ProductGallery::normalizeRows([]) === [], 'removing every relation permitted');
    verify(ProductGallery::normalizeRows('{}') === [], 'empty JSON object accepted');

    $invalid = [
        [['path' => '', 'alt' => '']], // blank row cannot silently wipe gallery
        [['path' => '../secret.php', 'alt' => '']],
        [['path' => 'https://other.example/a.jpg', 'alt' => '']],
        [['path' => 'images/cover.jpg', 'alt' => '']], // no duplicate cover
        [['path' => 'images/a.jpg'], ['path' => 'images/a.jpg']], // no duplicates
        [['path' => 'images/a.jpg#other', 'alt' => '']],
        [['path' => 'images/a.jpg', 'alt' => str_repeat('x', 256)]],
        array_fill(0, ProductGallery::MAX_IMAGES + 1, ['path' => 'images/a.jpg']),
    ];
    foreach ($invalid as $i => $case) {
        try {
            ProductGallery::normalizeRows($case, 'images/cover.jpg');
            verify(false, 'invalid gallery rejected ' . $i);
        } catch (\InvalidArgumentException $e) {
            verify(true, 'invalid gallery rejected ' . $i);
        }
    }

    // Form source is an actual Joomla Subform with add/remove/move and sentinel.
    $main = simplexml_load_file(__DIR__ . '/../src/com_minimarket/admin/forms/product.xml');
    $sub = simplexml_load_file(__DIR__ . '/../src/com_minimarket/admin/forms/product_gallery.xml');
    verify($main !== false && $sub !== false, 'both forms parse');
    verify((string) ($main->xpath("//field[@name='gallery']")[0]['type'] ?? '') === 'subform',
        'gallery is standard Joomla repeatable form');
    verify((string) ($main->xpath("//field[@name='gallery']")[0]['multiple'] ?? '') === 'true',
        'gallery supports repeated rows');
    verify((string) ($main->xpath("//field[@name='gallery_present']")[0]['default'] ?? '') === '1',
        'empty gallery post sentinel present');
    verify((string) ($sub->xpath("//field[@name='path']")[0]['type'] ?? '') === 'media',
        'each row uses Joomla Media');
    verify((string) ($sub->xpath("//field[@name='alt']")[0]['type'] ?? '') === 'text',
        'each row has alt');

    // One set of SQL operations per product rather than N file uploads.
    final class Query
    {
        public string $mode = '';
        public array $values = [];
        public function select($x): self { $this->mode = 'select'; return $this; }
        public function from($x): self { return $this; }
        public function where($x): self { return $this; }
        public function order($x): self { return $this; }
        public function delete($x): self { $this->mode = 'delete'; return $this; }
        public function insert($x): self { $this->mode = 'insert'; return $this; }
        public function columns($x): self { return $this; }
        public function values($x): self { $this->values[] = $x; return $this; }
    }
    final class FakeDb implements DatabaseInterface
    {
        public ?Query $active = null;
        public array $operations = [];
        public function getQuery($new = true): Query { return new Query(); }
        public function quoteName($value): mixed { return $value; }
        public function quote($value): string { return "'" . str_replace("'", "''", (string) $value) . "'"; }
        public function setQuery($query): void { $this->active = $query; }
        public function loadAssocList(): array
        {
            return [['path' => 'images/side.png', 'alt' => 'Side'],
                ['path' => 'images/back.jpg', 'alt' => 'Back']];
        }
        public function execute(): void { $this->operations[] = [$this->active->mode, $this->active->values]; }
    }

    $db = new FakeDb();
    $loaded = ProductGallery::loadFormRows($db, 12);
    verify(array_keys($loaded) === ['row0', 'row1'], 'stable row keys supplied to Joomla');
    verify($loaded['row1']['alt'] === 'Back', 'loaded image alt');
    ProductGallery::replaceRows($db, 12, $rows);
    verify($db->operations[0][0] === 'delete', 'only relations deleted before replacement');
    verify($db->operations[1][0] === 'insert'
        && count($db->operations[1][1]) === 2, 'two ordered relationship rows inserted');
    $db->operations = [];
    ProductGallery::replaceRows($db, 12, []);
    verify(count($db->operations) === 1 && $db->operations[0][0] === 'delete',
        'remove all only deletes link rows');
    verify(ProductGallery::loadFormRows($db, 0) === [], 'new product gallery empty');

    echo 'PRODUCT_GALLERY_OK: ' . $checks . " cases\n";
}
