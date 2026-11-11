<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedYml;
use MiniMarket\Component\Minimarket\Administrator\Service\SitemapFeed;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;

/** Read-only, tokenized XML endpoint for published feeds. No Joomla ACL bypass. */
final class FeedController extends BaseController
{
    public function xml(): void
    {
        $app=Factory::getApplication();
        $id=$app->input->getInt('id');
        $key=$app->input->getString('key','');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $feed=FeedConfig::find($db,$id);
        if (!$feed || (int)$feed['state']!==1 || strlen($key)!==40
            || !hash_equals((string)$feed['access_key'],$key)) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'),404);
        }
        [$categories,$productRows]=FeedConfig::catalog($db);
        $settings=FeedConfig::settings($feed);
        $ids=FeedSelection::productIds($categories,$productRows,$settings);
        if(count($ids)>5000)throw new \RuntimeException('Feed exceeds 5000 items',413);
        $repo=new CatalogRepository($db);
        if ((string)$feed['provider']==='sitemap') {
            $urls=[];
            foreach($ids as $pid) {
                // Product publication and visible category ancestry are already
                // checked by FeedSelection; never index an excluded product.
                $loc=SitemapFeed::absolute(RouteHelper::product($pid),Uri::root());
                if($loc!==null)$urls[]=['loc'=>$loc];
            }
            foreach(SitemapFeed::publicArticles($db) as $row) {
                $articleId=(int)($row['id']??0);
                $categoryId=(int)($row['catid']??0);
                if($articleId<1 || $categoryId<1)continue;
                $route=Route::_('index.php?option=com_content&view=article&id='
                    .$articleId.'&catid='.$categoryId,false);
                $loc=SitemapFeed::absolute($route,Uri::root());
                if($loc===null)continue;
                $urls[]=['loc'=>$loc,'lastmod'=>
                    SitemapFeed::lastModified((string)($row['modified']??''),
                        (string)($row['created']??''))];
            }
            if(count($urls)>SitemapFeed::MAX_URLS)
                throw new \RuntimeException('Sitemap exceeds 5000 URLs',413);
            $xml=SitemapFeed::render($urls);
            $app->setHeader('Content-Type','application/xml; charset=UTF-8',true);
            $app->setHeader('X-Content-Type-Options','nosniff',true);
            $app->setHeader('Cache-Control','no-store,max-age=0',true);
            $app->setHeader('Referrer-Policy','no-referrer',true);
            $app->sendHeaders();
            echo $xml;
            $app->close();
            return;
        }

        $productById=[];
        foreach($productRows as $row)$productById[(int)$row['id']]=$row;
        $base=Uri::root();
        $root=parse_url($base);
        $origin=($root['scheme']??'https').'://'.($root['host']??'localhost')
            .(isset($root['port'])?':'.$root['port']:'');
        $offers=[];
        $usedCategories=[];
        foreach($ids as $productId) {
            $p=$repo->getProduct($productId);
            if(!$p)continue;
            $route=RouteHelper::product($productId);
            if(str_starts_with($route,'https://')||str_starts_with($route,'http://')) {
                $url=$route;
            } elseif(str_starts_with($route,'/')) {
                $url=$origin.$route;
            }else {
                $url=$base.ltrim($route,'/');
            }
            $picture=explode('#',(string)($p->image??''),2)[0];
            $picture=preg_match('~^images/[a-zA-Z0-9_./ -]+\.(?:jpe?g|png|webp|gif|avif)$~iD',$picture)
                && !str_contains($picture,'..') && !str_contains($picture,'//')
                ?$base.$picture:'';
            $summary=trim(strip_tags((string)($p->introtext?:$p->description?:'')));
            $baseOffer=[
                'category_id'=>(int)$p->category_id,
                'url'=>$url,'image'=>$picture,'title'=>(string)$p->title,
                'description'=>mb_substr($summary,0,1500),
                'vendor'=>(string)($p->brand_title??''),'price'=>(string)$p->price,
                'old_price'=>(string)($p->old_price??''),
                'sku'=>(string)$p->sku,'stock'=>(int)$p->stock,
            ];
            $variants=($p->product_type??'simple')==='simple'
                ?$repo->getProductVariants($productId,(int)$p->category_id):['axes'=>[],'items'=>[]];
            if(!$variants['items']) {
                $offers[]=array_merge($baseOffer,['id'=>'p-'.$productId]);
                $usedCategories[(int)$p->category_id]=true;
                continue;
            }
            $stockMode=(int)($productById[$productId]['option_stock_mode']??0);
            foreach($variants['items'] as $variant) {
                $variantOffer=$baseOffer;
                $cover=(string)($variant['image']??'');
                if($cover!=='')$variantOffer['image']=$base.$cover;
                $parts=[];
                foreach($variant['selection'] as $axis=>$value) {
                    $label=$value;
                    foreach($variants['axes'][$axis]['options']??[] as $choice) {
                        if($choice['value']===$value){$label=$choice['title'];break;}
                    }
                    $parts[]=$label;
                }
                $offers[]=array_merge($variantOffer,[
                    'id'=>'sku-'.(int)$variant['id'],
                    'title'=>$p->title.' — '.implode(', ',$parts),
                    'sku'=>(string)$variant['sku'],
                    'stock'=>$stockMode===0?(int)$p->stock:(int)$variant['stock']
                ]);
                $usedCategories[(int)$p->category_id]=true;
            }
        }
        $categoryById=[];
        foreach($categories as $c)$categoryById[(int)$c['id']]=$c;
        foreach(array_keys($usedCategories) as $categoryId){
            $seen=[];
            while(isset($categoryById[$categoryId]) && !isset($seen[$categoryId])){
                $seen[$categoryId]=true;
                $usedCategories[$categoryId]=true;
                $categoryId=(int)$categoryById[$categoryId]['parent_id'];
            }
        }
        $feedCategories=[];
        foreach($usedCategories as $categoryId=>$_) {
            if(isset($categoryById[$categoryId]))$feedCategories[]=$categoryById[$categoryId];
        }
        usort($feedCategories,static fn(array $a,array $b) => ((int)$a['id']) <=> ((int)$b['id']));
        $inventoryEnabled=(int)ComponentHelper::getParams('com_minimarket')
            ->get('inventory_enabled',1)===1;
        $xml=FeedYml::render($feed,$feedCategories,$offers,$base,null,$inventoryEnabled);
        $app->setHeader('Content-Type','application/xml; charset=UTF-8',true);
        $app->setHeader('X-Content-Type-Options','nosniff',true);
        $app->setHeader('Cache-Control','no-store,max-age=0',true);
        $app->setHeader('Referrer-Policy','no-referrer',true);
        $app->sendHeaders();
        echo $xml;
        $app->close();
    }
}
