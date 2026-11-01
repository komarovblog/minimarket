<?php
/**
 * Isolated Joomla 6 menu-save event + DB scope tests.
 *
 * Command: php scripts/test-menu-sync.php
 * No live Joomla installation, database or external network required.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
        public function quote($value);
        public function setQuery($query);
        public function loadObjectList();
        public function execute();
    }

    trait DatabaseAwareTrait
    {
        private ?DatabaseInterface $database = null;

        public function setDatabase(DatabaseInterface $database): void
        {
            $this->database = $database;
        }

        public function getDatabase(): DatabaseInterface
        {
            return $this->database;
        }
    }
}

namespace Joomla\CMS\Plugin {
    class CMSPlugin
    {
        public function __construct(array $config = [])
        {
        }
    }
}

namespace Joomla\Event {
    interface SubscriberInterface
    {
        public static function getSubscribedEvents(): array;
    }
}

namespace Joomla\CMS\Event\Model {
    class AfterSaveEvent
    {
        public function __construct(private string $context, private object $item)
        {
        }

        public function getContext(): string
        {
            return $this->context;
        }

        public function getItem(): object
        {
            return $this->item;
        }
    }
}

namespace MiniMarket\MenuSyncSmoke {
    use Joomla\CMS\Event\Model\AfterSaveEvent;
    use Joomla\Database\DatabaseInterface;
    use Joomla\Plugin\Content\Minimarketmenu\Extension\Minimarketmenu;
    use Joomla\Plugin\Content\Minimarketmenu\Service\PrimaryMenuSync;

    \define('_JEXEC', 1);
    require_once __DIR__ . '/../src/plg_content_minimarketmenu/src/Service/PrimaryMenuSync.php';
    require_once __DIR__ . '/../src/plg_content_minimarketmenu/src/Extension/Minimarketmenu.php';

    final class Query
    {
        public string $action = 'read';
        public array $conditions = [];
        public array $sets = [];
        public string $table = '';

        public function select($cols): self
        {
            return $this;
        }

        public function from($table): self
        {
            $this->table = (string) $table;
            return $this;
        }

        public function update($table): self
        {
            $this->action = 'update';
            $this->table = (string) $table;
            return $this;
        }

        public function set($value): self
        {
            $this->sets[] = (string) $value;
            return $this;
        }

        public function where($value): self
        {
            $this->conditions[] = (string) $value;
            return $this;
        }
    }

    final class Db implements DatabaseInterface
    {
        public ?Query $current = null;
        public array $updates = [];
        public array $reads = [];
        public array $rows = [];

        public function __construct()
        {
            $this->rows = [
                (object) ['id' => 45, 'link' => 'index.php?option=com_minimarket&view=catalog',
                    'params' => '{"shop_primary_route":1,"show_categories":0,"show_products":1}'],
                (object) ['id' => 53, 'link' => 'index.php?option=com_minimarket&view=catalog',
                    'params' => '{"shop_primary_route":1,"products_order":"name"}'],
                (object) ['id' => 54, 'link' => 'index.php?option=com_minimarket&view=catalog',
                    'params' => '{"shop_primary_route":0,"show_categories":1}'],
                (object) ['id' => 55, 'link' => 'index.php?option=com_minimarket&view=category&id=2',
                    'params' => '{"shop_primary_route":1,"show_categories":1}'],
                (object) ['id' => 56, 'link' => 'index.php?option=com_content&view=category&id=2',
                    'params' => '{"shop_primary_route":1,"show_categories":1}'],
                (object) ['id' => 57, 'link' => 'index.php?option=com_minimarket&view=catalog',
                    'params' => 'not valid json'],
            ];
        }

        public function getQuery($new = true): Query
        {
            return new Query();
        }

        public function quoteName($name, $as = null): string
        {
            return (string) $name;
        }

        public function quote($value): string
        {
            return "'" . str_replace("'", "''", (string) $value) . "'";
        }

        public function setQuery($query): void
        {
            $this->current = $query;
        }

        public function loadObjectList(): array
        {
            $this->reads[] = $this->current;
            return $this->rows;
        }

        public function execute(): bool
        {
            $this->updates[] = $this->current;
            return true;
        }
    }

    $cases = 0;
    $fails = [];

    function check(bool $condition, string $label): void
    {
        global $cases, $fails;
        ++$cases;
        if (!$condition) {
            $fails[] = $label;
        }
    }

    function item(
        int $id = 31,
        int $published = 1,
        int $primary = 1,
        string $link = 'index.php?option=com_minimarket&view=catalog',
        string $type = 'component',
        int $client = 0,
        int $componentId = 78
    ): object {
        return (object) [
            'id' => $id, 'published' => $published, 'component_id' => $componentId,
            'type' => $type, 'client_id' => $client, 'link' => $link,
            'params' => json_encode(['shop_primary_route' => $primary, 'show_products' => 1]),
        ];
    }

    check(PrimaryMenuSync::isShopLink('index.php?option=com_minimarket&view=catalog'),
        'exact MiniMarket catalog link accepted');
    check(PrimaryMenuSync::isShopLink('index.php?view=catalog&option=com_minimarket'),
        'menu link parameter order irrelevant');
    check(!PrimaryMenuSync::isShopLink('index.php?option=com_minimarket&view=product&id=1'),
        'product view is not storefront');
    check(!PrimaryMenuSync::isShopLink('index.php?option=com_content&view=category'),
        'foreign component ignored');
    check(!PrimaryMenuSync::isShopLink('/catalog'),
        'SEF URL cannot be interpreted as saved Joomla menu component link');

    $db = new Db();
    $plugin = new Minimarketmenu([]);
    $plugin->setDatabase($db);
    check(Minimarketmenu::getSubscribedEvents()['onContentAfterSave'] === 'onContentAfterSave',
        'plugin subscribes to Joomla after-save event');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_content.article', item()));
    check(count($db->updates) === 0, 'foreign save event ignored');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item(published: 0)));
    check(count($db->updates) === 0, 'unpublished draft does not replace published primary');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item(primary: 0)));
    check(count($db->updates) === 0, 'unchecking primary leaves other entries alone');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item(type: 'alias')));
    check(count($db->updates) === 0, 'menu alias cannot change component primary');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item(client: 1)));
    check(count($db->updates) === 0, 'administrator menu cannot change site primary');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item(componentId: 0)));
    check(count($db->updates) === 0, 'missing component identifier ignored');

    $plugin->onContentAfterSave(new AfterSaveEvent('com_menus.item', item()));
    check(count($db->reads) === 1, 'published primary triggers one read');
    check(count($db->updates) === 2, 'two older primary flags cleared');
    check(str_contains(implode(' ', $db->reads[0]->conditions), 'component_id = 78'),
        'SQL read scoped to the MiniMarket component ID');
    check(str_contains(implode(' ', $db->reads[0]->conditions), 'client_id = 0'),
        'SQL read scoped to site menu');
    check(str_contains(implode(' ', $db->reads[0]->conditions), "type = 'component'"),
        'SQL read scoped to ordinary menu items');
    check(str_contains(implode(' ', $db->reads[0]->conditions), 'id <> 31'),
        'SQL read excludes saved current menu item');
    check(str_contains(implode(' ', $db->updates[0]->conditions), 'id = 45'),
        'first update targets first competing menu item only');
    check(str_contains(implode(' ', $db->updates[1]->conditions), 'id = 53'),
        'second update targets second competing menu item only');
    check(str_contains($db->updates[0]->sets[0], '"show_categories":0'),
        'other parameters (categories) preserved');
    check(str_contains($db->updates[0]->sets[0], '"show_products":1'),
        'other parameters (products) preserved');
    check(str_contains($db->updates[0]->sets[0], '"shop_primary_route":0'),
        'old primary flag cleared to 0');
    check(str_contains($db->updates[1]->sets[0], '"products_order":"name"'),
        'sorting parameter preserved');
    check(count($db->updates) === 2, 'no writes to unrelated product or foreign menus');

    if ($fails !== []) {
        fwrite(STDERR, 'MENU_SYNC_TEST_FAIL: ' . implode('; ', $fails) . PHP_EOL);
        exit(1);
    }

    echo 'MENU_SYNC_TEST_OK: ' . $cases . ' cases' . PHP_EOL;
}
