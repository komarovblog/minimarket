<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
</nav>
<section class="com-minimarket-category">
    <h1><?php echo $this->escape($this->seoHeading??$this->category->title); ?></h1>
    <p class="mm-cart__nav"><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></a></p>
    <?php
    $image = explode('#', (string) (($this->seoImage ?? '') ?: ($this->category->image ?? '')), 2)[0];
    $safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
        && !preg_match('/[\x00-\x1f<>]/', $image);
    ?>
    <?php if ($safeImage && ($this->productListOptions['category_image'] || !empty($this->seoImage))) : ?>
        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
             alt="<?php echo $this->escape((string) (($this->seoImageAlt ?? '') ?: $this->category->image_alt ?: $this->category->title)); ?>"
             loading="<?php echo $this->productListOptions['lazy_loading']?'lazy':'eager'; ?>" class="com-minimarket-category__image">
    <?php endif; ?>
    <?php
        $categoryIntro=$this->seoIntro??(string)($this->category->introtext??'');
        $categoryDescription=$this->seoDescription??(string)($this->category->description??'');
    ?>
    <?php if ($this->productListOptions['category_intro'] && trim($categoryIntro)!=='') : ?>
        <div class="com-minimarket-category__intro"><?php echo InputFilter::getInstance()->clean($categoryIntro,'HTML'); ?></div>
    <?php endif; ?>
    <?php if ($this->productListOptions['category_description'] && trim($categoryDescription)!=='') : ?>
        <div class="com-minimarket-category__description"><?php echo InputFilter::getInstance()->clean($categoryDescription,'HTML'); ?></div>
    <?php endif; ?>

    <?php if ($this->children) : ?>
        <section class="com-minimarket-category__children">
            <h2><?php echo Text::_('COM_MINIMARKET_CHILD_CATEGORIES'); ?></h2>
            <ul class="list-unstyled">
                <?php foreach ($this->children as $child) : ?>
                    <li><a href="<?php echo $this->escape(RouteHelper::category((int) $child->id)); ?>">
                        <?php echo $this->escape($child->title); ?>
                    </a></li>
                <?php endforeach; ?>
            </ul>
            <?php if($this->childPageCount>1):
                $childUrl=RouteHelper::category((int)$this->category->id);
                $childHref=static function(int $page) use($childUrl):string {
                    return $childUrl.(str_contains($childUrl,'?')?'&':'?').'mm_child_page='.$page;
                };
            ?>
             <nav class="d-flex align-items-center gap-2" aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_CHILD_CATEGORIES')); ?>">
              <?php if($this->childPage>1): ?>
               <a href="<?php echo $this->escape($childHref($this->childPage-1)); ?>" class="btn btn-outline-secondary btn-sm"><?php echo Text::_('JPREV'); ?></a>
              <?php endif; ?>
              <span><?php echo $this->childPage; ?> / <?php echo $this->childPageCount; ?></span>
              <?php if($this->childPage<$this->childPageCount): ?>
               <a href="<?php echo $this->escape($childHref($this->childPage+1)); ?>" class="btn btn-outline-secondary btn-sm"><?php echo Text::_('JNEXT'); ?></a>
              <?php endif; ?>
             </nav>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div data-mm-ajax-content>
    <?php if ($this->displayProducts) : ?>
        <?php if ($this->facetData['facets']) : ?>
            <div class="mm-facet-layout" data-mm-facet-layout>
                <aside class="mm-facet-sidebar">
                    <?php include __DIR__ . '/filters.php'; ?>
                </aside>
                <div class="mm-facet-results">
        <?php endif; ?>
        <?php
        $grid = ($this->productListOptions['layout'] ?? 'list') === 'grid';
        $gridClasses = $grid
            ? \MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions::bootstrapGridClasses($this->productListOptions)
            : 'list-unstyled mm-product-list';
        ?>
        <?php if($this->productListOptions['allow_layout_switch']): ?>
         <div class="d-flex gap-2 align-items-center mb-2">
          <?php foreach(['grid'=>'COM_MINIMARKET_LAYOUT_GRID','list'=>'COM_MINIMARKET_LAYOUT_LIST'] as $layoutKey=>$label):
            $layoutLink=RouteHelper::category((int)$this->category->id);
            $layoutQuery=http_build_query(['mm_layout'=>$layoutKey,'mm_order'=>$this->sortOrder,'mmf'=>$this->facetSelection],
              '','&',PHP_QUERY_RFC3986);
          ?>
           <a class="btn btn-sm <?php echo ($grid?'grid':'list')===$layoutKey?'btn-primary':'btn-outline-secondary'; ?>"
              href="<?php echo $this->escape($layoutLink.(str_contains($layoutLink,'?')?'&':'?').$layoutQuery); ?>"
              aria-current="<?php echo ($grid?'grid':'list')===$layoutKey?'page':'false'; ?>"><?php echo Text::_($label); ?></a>
          <?php endforeach; ?>
         </div>
        <?php endif; ?>
        <?php if($this->productListOptions['show_sort']): ?>
        <form method="get" class="mm-sort-bar" action="<?php echo $this->escape(RouteHelper::category((int)$this->category->id)); ?>">
            <?php if($this->productListOptions['allow_layout_switch']): ?><input type="hidden" name="mm_layout" value="<?php echo $grid?'grid':'list'; ?>"><?php endif; ?>
            <label for="mm-category-sort"><?php echo Text::_('COM_MINIMARKET_SORT_LABEL'); ?></label>
            <select name="mm_order" id="mm-category-sort" class="form-select form-select-sm" data-mm-sort>
                <?php foreach ([
                    'newest'=>'COM_MINIMARKET_SORT_NEWEST',
                    'name'=>'COM_MINIMARKET_SORT_NAME',
                    'price_asc'=>'COM_MINIMARKET_SORT_PRICE_ASC',
                    'price_desc'=>'COM_MINIMARKET_SORT_PRICE_DESC'
                ] as $order=>$label): ?>
                    <option value="<?php echo $this->escape($order); ?>"<?php echo $this->sortOrder===$order?' selected':''; ?>>
                        <?php echo Text::_($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php foreach ($this->facetSelection as $fid=>$choice):
                foreach ($choice['values']??[] as $value): ?>
                    <input type="hidden" name="mmf[<?php echo $this->escape($fid); ?>][]"
                           value="<?php echo $this->escape($value); ?>">
                <?php endforeach;
                if (isset($choice['text'])): ?>
                    <input type="hidden" name="mmf[<?php echo $this->escape($fid); ?>]"
                           value="<?php echo $this->escape($choice['text']); ?>">
                <?php endif;
                foreach (['min','max'] as $boundary): if (isset($choice[$boundary])): ?>
                    <input type="hidden" name="mmf[<?php echo $this->escape($fid); ?>][<?php echo $boundary; ?>]"
                           value="<?php echo $this->escape($choice[$boundary]); ?>">
                <?php endif; endforeach; endforeach; ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo Text::_('COM_MINIMARKET_FILTER_APPLY'); ?></button>
        </form>
        <?php endif; ?>
        <ul class="<?php echo $this->escape($gridClasses); ?> list-unstyled mm-category-products" data-mm-category-layout="<?php echo $grid ? 'grid' : 'list'; ?>">
        <?php foreach ($this->products as $product) : ?>
            <li class="<?php echo $grid ? 'col' : 'mm-product-list__item'; ?>"
                data-mm-facet-product="<?php echo (int)$product->id; ?>"<?php echo ($this->facetMatches[(int)$product->id]??true) ? '' : ' hidden'; ?>>
                <div class="<?php echo $grid ? 'mm-product-card' : 'mm-product-list__inner'; ?>">
                    <?php
                    $photo = explode('#', (string) ($product->image ?? ''), 2)[0];
                    $hasPhoto = str_starts_with($photo, 'images/') && !str_contains($photo, '..')
                        && !preg_match('/[\x00-\x1f<>]/', $photo);
                    ?>
                    <?php
                    $slides=[];
                    if ($hasPhoto) $slides[]=['url'=>Uri::root().$photo,
                        'alt'=>(string)($product->image_alt?:$product->title)];
                    if ($this->productListOptions['product_gallery_enabled']) {
                        foreach ($this->categoryGalleries[(int)$product->id]??[] as $galleryPhoto) {
                            $path=explode('#',(string)($galleryPhoto['path']??''),2)[0];
                            if (!str_starts_with($path,'images/') || str_contains($path,'..')
                                || preg_match('/[\\x00-\\x1f<>]/',$path)) continue;
                            $url=Uri::root().$path;
                            if (in_array($url,array_column($slides,'url'),true)) continue;
                            $slides[]=['url'=>$url,
                                'alt'=>(string)($galleryPhoto['alt']?:$product->title)];
                            if (count($slides)>=$this->productListOptions['product_gallery_limit']) break;
                        }
                    }
                    ?>
                    <?php if ($slides) : ?>
                        <div class="mm-product-card__gallery" data-mm-card-gallery
                             data-mm-gallery-photos="<?php echo $this->escape(json_encode($slides,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)); ?>">
                            <a class="mm-product-card__media" href="<?php echo $this->escape(RouteHelper::product((int)$product->id)); ?>">
                                <img src="<?php echo $this->escape($slides[0]['url']); ?>"
                                     alt="<?php echo $this->escape($slides[0]['alt']); ?>"
                                     loading="<?php echo $this->productListOptions['lazy_loading']?'lazy':'eager'; ?>" class="mm-product-card__image" data-mm-card-gallery-image>
                            </a>
                            <?php if (count($slides)>1) : ?>
                                <button type="button" class="mm-gallery-arrow mm-gallery-arrow--prev"
                                    data-mm-card-gallery-step="-1" aria-label="<?php echo Text::_('COM_MINIMARKET_GALLERY_PREVIOUS'); ?>">‹</button>
                                <button type="button" class="mm-gallery-arrow mm-gallery-arrow--next"
                                    data-mm-card-gallery-step="1" aria-label="<?php echo Text::_('COM_MINIMARKET_GALLERY_NEXT'); ?>">›</button>
                                <span class="mm-gallery-indicator" data-mm-card-gallery-indicator>1 / <?php echo count($slides); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mm-product-card__info">
                        <a href="<?php echo $this->escape(RouteHelper::product((int) $product->id)); ?>">
                            <?php echo $this->escape($product->title); ?>
                        </a>
                        <?php if ($this->productListOptions['show_badges']) echo BadgeHelper::render($product); ?>
                        <div class="mm-product-card__price">
                            <span><?php echo $this->escape((string) $product->price); ?></span>
                            <?php echo BadgeHelper::oldPrice($product); ?>
                        </div>
                        <?php if (in_array($this->productListOptions['system_display_location'],['category','both'],true)) : ?>
                            <dl class="mm-product-card__attributes small mb-2">
                                <?php if (empty($this->variantsInProducts[(int)$product->id])) : ?>
                                <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></dt>
                                <dd><?php echo $this->escape((string)$product->sku); ?></dd>
                                <?php endif; ?>
                                <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></dt>
                                <dd><?php echo (int)$product->stock; ?></dd>
                            </dl>
                        <?php endif; ?>
                        <?php if ($this->productListOptions['show_list_attributes'] && !empty($this->categoryAttributeRows[(int)$product->id])) : ?>
                            <dl class="mm-product-card__attributes small mb-2">
                                <?php foreach (array_slice($this->categoryAttributeRows[(int)$product->id],0,5) as $attr) : ?>
                                <dt><?php echo $this->escape((string)$attr['title']); ?></dt>
                                <dd><?php echo $this->escape(implode(', ',array_map(
                                    static fn($v)=>(string)$v['text'],$attr['values']))); ?>
                                    <?php echo $this->escape((string)$attr['unit']); ?></dd>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                        <?php if ($this->productListOptions['show_list_options'] && !empty($this->categoryVariantOptions[(int)$product->id])) : ?>
                            <dl class="mm-product-card__attributes small mb-2">
                                <?php foreach (array_slice($this->categoryVariantOptions[(int)$product->id],0,4) as $axis) : ?>
                                    <dt><?php echo $this->escape($axis['title']); ?></dt>
                                    <dd><?php echo $this->escape(implode(', ',$axis['values'])); ?></dd>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                        <div class="mm-product-card__actions d-flex flex-wrap gap-2 mt-2">
                            <?php if ($this->productListOptions['show_add_to_cart']) : ?>
                                <?php if (empty($this->variantsInProducts[(int)$product->id])) : ?>
                                    <form method="post" action="<?php echo $this->escape(RouteHelper::cartAction('add')); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product->id; ?>">
                                        <input type="hidden" name="variant_id" value="0">
                                        <input type="hidden" name="quantity" value="1">
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <?php echo Text::_('COM_MINIMARKET_CATEGORY_ADD_TO_CART'); ?></button>
                                    </form>
                                <?php else : ?>
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="<?php echo $this->escape(RouteHelper::product((int)$product->id)); ?>">
                                        <?php echo Text::_('COM_MINIMARKET_CATEGORY_CHOOSE_OPTIONS'); ?></a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($this->productListOptions['quick_view']) : ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-mm-quick-view="<?php echo (int)$product->id; ?>">
                                    <?php echo Text::_('COM_MINIMARKET_CATEGORY_QUICK_VIEW'); ?></button>
                            <?php endif; ?>
                        </div>
                        <?php if ($this->productListOptions['quick_view']) : ?>
                            <dialog id="mm-category-preview-<?php echo (int)$product->id; ?>"
                                    class="mm-category-preview p-3 rounded">
                                <h3 class="h5"><?php echo $this->escape($product->title); ?></h3>
                                <?php if ($hasPhoto) : ?>
                                    <img loading="<?php echo $this->productListOptions['lazy_loading']?'lazy':'eager'; ?>" class="img-fluid rounded mb-2"
                                         src="<?php echo $this->escape(Uri::root().$photo); ?>"
                                         alt="<?php echo $this->escape((string)($product->image_alt?:$product->title)); ?>">
                                <?php endif; ?>
                                <p><?php echo $this->escape((string)$product->price); ?>
                                    <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></p>
                                <?php if (in_array($this->productListOptions['system_display_location'],['category','both'],true)
                                        && empty($this->variantsInProducts[(int)$product->id])) : ?>
                                <p class="small"><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?>:
                                    <?php echo $this->escape((string)$product->sku); ?></p>
                                <?php endif; ?>
                                <a class="btn btn-primary" href="<?php echo $this->escape(RouteHelper::product((int)$product->id)); ?>">
                                    <?php echo Text::_('COM_MINIMARKET_CATEGORY_PRODUCT_DETAILS'); ?></a>
                                <form method="dialog" class="mt-2"><button class="btn btn-outline-secondary">
                                    <?php echo Text::_('COM_MINIMARKET_CATEGORY_CLOSE'); ?></button></form>
                            </dialog>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if ($this->productPageCount > 1) :
            $url = RouteHelper::category((int)$this->category->id);
            $params = ['mm_order'=>$this->sortOrder,'mmf'=>$this->facetSelection];
            $pageUrl = static function(int $page) use ($url,$params):string {
                $params['mm_page']=$page;
                return $url.(str_contains($url,'?')?'&':'?')
                    .http_build_query($params,'','&',PHP_QUERY_RFC3986);
            };
        ?>
            <nav class="mm-category-pages" data-mm-category-pages
                 aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PAGINATION_LABEL')); ?>">
                <span><?php echo Text::sprintf('COM_MINIMARKET_PAGINATION_STATUS',
                    $this->productPage,$this->productPageCount); ?></span>
                <?php if ($this->productPage > 1) : ?>
                    <a href="<?php echo $this->escape($pageUrl($this->productPage-1)); ?>"
                       rel="prev" data-mm-page-link><?php echo Text::_('JPREV'); ?></a>
                <?php endif; ?>
                <?php if ($this->productPage < $this->productPageCount) : ?>
                    <a href="<?php echo $this->escape($pageUrl($this->productPage+1)); ?>"
                       rel="next" data-mm-page-link data-mm-next-page>
                       <?php echo Text::_('JNEXT'); ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
        <?php if ($this->facetData['facets']) : ?>
                <p class="mm-facet-empty" data-mm-facet-empty<?php echo in_array(true,$this->facetMatches,true) ? ' hidden' : ''; ?>>
                    <?php echo Text::_('COM_MINIMARKET_FILTER_NO_MATCHES'); ?>
                </p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</section>
