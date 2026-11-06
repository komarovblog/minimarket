<?php
/** Disposable MySQL CI persistence for feed definitions and selection deltas. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\FeedDbTest {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/FeedSelection.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/FeedConfig.php';
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection;
    use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;
    $name=(string)(getenv('MM_CI_DB_DATABASE')?:'');
    if(getenv('MINIMARKET_DEMO_CI')!=='1'
       || !preg_match('/^minimarket_ci_[a-z0-9_]+$/D',$name)) {
        fwrite(STDERR,"FEED_DB_REFUSED\n");exit(2);
    }
    $sql=new \mysqli(
        getenv('MM_CI_DB_HOST')?:'127.0.0.1',
        getenv('MM_CI_DB_USER')?:'',
        getenv('MM_CI_DB_PASSWORD')?:'',
        $name,(int)(getenv('MM_CI_DB_PORT')?:3306));
    $sql->set_charset('utf8mb4');
    final class Db implements DatabaseInterface {
        private string $query='';
        public function __construct(private \mysqli $sql){}
        public function quoteName($name):string {
            return '`'.str_replace('`','``',str_replace('#__','nnop3_', (string)$name)).'`';
        }
        public function quote($value):string{return "'".$this->sql->real_escape_string((string)$value)."'";}
        public function setQuery($query):void{$this->query=str_replace('#__','nnop3_', (string)$query);}
        public function loadAssoc():?array {
            $r=$this->sql->query($this->query);return $r->fetch_assoc()?:null;
        }
        public function loadAssocList():array{
            return $this->sql->query($this->query)->fetch_all(MYSQLI_ASSOC);
        }
        public function execute():void{$this->sql->query($this->query);}
        public function insertid():int{return $this->sql->insert_id;}
    }
    $checks=0;
    function verify(bool $ok,string $why):void {global $checks;++$checks;
        if(!$ok)throw new \RuntimeException('FEEDS_DB_FAIL: '.$why);
    }
    $db=new Db($sql);
    $tables=$sql->query("SHOW TABLES LIKE 'nnop3_minimarket_feeds'")->num_rows;
    verify($tables===1,'feed table installed from full manifest');
    [$categories,$products]=FeedConfig::catalog($db);
    $visible=FeedSelection::visibleCategories($categories);
    verify(count($visible)>=8,'demo category tree present');
    $defaults=FeedSelection::productIds($categories,$products,[
        'category_ids'=>$visible,'include_descendants'=>1,
        'include_bundles'=>1,'selection_mode'=>'auto'
    ]);
    verify(count($defaults)>=70,'all published demo products available for feeds');
    $chosen=array_slice($defaults,0,3);
    $values=[
        'title'=>'CI feed — promotion','company'=>'CI demo','provider'=>'vk_yml',
        'state'=>1,'sort_order'=>12,'selection_mode'=>'auto','include_descendants'=>1,'include_bundles'=>1,
        'categories'=>$visible,'products'=>$chosen,
    ];
    $id=FeedConfig::save($db,$values,$categories,$products);
    verify($id>0,'new feed ID allocated');
    try {
        $feed=FeedConfig::find($db,$id);
        verify($feed!==null && (int)$feed['sort_order']===12 &&
            strlen($feed['access_key'])===40
            && preg_match('/^[a-f0-9]{40}$/D',$feed['access_key'])===1,
            'feed has random private URL key');
        $settings=FeedConfig::settings($feed);
        verify(FeedSelection::productIds($categories,$products,$settings)===$chosen,
            'automatic base plus excluded deltas roundtrip via SQL');
        $values['id']=$id;$values['company']='CI updated';
        verify(FeedConfig::save($db,$values,$categories,$products)===$id,
            'update retains ID');
        $new=FeedConfig::find($db,$id);
        verify($new['access_key']===$feed['access_key'] && $new['company']==='CI updated',
            'public URL key remains stable on editor save');
        $values['selection_mode']='manual';
        $values['products']=[(string)$chosen[0]];
        FeedConfig::save($db,$values,$categories,$products);
        $new=FeedConfig::find($db,$id);
        $manual=FeedConfig::settings($new);
        verify($manual['include_product_ids']===[(int)$chosen[0]]
            && $manual['exclude_product_ids']===[],
            'manual selection persists exactly selected SKU parent product');
    } finally {
        FeedConfig::delete($db,$id);
    }
    verify(FeedConfig::find($db,$id)===null,'feed deleted without touching catalog');
    $after=$sql->query('SELECT COUNT(*) FROM nnop3_minimarket_products')->fetch_row()[0];
    verify((int)$after>=70,'catalog preserved after feed CRUD');
    echo "MM61_FEED_MYSQL84_PASS: $checks checks\n";
}
