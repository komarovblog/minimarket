<?php
/** MiniMarket 0.6.21: managed recommendations, validation and menu consistency. */
declare(strict_types=1);
namespace Joomla\CMS\MVC\Controller { class BaseController{} }
namespace Joomla\Database { interface DatabaseInterface{} }
namespace MiniMarket\RecommendationsTest {
 \define('_JEXEC',1);
 require dirname(__DIR__).'/src/com_minimarket/admin/src/Controller/RecommendationController.php';
 require dirname(__DIR__).'/src/com_minimarket/site/src/Service/ProductRecommendations.php';
 use MiniMarket\Component\Minimarket\Administrator\Controller\RecommendationController as Editor;
 use MiniMarket\Component\Minimarket\Site\Service\ProductRecommendations as Recs;
 $assert=static function(bool $ok,string $name):void {
  if(!$ok)throw new \RuntimeException('MM621_RECOMMENDATIONS_FAIL: '.$name);
 };
 $reject=static function(callable $fn,string $name)use($assert):void {
  try{$fn();$assert(false,$name);}catch(\DomainException){$assert(true,$name);}
 };
 $data=Editor::normalize('hybrid','manual','12,13','14',11);
 $assert($data['modes']['similar']==='hybrid'&&$data['together']=== [14] &&
  $data['similar'] === [12,13], 'ordered manual targets and independent modes');
 $reject(fn()=>Editor::normalize('manual','auto','11','',11),'self target forbidden');
 $reject(fn()=>Editor::normalize('auto','auto','12,12','',11),'duplicate target forbidden');
 $reject(fn()=>Editor::normalize('auto','auto','0','',11),'zero target forbidden');
 $reject(fn()=>Editor::normalize('wrong','auto','','',11),'unknown mode forbidden');
 $reject(fn()=>Editor::normalize('auto','auto','12,','',11),'malformed targets forbidden');
 $reject(fn()=>Editor::normalize('auto','auto',implode(',',range(1,31)),'',100),
   'excessive links forbidden');
 final class Query{
  public string $table='';
  public function select($columns):self{return $this;}
  public function from($table):self{$this->table=(string)$table;return $this;}
  public function where($where):self{return $this;}
  public function order($sort):self{return $this;}
 }
 final class Db implements \Joomla\Database\DatabaseInterface{
  public Query $query;
  public ?array $config=null;
  public array $ids=[12,13];
  public function quoteName($name):string{return (string)$name;}
  public function quote($s):string{return "'".$s."'";}
  public function getQuery($new=true):Query{return new Query();}
  public function setQuery($q,$offset=0,$limit=0):void{$this->query=$q;}
  public function loadAssoc():?array{return $this->config;}
  public function loadColumn():array{return $this->ids;}
 }
 $db=new Db();
 $assert(Recs::modes($db,11)===['similar'=>'inherit','together'=>'inherit'],
  'legacy products inherit category rules, whose default is their own category');
 $db->config=['similar_mode'=>'hybrid','together_mode'=>'manual'];
 $assert(Recs::modes($db,11)===['similar'=>'hybrid','together'=>'manual'],'stored policy');
 $assert(Recs::manualIds($db,11,'similar')===[12,13],'manual IDs preserve priority');
 $db->ids=[11,12,13];
 $assert(Recs::manualIds($db,11,'together')===[12,13],'self excluded at runtime');
 $service=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/site/src/Service/ProductRecommendations.php');
 $assert(str_contains($service,"o.payment_status='paid'")
  &&str_contains($service,"o.fulfillment_status='delivered'")
  &&str_contains($service,"r.status IN ('received','closed')")
  &&str_contains($service,'getVisibleRecommendationsByIds'),
  'delivered paid and non-returned products pass visibility filtering');
 $root=dirname(__DIR__).'/src/com_minimarket/';
 foreach(['admin/src/View/Recommendations/HtmlView.php',
  'admin/src/View/Recommendation/HtmlView.php',
  'admin/tmpl/recommendations/default.php',
  'admin/tmpl/recommendation/edit.php','admin/sql/updates/mysql/0.6.21.sql'] as $path)
  $assert(is_file($root.$path),'real component feature '.$path);
 foreach(['admin/presets/minimarket.xml','minimarket.xml',
  'admin/tmpl/dashboard/default.php'] as $path)
  $assert(str_contains((string)file_get_contents($root.$path),'recommendations'),
    'three navigation entries '.$path);
 foreach(['ru-RU','en-GB'] as $locale){
  foreach(['admin/language/'.$locale.'/com_minimarket.ini',
   'admin/language/'.$locale.'/com_minimarket.sys.ini'] as $path)
   $assert(str_contains((string)file_get_contents($root.$path),
    'COM_MINIMARKET_RECOMMENDATIONS_TITLE='),'locale '.$path);
 }
 echo "MM621_MANAGED_RECOMMENDATIONS_PASS\n";
}
