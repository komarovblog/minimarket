<?php
/**
 * Disposable MySQL 8.4 integration test for JSON v2: full real 32-table
 * snapshot, preview rollback, idempotent apply, collision and integrity.
 * Uses ONLY the dedicated GitHub Actions minimarket_ci_* database.
 */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\ArchiveV2Test {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogArchiveV2;
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/CatalogArchiveV2.php';

    $name=(string)(getenv('MM_CI_DB_DATABASE')?:'');
    if (getenv('MINIMARKET_DEMO_CI')!=='1'
        || !preg_match('/^minimarket_ci_[a-z0-9_]+$/D',$name)) {
        fwrite(STDERR,"ARCHIVE_V2_TEST_REFUSED\n");
        exit(2);
    }
    $dbraw=new \mysqli(
        getenv('MM_CI_DB_HOST')?:'127.0.0.1',
        getenv('MM_CI_DB_USER')?:'',
        getenv('MM_CI_DB_PASSWORD')?:'',
        $name,
        (int)(getenv('MM_CI_DB_PORT')?:3306)
    );
    $dbraw->set_charset('utf8mb4');

    final class Adapter implements DatabaseInterface
    {
        private string $query='';
        public function __construct(private \mysqli $db) {}
        public function quoteName($value): string
        {
            return '`'.str_replace('`','``',str_replace('#__','nnop3_',(string)$value)).'`';
        }
        public function quote($value): string
        {
            return "'".$this->db->real_escape_string((string)$value)."'";
        }
        public function setQuery($sql,int $offset=0,int $limit=0): void
        {
            $this->query=str_replace('#__','nnop3_',(string)$sql);
            if ($limit>0) $this->query.=' LIMIT '.max(0,$offset).','.max(1,$limit);
        }
        public function loadAssocList(): array
        {
            return $this->db->query($this->query)->fetch_all(MYSQLI_ASSOC);
        }
        public function loadAssoc(): ?array
        {
            return $this->db->query($this->query)->fetch_assoc();
        }
        public function execute(): bool
        {
            return (bool)$this->db->query($this->query);
        }
        public function transactionStart(): void { $this->db->begin_transaction(); }
        public function transactionCommit(): void { $this->db->commit(); }
        public function transactionRollback(): void { $this->db->rollback(); }
    }
    function check(bool $yes,string $why): void {
        global $checks;
        ++$checks;
        if (!$yes) throw new \RuntimeException('ARCHIVE_V2_FAIL: '.$why);
    }
    function rehash(array $document): array
    {
        unset($document['digest']);
        $canonical=json_encode($document,
            JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $document['digest']=hash('sha256',$canonical);
        return $document;
    }
    $checks=0;
    $db=new Adapter($dbraw);
    $doc=CatalogArchiveV2::document($db);
    check($doc['format']==='minimarket-full' && $doc['version']===2,'full versioned format');
    check(count($doc['tables'])===32,'all 32 component tables');
    check(count($doc['tables']['products'])>70,'real sample products exported');
    check(count($doc['tables']['product_variants'])>=16,'SKU records retained');
    check(count($doc['tables']['product_images'])>90,'multi-image product gallery retained');
    check(count($doc['tables']['product_videos'])>=1,'promo video metadata retained');
    check(isset($doc['schema']['products']['meta_title'])
        && isset($doc['schema']['products']['badge_benefit_display']),
        'SEO and badge options included in archive');
    $raw=json_encode($doc,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $doc=CatalogArchiveV2::decode($raw);
    $report=CatalogArchiveV2::run($db,$doc,false);
    check(($report['products']['created']??-1)===0
        && $report['products']['unchanged']===count($doc['tables']['products']),
        'preview reports all existing products unchanged');
    $report=CatalogArchiveV2::run($db,$doc,true);
    check(($report['product_variants']['created']??-1)===0,
        'applying exact JSON again is an idempotent no-op');

    $broken=$doc;
    $broken['tables']['products'][0]['title']='DIFFERENT TITLE';
    try { CatalogArchiveV2::decode(json_encode($broken,JSON_THROW_ON_ERROR)); 
        check(false,'tampered archive accepted without valid digest'); }
    catch(\InvalidArgumentException $e) {
        check($e->getMessage()==='COM_MINIMARKET_EXCHANGE_SCHEMA','tampered digest rejected');
    }
    $broken=rehash($broken);
    try { CatalogArchiveV2::run($db,$broken,true);check(false,'conflicting row overwritten'); }
    catch(\RuntimeException $e) {
        check($e->getMessage()==='COM_MINIMARKET_EXCHANGE_CONFLICT',
            'different existing product rejected');
    }

    $new=$doc;
    $cat=$new['tables']['categories'][0];
    $cat['id']='888881';$cat['title']='Archive v2 transient test';
    $cat['alias']='archive-v2-ci-transient-test';$cat['parent_id']='0';$cat['state']='0';
    $new['tables']['categories'][]=$cat;
    $new=rehash($new);
    $before=(int)$dbraw->query("SELECT COUNT(*) FROM nnop3_minimarket_categories WHERE id=888881")->fetch_row()[0];
    check($before===0,'transient category not present before test');
    $preview=CatalogArchiveV2::run($db,$new,false);
    check($preview['categories']['created']===1,'preview recognizes new category');
    check((int)$dbraw->query("SELECT COUNT(*) FROM nnop3_minimarket_categories WHERE id=888881")->fetch_row()[0]===0,
        'preview actually rolls back inserts');
    $withConflict=$new;
    $withConflict['tables']['products'][0]['title']='SCHEMA CONFLICT';
    $withConflict=rehash($withConflict);
    try {CatalogArchiveV2::run($db,$withConflict,true);
        check(false,'conflicting product should abort full transaction');
    } catch(\RuntimeException $e) {
        check($e->getMessage()==='COM_MINIMARKET_EXCHANGE_CONFLICT',
            'transaction fails closed');
        check((int)$dbraw->query("SELECT COUNT(*) FROM nnop3_minimarket_categories WHERE id=888881")->fetch_row()[0]===0,
            'earlier category insert rolled back when later product conflicts');
    }
    $applied=CatalogArchiveV2::run($db,$new,true);
    check($applied['categories']['created']===1,'confirmed full import writes new category');
    $repeat=CatalogArchiveV2::run($db,$new,true);
    check($repeat['categories']['created']===0,'repeating confirmed import makes no duplicates');
    $dbraw->query("DELETE FROM nnop3_minimarket_categories WHERE id=888881");
    check((int)$dbraw->query("SELECT COUNT(*) FROM nnop3_minimarket_categories WHERE id=888881")->fetch_row()[0]===0,
        'CI transient record cleaned up');
    echo "CATALOG_ARCHIVE_V2_MYSQL84_PASS: $checks checks\n";
}
