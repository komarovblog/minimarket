<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
use MiniMarket\Component\Minimarket\Site\Service\ProductAttributeCatalog;

// One product URL and one product price; gallery is filtered by the selected
// physical SKU's stable variant_id. Restricted images are not in HTML/JSON for
// unrelated SKUs.
$cover = explode('#', (string) ($this->product->image ?? ''), 2)[0];
$isSafeImage = static function (string $path): bool {
    return str_starts_with($path, 'images/') && !str_contains($path, '..')
        && !preg_match('/[\x00-\x1f<>?#\\\\]/', $path);
};
$productCover = $isSafeImage($cover)
    ? ['url'=>Uri::root().$cover, 'alt'=>(string)(
        $this->product->image_alt ?: $this->product->title)]
    : null;
$defaultVariant = $this->variants['items'][0] ?? null;
$requestedVariant = (int)\Joomla\CMS\Factory::getApplication()->input->getInt('variant', 0);
if ($requestedVariant > 0) {
    foreach ($this->variants['items'] as $availableVariant) {
        if ((int)$availableVariant['id'] === $requestedVariant) {
            $defaultVariant = $availableVariant;
            break;
        }
    }
}
$images = [];
if ($defaultVariant) {
    $add = static function (string $path, string $alt) use (&$images, $isSafeImage): void {
        if ($isSafeImage($path) && !isset($images[$path])) {
            $images[$path] = ['url'=>Uri::root().$path, 'alt'=>$alt];
        }
    };
    $specialCover = false;
    foreach ($defaultVariant['gallery'] ?? [] as $photo) {
        if (!empty($photo['cover'])) {
            $add((string)$photo['path'], (string)($photo['alt'] ?: $this->product->title));
            $specialCover = true;
        }
    }
    // Existing v3 per-SKU cover remains a fallback when no gallery cover is
    // assigned. The product cover is the global default for every SKU.
    if (!$specialCover && !empty($defaultVariant['image'])) {
        $add((string)$defaultVariant['image'],
             (string)($defaultVariant['image_alt'] ?: $this->product->title));
    }
    if ($productCover && !$specialCover) $add($cover, $productCover['alt']);
    foreach ($defaultVariant['gallery'] ?? [] as $photo) {
        $add((string)$photo['path'], (string)($photo['alt'] ?: $this->product->title));
    }
    foreach ($defaultVariant['photos'] ?? [] as $photo) {
        $add((string)$photo['path'], (string)($photo['alt'] ?: $this->product->title));
    }
    $baseImages = $productCover && !$specialCover ? [$productCover] : [];
} else {
    if ($productCover) $images[$cover] = $productCover;
    foreach ($this->gallery as $photo) {
        $path = explode('#', (string)($photo->path ?? ''), 2)[0];
        if ($isSafeImage($path) && !isset($images[$path])) {
            $images[$path] = ['url'=>Uri::root().$path,
                'alt'=>(string)($photo->alt ?: $this->product->title)];
        }
    }
    $baseImages = array_values($images);
}
$images = array_values($images);
$main = $images[0] ?? null;
$hasCustomDescription=$defaultVariant && (int)($defaultVariant['description_mode']??0)===1;
$initialIntro=$hasCustomDescription
    ? (string)($defaultVariant['introtext_override']??'')
    : (string)($this->product->introtext??'');
$initialDescription=$hasCustomDescription
    ? (string)($defaultVariant['description_override']??'')
    : (string)($this->product->description??'');
$systemParams=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
$legacySystem=(string)$systemParams->get('system_display_location','both');
$showSystemField=static function(string $name) use ($systemParams,$legacySystem):bool {
    $location=(string)$systemParams->get('system_'.$name.'_display_location',$legacySystem);
    return in_array($location,['product','both'],true);
};
$showSku=$showSystemField('sku');
$showPrice=$showSystemField('price');
$showStock=$showSystemField('stock');
$isPhysical=(string)($this->product->delivery_type??'physical')==='physical';
$canOrder=$isPhysical || \MiniMarket\Component\Minimarket\Site\Service\DigitalAsset::isReady($this->product);
$showStock=$showStock && $isPhysical;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
    <span> / </span>
    <a href="<?php echo $this->escape(RouteHelper::category((int) $this->product->category_id)); ?>"><?php echo $this->escape($this->product->category_title); ?></a>
