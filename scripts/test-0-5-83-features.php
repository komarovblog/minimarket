<?php
/** 0.5.83: new review and SEO systems, admin fixes and secure defaults. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'site/src/Service/ReviewData.php';
require $root.'admin/src/Service/SeoFilterData.php';
use MiniMarket\Component\Minimarket\Site\Service\ReviewData;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoFilterData;
$n=0;
$check=static function(bool $ok,string $description)use(&$n):void{
  ++$n;if(!$ok)throw new RuntimeException('MM83_FEATURE_FAIL '.$description);
};
$reject=static function(callable $callback,string $description)use($check):void{
  try{$callback();$check(false,$description);}catch(DomainException $e){$check(true,$description);}
};
$review=ReviewData::normalize(['author_name'=>'Иван','rating'=>5,
 'body'=>'Доставили быстро, качество хорошее.']);
$check($review['rating']===5&&$review['photo_path']===''&&$review['video_url']==='',
 'review text without untrusted media');
$reject(fn()=>ReviewData::normalize(['author_name'=>'X','rating'=>8,'body'=>'short']),
 'invalid review rejected');
$review=ReviewData::normalize(['author_name'=>'Иван','rating'=>4,
 'body'=>'Отличная вещь, рекомендую!','photo_path'=>'images/reviews/test.webp'],true);
$check($review['photo_path']==='images/reviews/test.webp','safe manual image reference');
$reject(fn()=>ReviewData::normalize(['author_name'=>'Иван','rating'=>4,
 'body'=>'Отличная вещь, рекомендую!','photo_path'=>'../../secret.jpg'],true),
 'path traversal rejected');
$seo=SeoFilterData::normalize(['category_id'=>3,'alias'=>'red-shoes',
 'title'=>'Red shoes catalog','heading'=>'Red shoes','filter_query'=>'mmf%5Bprice%5D%5Bmin%5D=1000']);
$filters=json_decode($seo['filters_json'],true);
$check($filters['price']['min']==='1000'&&$seo['alias']==='red-shoes',
 'SEO query stores structured facet data');
$reject(fn()=>SeoFilterData::normalize(['category_id'=>3,'alias'=>'bad/path',
 'title'=>'Red shoes catalog','heading'=>'Red shoes',
 'filter_query'=>'mmf%5Bprice%5D%5Bmin%5D=1000']),
 'noncanonical slug refused');
$reject(fn()=>SeoFilterData::normalize(['category_id'=>3,'alias'=>'valid',
 'title'=>'Red shoes catalog','heading'=>'Red shoes',
 'filter_query'=>'task=review.create']),
 'non-facet query refused');
$read=static fn(string $file):string=>(string)file_get_contents($root.$file);
$manual=$read('admin/src/Service/ManualOrderCatalog.php');
$check(strpos($manual,'$rows=(array)$db->loadAssocList();')
 < strpos($manual,'$snapshot=BundleStock::snapshot($db);'),
 'manual order DB cursor consumed before stock queries');
$manifest=simplexml_load_file($root.'minimarket.xml');
$check($manifest!==false && version_compare((string)$manifest->version, '0.5.83', '>='),
 'manifest version');
foreach(['ru-RU','en-GB'] as $lang){
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['ORDERS_TITLE','CONFIG_SETTINGS_MENU','REVIEWS_TITLE','SEOFILTERS_TITLE','DASHBOARD_DELIVERIES'] as $name) {
   $check(isset($sys['COM_MINIMARKET_'.$name]),'system menu translation '.$name.' '.$lang);
 }
}
$router=$read('site/src/Service/Router.php');
$check(str_contains($router,"return ['seo',") &&
 str_contains($router,"'view'=>'seofilter'"), 'SEF filter route parsing');
$site=$read('site/src/View/Seofilter/HtmlView.php');
$check(str_contains($site,'parent::display($tpl)') &&
 str_contains($site,'SeoHelper::setCanonical'), 'SEO landing reuses faceted category');
$storage=$read('site/src/Service/ReviewStorage.php');
$check(str_contains($storage,'#__minimarket_reviews') &&
 str_contains($storage,'review_key'), 'review persistence is isolated');
$admin=$read('admin/src/Controller/ReviewController.php');
$check(str_contains($admin,"authorise('core.edit.state'") &&
 str_contains($admin,"Session::checkToken('post')"),'moderation ACL and CSRF');
$public=$read('site/src/Controller/ReviewController.php');
$check(str_contains($public,'reviews_moderation') &&
 str_contains($public,'com_minimarket.last_review_at') &&
 str_contains($public,"Session::checkToken('post')"),'public anti-spam');
$delivery=$read('admin/src/Service/AdminEntityLinks.php');
$check(str_contains($delivery,'view=deliveries') &&
 !str_contains($delivery,'view=orders&filter_delivery=courier'),'real delivery screen');
$reviewForm=simplexml_load_file($root.'admin/forms/product_promo_video.xml');
$videos=simplexml_load_file($root.'admin/forms/product_video.xml');
$check($reviewForm!==false && $videos!==false &&
 count($videos->xpath('//field[@name="is_promo"]'))===0 &&
 str_contains($read('admin/tmpl/product/edit.php'),'COM_MINIMARKET_PROMO_VIDEO_SECTION'),
 'promo video editor is separate');
$config=simplexml_load_file($root.'admin/config.xml');
$check($config!==false && count($config->xpath('//fieldset[@name="reviews"]'))===1 &&
 count($config->xpath('//field[@name="category_product_gallery_enabled"]'))===1 &&
 count($config->xpath('//field[@name="offcanvas_cart_target"]'))===1,
 'review, carousel and future offcanvas options');
$check(str_contains($read('site/tmpl/category/default.php'),'data-mm-card-gallery') &&
 str_contains($read('site/src/View/Category/HtmlView.php'),'categoryGalleries'),
 'category gallery uses actual product images');
$check(file_exists($root.'admin/sql/updates/mysql/0.5.83.sql'),'additive migration present');
$sql=$read('admin/sql/updates/mysql/0.5.83.sql');
$check(str_contains($sql,'#__minimarket_reviews') &&
 str_contains($sql,'#__minimarket_seo_filters') &&
 !preg_match('/\\b(?:DROP\\s+TABLE|TRUNCATE\\s+TABLE|DELETE\\s+FROM)\\b/i',$sql),
 'new schema isolated, non destructive');
$installer=$read('script.php');
$check(str_contains($installer,"version_compare(\$schemaVersion, '0.5.83', '>')") &&
 str_contains($installer,'archived-updates/mysql') &&
 str_contains($installer,"hash_file('sha256'"),
 'old SQL maintenance history may be retired only after verification/backup');
echo "MM83_FEATURES_PASS: $n assertions\n";
