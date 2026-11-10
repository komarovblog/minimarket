<?php
/** Checkout request: validation, immutable server quotes, transactional write. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\CheckoutSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\CheckoutData;
    use MiniMarket\Component\Minimarket\Site\Service\CartState;
    use MiniMarket\Component\Minimarket\Site\Service\OrderStorage;
    \define('_JEXEC', 1);
    require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutData.php';
    require __DIR__.'/../src/com_minimarket/site/src/Service/CartState.php';
    require __DIR__.'/../src/com_minimarket/site/src/Service/OrderStorage.php';
    $n=0;
    function chk(bool $valid,string $msg):void {
        global $n;$n++;
        if(!$valid) throw new \RuntimeException('MM78_CHECKOUT_FAIL: '.$msg);
    }
    function rejects(callable $fn,string $msg):void {
        try{$fn();chk(false,$msg);}catch(\DomainException){chk(true,$msg);}
    }
    $good=CheckoutData::normalize([
        'name'=>'  Иван  Иванов  ','phone'=>'+7 (999) 123-45-67',
        'email'=>'buyer@example.com','comment'=>'<p>Перезвоните утром</p>']);
    chk($good['name']==='Иван Иванов' && $good['comment']==='Перезвоните утром',
        'trim and strip markup from customer data');
    rejects(fn()=>CheckoutData::normalize(['name'=>'И','phone'=>'+79161234567']), 'short name rejected');
    rejects(fn()=>CheckoutData::normalize(['name'=>'Иван','phone'=>'123']), 'short phone rejected');
    rejects(fn()=>CheckoutData::normalize(['name'=>'Иван','phone'=>'79161234567','email'=>'bad@']), 'bad email rejected');
    rejects(fn()=>CheckoutData::normalize(['name'=>'Иван','phone'=>'79161234567','comment'=>str_repeat('x',2001)]), 'long comment rejected');

    final class Query {
        public string $table='';
        public array $columns=[];
        public array $entries=[];
        public function insert($table):self{$this->table=(string)$table;return $this;}
        public function columns($columns):self{$this->columns=$columns;return $this;}
        public function values($row):self{$this->entries[]=(string)$row;return $this;}
    }
    final class FakeDb implements DatabaseInterface {
        public ?Query $current=null;
        public array $saved=[];
        public array $steps=[];
        public bool $failItems=false;
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($value):mixed{return $value;}
        public function quote($value):string{return "'".str_replace("'","''",(string)$value)."'";}
        public function setQuery($q):void{$this->current=$q;}
        public function execute():void {
            if($this->failItems && str_contains($this->current->table,'order_items')) {
                throw new \RuntimeException('simulated line write failure');
            }
            $this->saved[]=['table'=>$this->current->table,'entries'=>$this->current->entries];
        }
        public function insertid():int{return 123;}
        public function transactionStart():void{$this->steps[]='begin';}
        public function transactionCommit():void{$this->steps[]='commit';}
        public function transactionRollback():void{$this->steps[]='rollback';}
    }
    $quote=['available'=>true,'units'=>3,'total_cents'=>500100,'lines'=>[
        ['product_id'=>5,'variant_id'=>9,'quantity'=>2,'unit_cents'=>150050,
         'valid'=>true,'type'=>'simple','title'=>'Товар размер M','sku'=>'SKU-M'],
        ['product_id'=>8,'variant_id'=>0,'quantity'=>1,'unit_cents'=>200000,
         'valid'=>true,'type'=>'bundle','title'=>'Комплект','sku'=>'SET-8']
    ]];
    $db=new FakeDb();
    $guestDb=new FakeDb();
    $guestId=OrderStorage::create($guestDb,$good,$quote,'00112233445566778899aabbccddeeff',0);
    chk($guestId===123 && str_contains($guestDb->saved[0]['entries'][0],",0,"),
        'contact-only order can be stored with user_id zero');
    $id=OrderStorage::create($db,$good,$quote,'00112233445566778899aabbccddeeff',77);
    chk($id===123,'returns database-generated order id');
    chk($db->steps===['begin','commit'],'saves order and all lines in one transaction');
    chk(count($db->saved)===2 && count($db->saved[1]['entries'])===2,
        'one order and two server-priced snapshot lines');
    chk(str_contains($db->saved[0]['entries'][0],'500100')
        && str_contains($db->saved[1]['entries'][0],"'SKU-M'"),
        'total and SKU come from server quote');
    $db=new FakeDb();$db->failItems=true;
    try {
        OrderStorage::create($db,$good,$quote,'00112233445566778899aabbccddeeff',77);
        chk(false,'rollback on failed line insert');
    } catch (\RuntimeException) {
        chk($db->steps===['begin','rollback'],'rollback if order items fail');
    }
    $broken=$quote;$broken['total_cents']=123;
    rejects(fn()=>OrderStorage::create(new FakeDb(),$good,$broken,'00112233445566778899aabbccddeeff',77),
        'never trust a mismatching total');
    $broken=$quote;$broken['lines'][0]['valid']=false;
    rejects(fn()=>OrderStorage::create(new FakeDb(),$good,$broken,'00112233445566778899aabbccddeeff',77),
        'unavailable item rejected');
    rejects(fn()=>OrderStorage::create(new FakeDb(),$good,$quote,'invalid',77),
        'checkout nonce must be 128-bit hex');

    $root=dirname(__DIR__).'/src/com_minimarket/';
    $manifest=simplexml_load_file($root.'minimarket.xml');
    chk($manifest!==false && (string)$manifest->version==='0.5.80','0.5.80 contact-only interim checkout');
    chk(count($manifest->xpath("//submenu/menu[@link='option=com_minimarket&view=orders']"))===1,
        'native Joomla admin Orders menu');
    $route=file_get_contents($root.'site/src/Service/Router.php');
    chk(str_contains($route,"return ['checkout'];")
        && str_contains($route,"return ['order-complete'];"),
        'checkout and confirmation have stable public URL segments');
    $controller=file_get_contents($root.'site/src/Controller/CheckoutController.php');
    chk(str_contains($controller,"Session::checkToken('post')")
        && str_contains($controller,'hash_equals($expected,$provided)')
        && str_contains($controller,'CartCatalog($db)')
        && str_contains($controller,'OrderStorage::create')
        && str_contains($controller,'CartState::SESSION_KEY,[]'),
        'checkout verifies CSRF, unique nonce, cart and clears only on success');
    $checkout=file_get_contents($root.'site/tmpl/checkout/default.php');
    chk(str_contains($checkout,"HTMLHelper::_('form.token')")
        && str_contains($checkout,'checkout_key')
        && str_contains($checkout,'name="phone"'),
        'Joomla checkout form and required phone exist');
    $admin=file_get_contents($root.'admin/tmpl/dashboard/default.php');
    chk(str_contains($admin,"'view' => 'orders'")
        && str_contains($admin,"'count' => 'orders'"),
        'dashboard Orders tile links to real count');
    echo "MM78_CHECKOUT_OK: $n checks\n";
}
