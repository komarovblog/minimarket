<?php
namespace MiniMarket\Component\Minimarket\Site\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;
use MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions;
use MiniMarket\Component\Minimarket\Site\Service\FacetedCatalog;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility;

class HtmlView extends BaseHtmlView
{
    public object $category;
    public ?string $seoHeading=null;
    public ?string $seoIntro=null;
    public ?string $seoDescription=null;
    public array $products = [];
    public array $categoryAttributeRows = [];
    public array $categoryVariantOptions = [];
    public array $categoryGalleries = [];
    public array $variantsInProducts = [];
    public array $children = [];
    public bool $displayProducts = true;
    public array $productListOptions = [];
    public array $facetData = ['facets'=>[], 'facts'=>[], 'skus'=>[]];
    public array $facetSelection = [];
    public array $facetMatches = [];
    public array $facetGroupCounts = [];
    public string $sortOrder = 'newest';
    public int $productPage = 1;
    public int $productsPerPage = 10;
    public int $productTotal = 0;
    public int $productPageCount = 1;

    public function display($tpl = null)
    {
        $id = Factory::getApplication()->input->getInt('id');

        if ($id < 1) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $repo = new CatalogRepository(Factory::getContainer()->get(DatabaseInterface::class));
        $category = $repo->getCategory($id);

        if ($category === null) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $this->category = $category;
        $options = ComponentHelper::getParams('com_minimarket');
        $this->productListOptions = CategoryDisplayOptions::resolve($category, $options);
        $this->productsPerPage = $this->productListOptions['products_per_page'];
        $display = $this->productListOptions;
        // Always show every published direct child; product pagination is independent.
        $this->children=$display['show_children'] ? $repo->getChildCategories($id) : [];
        if($display['allow_layout_switch']){
            $requestedLayout=Factory::getApplication()->input->getCmd('mm_layout','');
            if(in_array($requestedLayout,['grid','list'],true)){
                $this->productListOptions['layout']=$requestedLayout;
            }
        }
        $this->displayProducts = $display['show_products'] || $display['show_bundles']
            || $display['show_descendant_products'] || $display['show_descendant_bundles'];
        $this->products = $this->displayProducts
            ? $repo->getProductsForCategory(
                $id, $display['show_products'], $display['show_bundles'],
                $display['show_descendant_products'], $display['show_descendant_bundles']
            ) : [];
        $requestedOrder=Factory::getApplication()->input->getCmd('mm_order','newest');
        $this->sortOrder=in_array($requestedOrder,['newest','name','price_asc','price_desc'],true)
            ?$requestedOrder:'newest';
        if ($this->sortOrder==='name') {
            usort($this->products,static fn($x,$y):int=>
                strnatcasecmp((string)$x->title,(string)$y->title) ?: ((int)$x->id<=>(int)$y->id));
        } elseif ($this->sortOrder==='price_asc') {
            usort($this->products,static fn($x,$y):int=>
                ((float)$x->price<=>(float)$y->price) ?: ((int)$x->id<=>(int)$y->id));
        } elseif ($this->sortOrder==='price_desc') {
            usort($this->products,static fn($x,$y):int=>
                ((float)$y->price<=>(float)$x->price) ?: ((int)$x->id<=>(int)$y->id));
        }
        // Query all public facts for this exact list in bounded batches.
        if ($this->products) {
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $this->facetData=FacetedCatalog::build(
                $db,$this->products,(int)$options->get('inventory_enabled',1)===1,
                $this->productListOptions);
            $raw=Factory::getApplication()->input->get('mmf',[],'array');
            $this->facetSelection=FacetedCatalog::selection(
                is_array($raw)?$raw:[],$this->facetData['facets']);
            foreach ($this->products as $product) {
                $pid=(int)$product->id;
                $this->facetMatches[$pid]=FacetedCatalog::matches(
                    $this->facetData['facts'][$pid]??[],
                    $this->facetData['skus'][$pid]??[],
                    $this->facetData['facets'],$this->facetSelection);
            }
            // Counts include all matching catalog products, NOT just the
            // current page. Ignore the selected values of this same facet.
            foreach ($this->facetData['facets'] as $fid=>&$facet) {
                if (!in_array($facet['mode'],['dropdown','checkboxes','values','radio','swatches','boolean'],true)) {
                    continue;
                }
                $other=$this->facetSelection;
                unset($other[$fid]);
                $groupCount=0;
                foreach ($this->products as $candidate) {
                    $pid=(int)$candidate->id;
                    if (FacetedCatalog::matches(
                        $this->facetData['facts'][$pid]??[],
                        $this->facetData['skus'][$pid]??[],
                        $this->facetData['facets'],$other)) $groupCount++;
                }
                $this->facetGroupCounts[$fid]=$groupCount;
                foreach ($facet['choices'] as &$choice) {
                    $trial=$other;
                    $trial[$fid]=['values'=>[$choice['value']]];
                    $count=0;
                    foreach ($this->products as $candidate) {
                        $pid=(int)$candidate->id;
                        if (FacetedCatalog::matches(
                            $this->facetData['facts'][$pid]??[],
                            $this->facetData['skus'][$pid]??[],
                            $this->facetData['facets'],$trial)) $count++;
                    }
                    $choice['count']=$count;
                }
                unset($choice);
            }
            unset($facet);
        }
        // Pagination is performed only AFTER matching all published products.
        // The browser receives just the requested slice; AJAX pages and sort
        // operate on the complete server catalog, not on loaded DOM nodes.
        $filtered=array_values(array_filter($this->products,fn($p):bool=>
            $this->facetMatches[(int)$p->id]??true));
        $this->productTotal=count($filtered);
        $this->productPageCount=max(1,(int)ceil($this->productTotal/$this->productsPerPage));
        $this->productPage=min($this->productPageCount,
            max(1,Factory::getApplication()->input->getInt('mm_page',1)));
        $this->products=array_slice($filtered,
            ($this->productPage-1)*$this->productsPerPage,$this->productsPerPage);
        if ($this->products) {
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $ids=array_map(static fn($p):int=>(int)$p->id,$this->products);
            $q=$db->getQuery(true)->select('DISTINCT product_id')
                ->from($db->quoteName('#__minimarket_product_variants'))
                ->where('product_id IN ('.implode(',',$ids).')');
            $db->setQuery($q);
            $this->variantsInProducts=array_fill_keys(array_map('intval',(array)$db->loadColumn()),true);
            if ($this->productListOptions['product_gallery_enabled']) {
                $gq=$db->getQuery(true)->select(['product_id','path','alt'])
                    ->from($db->quoteName('#__minimarket_product_images'))
                    ->where('product_id IN ('.implode(',',$ids).')')
                    ->order('product_id ASC, sort_order ASC, id ASC');
                $db->setQuery($gq,0,count($ids)*30);
                foreach ((array)$db->loadAssocList() as $photo) {
                    $pid=(int)$photo['product_id'];
                    if (count($this->categoryGalleries[$pid]??[])>=8) continue;
                    $this->categoryGalleries[$pid][]=$photo;
                }
            }
            $optionLocations=[];$optionIds=[];
            $optionHierarchy=CategoryOrderOptions::hierarchy($db);
            $optionFlagsByCategory=[];
            $q=$db->getQuery(true)->select(['id','code','display_location'])
                ->from($db->quoteName('#__minimarket_order_options'))
                ->where('state=1');
            $db->setQuery($q);
            foreach((array)$db->loadAssocList() as $row) {
                $code=(string)$row['code'];
                $optionLocations[$code]=(string)$row['display_location'];
                $optionIds[$code]=(int)$row['id'];
            }
            foreach($this->products as $product) {
                $pid=(int)$product->id;
                $this->categoryAttributeRows[$pid]=$repo->getProductAttributes(
                    $pid,(int)$product->category_id,'category');
                if (isset($this->variantsInProducts[$pid])) {
                    $categoryId=(int)$product->category_id;
                    if(!isset($optionFlagsByCategory[$categoryId])){
                        $optionFlagsByCategory[$categoryId]=CategoryListVisibility::resolve(
                            $categoryId,$optionHierarchy['parents'],$optionHierarchy['rules'],
                            $optionHierarchy['list_flags']??[]);
                    }
                    $visibleOptions=$optionFlagsByCategory[$categoryId];
                    $variants=$repo->getProductVariants($pid,$categoryId);
                    foreach($variants['axes']??[] as $code=>$axis) {
                        $linkedOptionId=$optionIds[(string)$code]??0;
                        if($linkedOptionId>0 && !($visibleOptions[$linkedOptionId]??false))continue;
                        $location=$optionLocations[(string)$code]??'inherit';
                        if ($location==='inherit') {
                            $location=$this->productListOptions['option_display_location'];
                        }
                        if (!in_array($location,['category','both'],true))continue;
                        $values=[];
                        foreach($variants['items']??[] as $variant) {
                            $chosen=(string)($variant['selection'][$code]??'');
                            foreach($axis['options']??[] as $option) {
                                if ((string)$option['value']===$chosen)
                                    $values[(string)$option['title']]=(string)$option['title'];
                            }
                        }
                        $this->categoryVariantOptions[$pid][]=['title'=>(string)$axis['title'],
                            'values'=>array_slice(array_values($values),0,6)];
                    }
                }
            }
        }
        $document = $this->getDocument();
        $metaTitle = trim((string) ($category->meta_title ?? ''));
        $document->setTitle($metaTitle !== '' ? $metaTitle : (string) $category->title);
        $metaDescription = trim((string) ($category->meta_description ?? ''));
        if ($metaDescription === '') {
            $source = (string) ($category->introtext ?: $category->description ?: '');
            $metaDescription = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            $metaDescription = mb_substr($metaDescription, 0, 160);
        }
        if ($metaDescription !== '') {
            $document->setDescription($metaDescription);
        }
        $robots = (string) ($category->meta_robots ?? '');
        if (FacetedCatalog::active($this->facetSelection)) {
            $document->setMetaData('robots','noindex,follow');
        } elseif (in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $document->setMetaData('robots', $robots);
        }
        $document->setMetaData('og:type', 'website', 'property');
        $document->setMetaData('og:title',
            (string) ($category->og_title ?: $metaTitle ?: $category->title), 'property');
        $ogDescription = trim((string) ($category->og_description ?: $metaDescription));
        if ($ogDescription !== '') {
            $document->setMetaData('og:description', $ogDescription, 'property');
        }
        $ogImage = explode('#', (string) ($category->og_image ?: $category->image ?: ''), 2)[0];
        if (str_starts_with($ogImage, 'images/')
            && !str_contains($ogImage, '..')
            && !preg_match('/[\x00-\x1f<>]/', $ogImage)) {
            $document->setMetaData('og:image', Uri::root() . $ogImage, 'property');
            $document->setMetaData('og:image:alt',
                (string) ($category->og_image_alt ?: $category->image_alt ?: $category->title), 'property');
        }
        SeoHelper::setCanonical($document, 'category', $id);

        $this->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.badges',
                'media/com_minimarket/css/badges.css')
            ->registerAndUseStyle('com_minimarket.catalog-layout',
                'media/com_minimarket/css/catalog-layout.css')
            ->registerAndUseScript('com_minimarket.faceted-catalog',
                'media/com_minimarket/js/faceted-catalog.js',[],['defer'=>true])
            ->registerAndUseScript('com_minimarket.category-actions',
                'media/com_minimarket/js/category-actions.js',[],['defer'=>true])
            ->registerAndUseScript('com_minimarket.category-card-gallery',
                'media/com_minimarket/js/category-card-gallery.js',[],['defer'=>true]);

        parent::display($tpl);
    }
}