</nav>
<article class="com-minimarket-product" data-mm-product-page data-mm-page-layout="<?php echo $this->escape($this->pageLayout); ?>">
    <h1><?php echo $this->escape($this->product->title); ?></h1>
    <?php echo BadgeHelper::render($this->product); ?>
    <?php if ((string)($this->product->brand_title ?? '') !== '') : ?>
        <p><strong><?php echo Text::_('COM_MINIMARKET_BRAND'); ?>:</strong>
            <?php echo $this->escape((string)$this->product->brand_title); ?></p>
    <?php endif; ?>
    <?php if (!empty($this->collections)) : ?>
        <p><strong><?php echo Text::_('COM_MINIMARKET_COLLECTIONS'); ?>:</strong>
            <?php echo $this->escape(implode(', ',array_map(
                static fn($row)=>(string)$row->title,$this->collections))); ?></p>
    <?php endif; ?>
    <?php if (($this->product->product_type ?? 'simple') === 'bundle') : ?>
        <section class="mm-bundle-composition" aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BUNDLE_COMPOSITION')); ?>">
            <h2><?php echo Text::_('COM_MINIMARKET_BUNDLE_COMPOSITION'); ?></h2>
            <?php if ($this->bundleComponents) : ?>
                <ul>
                <?php foreach ($this->bundleComponents as $entry) : ?>
                    <li>
                        <a href="<?php echo $this->escape(RouteHelper::product((int)$entry['component_product_id'])); ?>">
                            <?php echo $this->escape((string)$entry['title']); ?>
                        </a>
                        <?php if (!empty($entry['variant_sku'])) : ?>
                            (SKU: <?php echo $this->escape((string)$entry['variant_sku']); ?>)
                        <?php endif; ?>
                        — <?php echo (int)$entry['quantity']; ?> ×
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php echo Text::_('COM_MINIMARKET_BUNDLE_NO_COMPONENTS'); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($main !== null) : ?>
        <div class="mm-product-gallery" data-mm-product-gallery>
            <a class="mm-product-gallery__main-link" data-mm-gallery-main-link
               href="<?php echo $this->escape($main['url']); ?>" target="_blank" rel="noopener"
               aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_GALLERY_OPEN_ORIGINAL')); ?>">
                <img class="mm-product-gallery__main" data-mm-gallery-main
                     src="<?php echo $this->escape($main['url']); ?>"
                     alt="<?php echo $this->escape($main['alt']); ?>">
            </a>
            <div class="mm-product-gallery__thumbs" role="group"
                     aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_GALLERY_IMAGES')); ?>" <?php echo count($images) <= 1 ? 'hidden' : ''; ?>>
                    <?php foreach ($images as $i => $img) : ?>
                        <button type="button" class="mm-product-gallery__thumb"
                                data-mm-gallery-thumb data-mm-gallery-src="<?php echo $this->escape($img['url']); ?>"
                                data-mm-gallery-alt="<?php echo $this->escape($img['alt']); ?>"
                                aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                                aria-label="<?php echo $this->escape(Text::sprintf('COM_MINIMARKET_GALLERY_IMAGE_NUMBER', $i + 1)); ?>">
                            <img src="<?php echo $this->escape($img['url']); ?>"
                                 alt="" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
        </div>
    <?php endif; ?>
    <?php
    // Exactly one local MP4/WebM promo is allowed by the form validator.
    $promoVideo = null;
    foreach ($this->videos as $videoEntry) {
        $candidate = (string)($videoEntry->source ?? '');
        if ((int)($videoEntry->is_promo ?? 0) === 1 &&
            preg_match('~^(images|media)/[a-zA-Z0-9_./ -]+\\.(?:mp4|webm)$~iD', $candidate) &&
            !str_contains($candidate, '..') && !str_contains($candidate, '//')) {
            $promoVideo = $videoEntry;
            break;
        }
    }
    ?>
    <?php if ($promoVideo !== null) : ?>
        <?php
        $promoPoster = explode('#', (string)($promoVideo->poster ?? ''), 2)[0];
        $promoPosterUrl = $isSafeImage($promoPoster) ? Uri::root() . $promoPoster : '';
        ?>
        <aside class="mm-promo-video" data-mm-promo aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_VIDEO_PROMO')); ?>">
            <div class="mm-promo-video__media">
                <video data-mm-promo-player muted playsinline preload="metadata"
                       role="button" tabindex="0" aria-expanded="false"
                       aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_VIDEO_PROMO_EXPAND')); ?>"
                       <?php if ($promoPosterUrl !== '') : ?>poster="<?php echo $this->escape($promoPosterUrl); ?>"<?php endif; ?>>
                    <source src="<?php echo $this->escape(Uri::root() . (string)$promoVideo->source); ?>">
                </video>
            </div>
            <div class="mm-promo-video__actions">
                <button type="button" class="btn btn-primary btn-sm" data-mm-promo-expand aria-expanded="false">
                    <?php echo Text::_('COM_MINIMARKET_VIDEO_PROMO_EXPAND'); ?>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-mm-promo-close
                        aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_VIDEO_PROMO_CLOSE')); ?>">×</button>
            </div>
            <div class="mm-promo-video__expanded" data-mm-promo-panel hidden>
                <p><?php echo $this->escape((string)($promoVideo->title ?: $this->product->title)); ?></p>
                <?php if ($canOrder) : ?>
                    <a class="btn btn-primary" href="#mm-cart-add-form">
                        <?php echo Text::_('COM_MINIMARKET_VIDEO_PROMO_BUY'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </aside>
    <?php endif; ?>
    <?php if (!empty($this->videos)) : ?>
        <section class="mm-product-videos" data-mm-model-videos
                 <?php echo $defaultVariant && (int)($defaultVariant['video_mode']??0)!==0 ? 'hidden' : ''; ?> aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_TAB_VIDEOS')); ?>">
            <h2><?php echo Text::_('COM_MINIMARKET_PRODUCT_TAB_VIDEOS'); ?></h2>
            <?php foreach ($this->videos as $item) :
                if ((int)($item->is_promo ?? 0) === 1) continue; ?>
                <?php
                // Never render admin-entered HTML/iframe src directly.
                $src = trim((string) ($item->source ?? ''));
                $kind = '';
                $player = '';
                $poster = trim((string) ($item->poster ?? ''));
                $posterUrl = $isSafeImage($poster) ? Uri::root() . $poster : '';
                if (preg_match('~^https://[a-zA-Z0-9.-]+(?::443)?/[^<>"\\x00-\\x1f]+\\.(?:mp4|webm)(?:\\?[^<>"\\x00-\\x1f]*)?$~iD', $src)) {
                    $kind = 'file';
                    $player = $src;
                } elseif (preg_match('~^(images|media)/[a-zA-Z0-9_./ -]+\\.(?:mp4|webm)$~iD', $src)
                    && !str_contains($src, '..') && !str_contains($src, '//')) {
                    $kind = 'file';
                    $player = Uri::root() . $src;
                } elseif (preg_match('~^https://www\\.youtube\\.com/watch\\?v=([a-zA-Z0-9_-]{11})$~D', $src, $m)) {
                    $kind = 'embed';
                    $player = 'https://www.youtube-nocookie.com/embed/' . $m[1];
                } elseif (preg_match('~^https://rutube\\.ru/video/([a-zA-Z0-9]{12,50})/$~D', $src, $m)) {
                    $kind = 'embed';
                    $player = 'https://rutube.ru/play/embed/' . $m[1];
                }
                ?>
                <?php if ($kind !== '') : ?>
                    <div class="mm-product-videos__item">
                        <?php if (trim((string) ($item->title ?? '')) !== '') : ?>
                            <h3><?php echo $this->escape((string) $item->title); ?></h3>
                        <?php endif; ?>
                        <?php if ($kind === 'file') : ?>
                            <video controls preload="metadata" <?php if ($posterUrl !== '') : ?>poster="<?php echo $this->escape($posterUrl); ?>"<?php endif; ?> style="width:100%;max-width:720px">
                                <source src="<?php echo $this->escape($player); ?>">
                            </video>
                        <?php else : ?>
                            <?php if ($posterUrl !== '') : ?>
                                <details class="mm-video-preview">
                                    <summary style="cursor:pointer;max-width:720px">
                                        <img src="<?php echo $this->escape($posterUrl); ?>" alt="<?php echo $this->escape((string) ($item->title ?: Text::_('COM_MINIMARKET_PRODUCT_TAB_VIDEOS'))); ?>" loading="lazy" style="width:100%;aspect-ratio:16/9;object-fit:cover">
                                    </summary>
                            <?php endif; ?>
                            <iframe src="<?php echo $this->escape($player); ?>" title="<?php echo $this->escape(
                                (string) ($item->title ?: Text::_('COM_MINIMARKET_PRODUCT_TAB_VIDEOS'))
                            ); ?>" loading="lazy" allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; encrypted-media; picture-in-picture; fullscreen"
                                style="width:100%;max-width:720px;aspect-ratio:16/9;border:0"></iframe>
                            <?php if ($posterUrl !== '') : ?></details><?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <?php if (!empty($this->variants['items'])) : ?>
        <section class="mm-product-videos" data-mm-sku-videos
            <?php echo !$defaultVariant || (int)($defaultVariant['video_mode']??0)!==1 ? 'hidden' : ''; ?>>
            <h2><?php echo Text::_('COM_MINIMARKET_PRODUCT_TAB_VIDEOS'); ?></h2>
            <div data-mm-sku-video-list></div>
        </section>
        <section class="mm-variants" data-mm-variants
             data-mm-selected="<?php echo (int) ($defaultVariant['id'] ?? 0); ?>"
             data-mm-variants-data="<?php echo $this->escape(json_encode(
                 $this->variants['items'],
                 JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
             )); ?>"
             data-mm-root="<?php echo $this->escape(Uri::root()); ?>"
             data-mm-in-stock="<?php echo $this->escape(Text::_('COM_MINIMARKET_VARIANT_IN_STOCK')); ?>"
             data-mm-out-of-stock="<?php echo $this->escape(Text::_('COM_MINIMARKET_VARIANT_OUT_OF_STOCK')); ?>"
             data-mm-intro-base="<?php echo $this->escape(InputFilter::getInstance()->clean(
                 (string)($this->product->introtext??''),'HTML')); ?>"
             data-mm-description-base="<?php echo $this->escape(InputFilter::getInstance()->clean(
                 (string)($this->product->description??''),'HTML')); ?>"
             data-mm-stock-mode="<?php echo (int)($this->product->option_stock_mode??0); ?>"
             data-mm-inventory-enabled="<?php echo $this->inventoryEnabled && $isPhysical ? '1' : '0'; ?>"
             data-mm-stock-base="<?php echo (int)$this->product->stock; ?>"
             data-mm-gallery-base="<?php echo $this->escape(json_encode(
                 $baseImages,
                 JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
             )); ?>">
            <h2><?php echo Text::_('COM_MINIMARKET_PRODUCT_TAB_VARIANTS'); ?></h2>
            <div class="mm-variants__axes">
                <?php foreach ($this->variants['axes'] as $axisId => $axis) : ?>
                    <label class="mm-variants__axis">
                        <span><?php echo $this->escape($axis['title']); ?></span>
                        <select class="form-select" data-mm-variant-axis="<?php echo $this->escape((string)$axisId); ?>">
                            <?php foreach ($axis['options'] as $option) : ?>
                                <option value="<?php echo $this->escape($option['value']); ?>"
                                    <?php echo (($defaultVariant['selection'][$axisId] ?? '') === $option['value']) ? 'selected' : ''; ?>>
                                    <?php echo $this->escape($option['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
            <p data-mm-variant-availability role="status">
                <?php echo Text::_('COM_MINIMARKET_VARIANT_AVAILABILITY'); ?>
            </p>
        </section>
    <?php endif; ?>
    <div class="com-minimarket-product__intro" data-mm-variant-intro
         <?php echo trim($initialIntro)==='' ? 'hidden' : ''; ?>>
        <?php echo InputFilter::getInstance()->clean($initialIntro, 'HTML'); ?>
    </div>
    <dl>
        <dt<?php echo $showSku?'':' hidden'; ?>><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></dt>
        <dd data-mm-variant-sku<?php echo $showSku?'':' hidden'; ?>><?php echo $this->escape((string) ($defaultVariant['sku'] ?? $this->product->sku)); ?></dd>
        <dt<?php echo $showPrice?'':' hidden'; ?>><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></dt>
        <dd data-mm-variant-price<?php echo $showPrice?'':' hidden'; ?>><?php echo $this->escape((string) $this->product->price); ?>
            <?php echo BadgeHelper::oldPrice($this->product); ?></dd>
        <dt<?php echo $showStock?'':' hidden'; ?>><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></dt>
        <dd data-mm-variant-stock<?php echo $showStock?'':' hidden'; ?>><?php echo $this->inventoryEnabled
            ? (int) (((int)($this->product->option_stock_mode??0)===0)
                ? $this->product->stock
                : ($defaultVariant['stock'] ?? $this->product->stock))
            : '—'; ?></dd>
    </dl>
    <?php
    $initialStock = $defaultVariant && (int)($this->product->option_stock_mode??1) !== 0
        ? (int)$defaultVariant['stock'] : (int)$this->product->stock;
    $initialCanBuy = $canOrder && (!$this->inventoryEnabled || !$isPhysical || $initialStock > 0);
    ?>
    <?php if ($canOrder) : ?>
        <form action="<?php echo $this->escape(RouteHelper::cartAction('add')); ?>"
              method="post" class="mm-cart-add" id="mm-cart-add-form" data-mm-cart-form>
            <input type="hidden" name="product_id" value="<?php echo (int)$this->product->id; ?>">
            <input type="hidden" name="variant_id" value="<?php echo (int)($defaultVariant['id'] ?? 0); ?>"
                   data-mm-cart-variant>
            <label><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?>
                <input class="form-control" type="number" name="quantity" min="1" max="99"
                       value="1" step="1" required>
            </label>
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-primary" data-mm-cart-add
                    <?php echo $initialCanBuy ? '' : 'disabled'; ?>>
                <?php echo Text::_('COM_MINIMARKET_CART_ADD'); ?></button>
        </form>
    <?php endif; ?>

    <?php
    $detailTabs=['description'=>'COM_MINIMARKET_PRODUCT_DESCRIPTION_SECTION'];
    if($this->attributes)$detailTabs['attributes']='COM_MINIMARKET_PRODUCT_ATTRIBUTES';
    if($this->reviewsEnabled)$detailTabs['reviews']='COM_MINIMARKET_REVIEWS_TITLE';
    ?>
    <?php if($this->pageLayout==='tabs'): ?>
    <nav class="nav nav-tabs mm-product-tabs mb-3" role="tablist"
         aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_DETAIL_TABS_TITLE')); ?>">
        <?php foreach($detailTabs as $tabKey=>$tabLabel): ?>
        <button class="nav-link<?php echo $tabKey==='description'?' active':''; ?>"
                type="button" id="mm-product-tab-<?php echo $tabKey; ?>" role="tab"
                data-mm-product-tab="<?php echo $tabKey; ?>"
                aria-controls="mm-product-<?php echo $tabKey; ?>"
                aria-selected="<?php echo $tabKey==='description'?'true':'false'; ?>"
                tabindex="<?php echo $tabKey==='description'?'0':'-1'; ?>">
            <?php echo Text::_($tabLabel); ?>
        </button>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if($this->showSectionNav): ?>
    <nav class="mm-product-section-nav nav nav-pills gap-2 my-3" data-mm-section-nav
         aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_DETAIL_QUICK_NAV')); ?>">
        <?php foreach($detailTabs as $tabKey=>$tabLabel): ?>
        <a class="nav-link" href="#mm-product-<?php echo $tabKey; ?>"
           data-mm-section-link="<?php echo $tabKey; ?>"><?php echo Text::_($tabLabel); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!empty($this->attributes)) : ?>
        <section class="mm-product-attributes" id="mm-product-attributes" data-mm-tab-panel="attributes" aria-labelledby="mm-product-attributes-heading">
            <h2 id="mm-product-attributes-heading"><?php echo Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES'); ?></h2>
            <dl class="mm-product-attributes__list">
                <?php foreach ($this->attributes as $attribute) : ?>
                    <div class="mm-product-attributes__row">
                        <dt><?php echo $this->escape($attribute['title']); ?></dt>
                        <dd>
                            <?php if ($attribute['type'] === 'editor') : ?>
                                <?php foreach ($attribute['values'] as $entry) : ?>
                                    <div class="mm-product-attributes__html">
                                        <?php echo InputFilter::getInstance()->clean($entry['text'], 'HTML'); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <?php foreach ($attribute['values'] as $entry) : ?>
                                    <?php
                                    $value = $entry['text'];
                                    $type = $attribute['type'];
                                    $file = $type === 'document' ? ProductAttributeCatalog::safeDocument($value) : '';
                                    $image = ProductAttributeCatalog::safeImage($entry['image']);
                                    $hex = $type === 'color' && preg_match('/^#[a-fA-F0-9]{6}$/D', $entry['hex'])
                                        ? $entry['hex'] : '';
                                    ?>
                                    <?php if ($type === 'document' && $file === '') : ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <span class="mm-product-attributes__value">
                                        <?php if ($image !== '') : ?>
                                            <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
                                                 alt="" loading="lazy" class="mm-product-attributes__sample">
                                        <?php elseif ($hex !== '') : ?>
                                            <span aria-hidden="true" class="mm-product-attributes__swatch"
                                                  style="background-color:<?php echo $this->escape($hex); ?>"></span>
                                        <?php endif; ?>
                                        <?php if ($file !== '') : ?>
                                            <a href="<?php echo $this->escape(Uri::root() . $file); ?>"
                                               target="_blank" rel="noopener">
                                                <?php echo Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTE_OPEN_FILE'); ?>
                                            </a>
                                        <?php elseif ($type === 'boolean') : ?>
                                            <?php echo Text::_($value === '1' ? 'JYES' : 'JNO'); ?>
                                        <?php elseif ($type !== 'color'
                                            || ($attribute['color_display'] ?? 'both') !== 'swatch'
                                            || ($hex === '' && $image === '')) : ?>
                                            <?php
                                            $displayValue = $type === 'number' && str_contains($value, '.')
                                                ? rtrim(rtrim($value, '0'), '.') : $value;
                                            echo $this->escape($displayValue);
                                            ?>
                                            <?php if ($type === 'number' && $attribute['unit'] !== '') : ?>
                                                <?php echo ' ' . $this->escape($attribute['unit']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>
    <section class="mm-product-description" id="mm-product-description" data-mm-tab-panel="description"
             aria-labelledby="mm-product-description-heading">
        <h2 id="mm-product-description-heading"><?php echo Text::_('COM_MINIMARKET_PRODUCT_DESCRIPTION_SECTION'); ?></h2>
    <div class="com-minimarket-product__description" data-mm-variant-description
         <?php echo trim($initialDescription)==='' ? 'hidden' : ''; ?>>
        <?php echo InputFilter::getInstance()->clean($initialDescription, 'HTML'); ?>
    </div>
    </section>
    <?php if ($this->reviewsEnabled) : ?>
    <section class="mm-product-reviews mt-4" id="mm-product-reviews" data-mm-tab-panel="reviews">
        <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_REVIEWS_TITLE'); ?></h2>
        <?php if (!$this->reviews) : ?>
            <p><?php echo Text::_('COM_MINIMARKET_REVIEWS_EMPTY'); ?></p>
        <?php endif; ?>
        <?php foreach ($this->reviews as $review) :
            $photo=explode('#',(string)($review->photo_path??''),2)[0];
            $photoOk=str_starts_with($photo,'images/') && !str_contains($photo,'..')
                && preg_match('~\.(?:jpg|jpeg|png|webp|avif)$~iD',$photo);
            $video=(string)($review->video_url??'');
            $videoSafe=(str_starts_with($video,'https://') || preg_match('~^(images|media)/~',$video))
                && !str_contains($video,'..') && !preg_match('/[<>"\\x00-\\x1f]/',$video);
        ?>
        <article class="border rounded p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <strong><?php echo $this->escape($review->author_name); ?></strong>
                <span aria-label="<?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?>">
                    <?php echo str_repeat('★',max(0,min(5,(int)$review->rating))); ?>
                    <?php echo str_repeat('☆',5-max(0,min(5,(int)$review->rating))); ?>
                </span>
            </div>
            <?php if((string)$review->title!=='') : ?><h3 class="h6 mt-2"><?php echo $this->escape($review->title); ?></h3><?php endif; ?>
            <p><?php echo nl2br($this->escape($review->body)); ?></p>
            <?php if ($photoOk) : ?>
                <img loading="lazy" src="<?php echo $this->escape(Uri::root().$photo); ?>"
                     class="img-fluid rounded" style="max-height:260px" alt="">
            <?php endif; ?>
            <?php if ($videoSafe) : ?>
                <p><a href="<?php echo $this->escape(str_starts_with($video,'https://')?$video:Uri::root().$video); ?>"
                    target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_MINIMARKET_REVIEW_WATCH_VIDEO'); ?></a></p>
            <?php endif; ?>
        

</article>
        <?php endforeach; ?>
        <?php if ($this->reviewsCanSubmit) : ?>
            <form method="post" action="<?php echo $this->escape(RouteHelper::reviewAction()); ?>"
                  class="border rounded p-3 mt-3">
                <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_REVIEW_ADD'); ?></h3>
                <input type="hidden" name="product_id" value="<?php echo (int)$this->product->id; ?>">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="mm-review-author"><?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?> *</label>
                        <input id="mm-review-author" class="form-control" name="author_name" required minlength="2"
                               maxlength="120" value="<?php echo $this->escape((string)(\Joomla\CMS\Factory::getApplication()->getIdentity()->name??'')); ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="mm-review-rating"><?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?> *</label>
                        <select id="mm-review-rating" class="form-select" name="rating" required>
                            <?php foreach([5,4,3,2,1] as $rating) : ?><option value="<?php echo $rating; ?>"><?php echo $rating; ?> / 5</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="mm-review-title"><?php echo Text::_('COM_MINIMARKET_REVIEW_HEADLINE'); ?></label>
                        <input id="mm-review-title" class="form-control" name="title" maxlength="140">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="mm-review-body"><?php echo Text::_('COM_MINIMARKET_REVIEW_BODY'); ?> *</label>
                        <textarea id="mm-review-body" name="body" class="form-control" rows="4" minlength="10" maxlength="5000" required></textarea>
                    </div>
                </div>
                <input type="text" name="website" tabindex="-1" autocomplete="off" hidden>
                <?php echo HTMLHelper::_('form.token'); ?>
                <button class="btn btn-primary mt-3" type="submit"><?php echo Text::_('COM_MINIMARKET_REVIEW_SEND'); ?></button>
                <p class="small text-muted"><?php echo Text::_('COM_MINIMARKET_REVIEW_MODERATION_NOTE'); ?></p>
            </form>
        <?php else : ?>
            <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_REVIEWS_SIGNIN'); ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($this->relatedGroups) : ?>
        <section class="mm-related-products" aria-labelledby="mm-related-products-title">
            <h2 id="mm-related-products-title"><?php echo Text::_('COM_MINIMARKET_RELATED_PRODUCTS'); ?></h2>
            <?php foreach ($this->relatedGroups as $group) : ?>
                <section class="mb-3" aria-labelledby="mm-related-group-<?php echo (int) $group['id']; ?>">
                    <h3 id="mm-related-group-<?php echo (int) $group['id']; ?>" class="h5">
                        <?php echo $this->escape((string) $group['title']); ?>
                    </h3>
                    <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($group['products'] as $linked) :
                        $relatedImage = explode('#', (string) ($linked->image ?? ''), 2)[0];
                        $showRelatedImage = $isSafeImage($relatedImage);
                    ?>
                        <a class="d-flex align-items-center gap-2 border rounded p-2 text-decoration-none"
                           href="<?php echo $this->escape(RouteHelper::product((int) $linked->id)); ?>">
                            <?php if ($showRelatedImage) : ?>
                                <img src="<?php echo $this->escape(Uri::root() . $relatedImage); ?>"
                                     alt="" loading="lazy" width="64" height="64" style="object-fit:contain">
                            <?php endif; ?>
                            <span><?php echo $this->escape((string) $linked->title); ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php
    $recommendations=[
        ['title'=>'COM_MINIMARKET_PRODUCT_SIMILAR_TITLE','products'=>$this->similarProducts],
        ['title'=>'COM_MINIMARKET_PRODUCT_BOUGHT_TOGETHER_TITLE','products'=>$this->boughtTogetherProducts],
    ];
    foreach($recommendations as $group): if(!$group['products'])continue;
    ?>
    <section class="mm-product-recommendations my-4">
        <h2 class="h4"><?php echo Text::_($group['title']); ?></h2>
        <ul class="row row-cols-2 row-cols-md-4 g-3 list-unstyled">
        <?php foreach($group['products'] as $suggestion):
            $thumb=explode('#',(string)($suggestion->image??''),2)[0];
            $safeThumb=$isSafeImage($thumb);
        ?>
            <li class="col">
                <a class="d-flex flex-column h-100 border rounded p-3 text-decoration-none"
                   href="<?php echo $this->escape(RouteHelper::product((int)$suggestion->id)); ?>">
                    <?php if($safeThumb): ?>
                    <img class="img-fluid mb-2" loading="lazy" style="max-height:140px;object-fit:contain"
                         src="<?php echo $this->escape(Uri::root().$thumb); ?>"
                         alt="<?php echo $this->escape((string)($suggestion->image_alt ?: $suggestion->title)); ?>">
                    <?php endif; ?>
                    <span><?php echo $this->escape((string)$suggestion->title); ?></span>
                    <strong class="mt-auto"><?php echo $this->escape((string)$suggestion->price); ?>
                        <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
    <p class="mm-cart__nav"><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></a></p>
    <?php if ($this->outOfStock) : ?>
        <p class="alert alert-secondary" role="status"><?php echo Text::_('COM_MINIMARKET_OUT_OF_STOCK'); ?></p>
        <?php if ($this->alternatives) : ?>
            <section class="mm-alternatives">
                <h2><?php echo Text::_('COM_MINIMARKET_ALTERNATIVES'); ?></h2>
                <ul class="list-unstyled d-flex flex-wrap gap-3">
                    <?php foreach ($this->alternatives as $alternative) :
                        $thumb=explode('#',(string)($alternative->image??''),2)[0];
                        $safeThumb=str_starts_with($thumb,'images/')
                            && !str_contains($thumb,'..') && !str_contains($thumb,'//');
                    ?>
                        <li class="border rounded p-2" style="max-width:160px">
                            <a href="<?php echo $this->escape(RouteHelper::product((int)$alternative->id)); ?>">
                                <?php if ($safeThumb) : ?>
                                    <img src="<?php echo $this->escape(Uri::root().$thumb); ?>"
                                         alt="" loading="lazy" style="width:100%;max-height:110px;object-fit:contain">
                                <?php endif; ?>
                                <span><?php echo $this->escape($alternative->title); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($this->showStickyBuyBar && $canOrder): ?>
    <aside class="mm-product-sticky-bar shadow-sm" data-mm-product-sticky hidden
           aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_STICKY_BUY')); ?>">
        <div class="container-fluid d-flex align-items-center gap-3 py-2">
            <?php if($main): ?>
                <img data-mm-sticky-image src="<?php echo $this->escape($main['url']); ?>"
                     alt="" width="52" height="52" class="mm-product-sticky-bar__image">
            <?php endif; ?>
            <div class="mm-product-sticky-bar__name flex-grow-1">
                <strong><?php echo $this->escape((string)$this->product->title); ?></strong>
                <span class="d-block"><?php echo $this->escape((string)$this->product->price); ?>
                    <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></span>
            </div>
            <label class="d-flex align-items-center gap-2 mb-0">
                <span class="visually-hidden"><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></span>
                <input type="number" class="form-control form-control-sm mm-product-sticky-bar__quantity"
                       data-mm-sticky-quantity min="1" max="99" value="1" step="1">
            </label>
            <button class="btn btn-primary" type="button" data-mm-sticky-buy
                    <?php echo $initialCanBuy?'':'disabled'; ?>>
                <?php echo Text::_('COM_MINIMARKET_CART_ADD'); ?>
            </button>
        </div>
    </aside>
    <?php endif; ?>
</article>
