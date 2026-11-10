<?php
/**
 * Execute the exact CatalogRepository SKU availability SQL against an isolated
 * MySQL 8.4 fixture. Never runs against the user's local Joomla database.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {}
}

namespace {
    define('_JEXEC', 1);
    require dirname(__DIR__).'/src/com_minimarket/site/src/Service/CatalogRepository.php';
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

    if (getenv('MINIMARKET_CANONICAL_CI') !== '1'
        || getenv('MM_CI_DB_DATABASE') !== 'minimarket_ci_canonical73') {
        throw new \RuntimeException('MM75_STOCK_SQL_TEST_REFUSED_OUTSIDE_CI');
    }
    $fake = new class implements DatabaseInterface {
        public function quoteName($name,$as=null) {
            return '`'.str_replace('`','``',(string)$name).'`'
                .($as===null?'':' AS `'.$as.'`');
        }
    };
    $repo = new CatalogRepository($fake);
    $m = (new \ReflectionClass($repo))->getMethod('availabilitySql');
    $predicate = str_replace('#__','nnop3_', (string)$m->invoke($repo));
    \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $db=new \mysqli(
        getenv('MM_CI_DB_HOST')?:'127.0.0.1',
        getenv('MM_CI_DB_USER')?:'',
        getenv('MM_CI_DB_PASSWORD')?:'',
        'minimarket_ci_canonical73',
        (int)(getenv('MM_CI_DB_PORT')?:3306)
    );
    $count = static function (int $id) use ($db,$predicate):int {
        $sql='SELECT COUNT(*) FROM nnop3_minimarket_products p WHERE p.id='
            .$id.' AND '.$predicate;
        return (int)$db->query($sql)->fetch_row()[0];
    };
    $db->begin_transaction();
    try {
        // 1201 has zero product stock, but some active SKU variants in stock.
        $db->query('UPDATE nnop3_minimarket_products SET stock=0 WHERE id=1201');
        if ($count(1201)!==1) throw new \RuntimeException('MM75_STOCK_ACTIVE_SKU_HIDDEN');

        // All active SKU quantities are now zero: the listing must hide it.
        $db->query('UPDATE nnop3_minimarket_product_variants SET stock=0 WHERE product_id=1201');
        if ($count(1201)!==0) throw new \RuntimeException('MM75_STOCK_SOLD_OUT_SKU_LISTED');

        // Independent color/product variant still sells despite base stock=0.
        $db->query('UPDATE nnop3_minimarket_products SET stock=0 WHERE id=1202');
        if ($count(1202)!==1) throw new \RuntimeException('MM75_STOCK_SECOND_SKU_HIDDEN');

        // Bundle SQL preselects bundles; PHP derives nested component stock.
        if ($count(1090)!==1) throw new \RuntimeException('MM75_STOCK_BUNDLE_NOT_CANDIDATE');
    } finally {
        $db->rollback();
    }
    echo "MM75_STOCK_LISTING_SQL_MYSQL84_PASS: real SKU and bundle predicate\n";
}
