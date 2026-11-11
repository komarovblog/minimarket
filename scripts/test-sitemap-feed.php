<?php
/** XML sitemap regression without Joomla runtime; browser SEF test is separate. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/admin/src/Service/SitemapFeed.php';
use MiniMarket\Component\Minimarket\Administrator\Service\SitemapFeed;
$n=0;
$check=static function(bool $ok,string $why)use(&$n):void{
  ++$n;if(!$ok)throw new RuntimeException('SITEMAP_FEED_FAIL: '.$why);
};
$base='https://example.com/subsite/';
$check(SitemapFeed::absolute('/subsite/beds/product-7',$base)==='https://example.com/subsite/beds/product-7','subfolder route');
$check(SitemapFeed::absolute('index.php?option=com_content&view=article&id=9',$base)===
 'https://example.com/subsite/index.php?option=com_content&view=article&id=9','Joomla com_content route');
$check(SitemapFeed::absolute('https://evil.example/steal',$base)===null,'foreign domain rejected');
$check(SitemapFeed::absolute('/other/article',$base)===null,'subsite path escaped');
$check(SitemapFeed::absolute('/subsite/a#other',$base)===null,'fragment rejected');
$check(SitemapFeed::lastModified('2026-11-11 13:01:01')==='2026-11-11','modified date');
$check(SitemapFeed::lastModified('0000-00-00 00:00:00','2025-05-09 10:12:00')==='2025-05-09','created fallback');
$xml=SitemapFeed::render([
 ['loc'=>'https://example.com/subsite/p?one=1&two=2'],
 ['loc'=>'https://example.com/subsite/p?one=1&two=2'],
 ['loc'=>'https://example.com/subsite/article','lastmod'=>'2026-11-11'],
]);
$check(substr_count($xml,'<url>')===2 && str_contains($xml,'<lastmod>2026-11-11</lastmod>'),'dedupe and date');
$check(str_contains($xml,'one=1&amp;two=2') && simplexml_load_string($xml)!==false,'valid escaped XML');
$root=dirname(__DIR__).'/src/com_minimarket/';
$config=(string)file_get_contents($root.'admin/src/Service/FeedConfig.php');
$controller=(string)file_get_contents($root.'site/src/Controller/FeedController.php');
$check(str_contains($config,"'sitemap' => 'COM_MINIMARKET_FEED_SITEMAP'"),'feed provider installed');
$check(str_contains($controller,"SitemapFeed::publicArticles(\$db)")
 && str_contains($controller,"FeedSelection::productIds")
 && str_contains($controller,"SitemapFeed::absolute")
 && str_contains($controller,"SitemapFeed::render"),'published products and Joomla articles');
$service=(string)file_get_contents($root.'admin/src/Service/SitemapFeed.php');
foreach(['a.state=1','a.access=1','c.published=1','c.access=1',
 'ancestor.lft<=c.lft','ancestor.rgt>=c.rgt','ancestor.published<>1',
 'ancestor.access<>1','a.publish_up','a.publish_down','com_content'] as $filter) {
 $check(str_contains($service,$filter),'anonymous content constraint '.$filter);
}
$check(SitemapFeed::MAX_URLS===5000,'bounded sitemap');
echo 'SITEMAP_FEED_PASS: '.$n." checks\n";
