<?php
/** MiniMarket 0.6.20: automatic discounts are selected server side, never stacked. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\AutoDiscountTest {
 \define('_JEXEC',1);
 require __DIR__.'/../src/com_minimarket/site/src/Service/AutoDiscount.php';
 use MiniMarket\Component\Minimarket\Site\Service\AutoDiscount as Auto;
 use Joomla\Database\DatabaseInterface;
 $assert=static function(bool $v,string $name):void {
  if(!$v)throw new \RuntimeException('MM620_AUTO_FAIL: '.$name);
 };
 $reject=static function(callable $cb,string $name)use($assert):void {
  try{$cb();$assert(false,$name);}catch(\DomainException){$assert(true,$name);}
 };
 $base=['title'=>'Пятница','discount_type'=>'percent','percent'=>'12.50',
  'applies_to'=>'all','target_ids'=>'','starts_at'=>'','ends_at'=>'','state'=>1];
 $d=Auto::normalize($base);
 $assert($d['basis_points']===1250&&$d['amount_cents']===0&&$d['target_ids_json']==='[]',
  'exact basis points and global target');
 $fixed=Auto::normalize([...$base,'discount_type'=>'fixed','amount'=>'253.49',
  'applies_to'=>'product','target_ids'=>'11, 22']);
 $assert($fixed['amount_cents']===25349&&$fixed['target_ids_json']==='[11,22]','integer cents & scope IDs');
 $reject(fn()=>Auto::normalize([...$base,'percent'=>'100.01']),'percentage >100 rejected');
 $reject(fn()=>Auto::normalize([...$base,'percent'=>'5.999']),'percentage precision rejected');
 $reject(fn()=>Auto::normalize([...$base,'applies_to'=>'category','target_ids'=>'1,1']),
  'duplicate targets rejected');
 $reject(fn()=>Auto::normalize([...$base,'discount_type'=>'fixed','amount'=>'-0.01']),
  'negative price rejected');
 $reject(fn()=>Auto::normalize([...$base,'starts_at'=>'2026-12-01T12:00',
  'ends_at'=>'2026-11-01T12:00']),'invalid period rejected');
 final class Query {
  public string $table='';
  public function select($v):self{return $this;}
  public function from($table):self{$this->table=(string)$table;return $this;}
  public function where($where):self{return $this;}
  public function order($v):self{return $this;}
 }
 final class Db implements DatabaseInterface {
  public Query $query;
  public array $rules=[];
  public function getQuery($new=true):Query{return new Query();}
  public function quoteName($name):string{return (string)$name;}
  public function quote($value):string{return "'".str_replace("'","''",(string)$value)."'";}
  public function setQuery($q,$offset=0,$limit=0):void{$this->query=$q;}
  public function loadAssocList():array{
   if(str_contains($this->query->table,'auto_discounts'))return $this->rules;
   if(str_contains($this->query->table,'products'))return [
    ['id'=>11,'category_id'=>2,'brand_id'=>3],
    ['id'=>22,'category_id'=>5,'brand_id'=>4]];
   throw new \RuntimeException('Unexpected table '.$this->query->table);
  }
 }
 $db=new Db();
 $lines=[['product_id'=>11,'line_cents'=>20000],['product_id'=>22,'line_cents'=>10000]];
 $entry=static fn(int $id,string $kind,string $scope,array $ids,int $amount,int $bps):array=>[
  'id'=>$id,'title'=>'Rule #'.$id,'discount_type'=>$kind,'applies_to'=>$scope,
  'target_ids_json'=>json_encode($ids),'amount_cents'=>$amount,'basis_points'=>$bps];
 $db->rules=[
  $entry(10,'percent','all',[],0,1000),        // 3,000
  $entry(11,'fixed','product',[11],5000,0),  // 5,000
  $entry(12,'percent','category',[5],0,5000),// 5,000, loses tie to #11
  $entry(13,'percent','brand',[3],0,6000)    // 12,000: best
 ];
 $best=Auto::quote($db,$lines,30000);
 $assert($best['id']===13&&$best['discount_cents']===12000,'strongest applicable offer selected');
 array_pop($db->rules);
 $best=Auto::quote($db,$lines,30000);
 $assert($best['id']===11&&$best['discount_cents']===5000,'stable deterministic tie on rule id');
 $db->rules=[$entry(14,'fixed','all',[],5000000,0)];
 $assert(Auto::quote($db,$lines,30000)['discount_cents']===30000,'cannot discount beyond subtotal');
 $db->rules=[];
 $assert(Auto::quote($db,$lines,30000)===null,'without active offers no automatic discount');
 $root=dirname(__DIR__).'/src/com_minimarket/';
 $order=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
 $assert(str_contains($order,'AutoDiscount::quote')
  &&str_contains($order,'$promo=null;')
  &&str_contains($order,"'auto_discount_id','auto_discount_title'"),
  'order source snapshot and nonstacking coupon comparison');
 $checkout=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
 $assert(str_contains($checkout,'$bestDiscount=max('),'checkout preview compares both offers');
 foreach(['minimarket.xml','admin/sql/updates/mysql/0.6.20.sql'] as $path)
  $assert(is_file($root.$path),'manifest or migration available');
 foreach(['autodiscounts','autodiscount'] as $view)
  $assert(is_file($root.'admin/src/View/'.ucfirst($view).'/HtmlView.php'),
   'real editor/list view exists: '.$view);
 $menu=(string)file_get_contents($root.'admin/presets/minimarket.xml');
 $manifest=(string)file_get_contents($root.'minimarket.xml');
 $dashboard=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
 foreach([$menu,$manifest,$dashboard] as $source)
  $assert(str_contains($source,'autodiscounts'),'all navigation entries active');
 $assert(!str_contains((string)file_get_contents($root.'admin/sql/updates/mysql/0.6.20.sql'),'stock_movements'),
   'no inventory movement system');
 echo "MM620_AUTOMATIC_DISCOUNTS_PASS\n";
}
