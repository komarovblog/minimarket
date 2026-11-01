<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;

// Primary cover remains products.image, so old products retain their image.
$images = [];
$cover = explode('#', (string) ($this->product->image ?? ''), 2)[0];
$isSafeImage = static function (string $path): bool {
    return str_starts_with($path, 'images/') && !str_contains($path, '..')
        && !preg_match('/[\x00-\x1f<>?#\\\\]/', $path);
};
if ($isSafeImage($cover)) {
    $images[$cover] = [
        'url' => Uri::root() . $cover,
        'alt' => (string) ($this->product->image_alt ?: $this->product->title),
    ];
}
foreach ($this->gallery as $photo) {
    $path = explode('#', (string) ($photo->path ?? ''), 2)[0];
    if ($isSafeImage($path) && !isset($images[$path])) {
        $images[$path] = [
            'url' => Uri::root() . $path,
            'alt' => (string) ($photo->alt ?: $this->product->title),
        ];
    }
}
$images = array_values($images);
$main = $images[0] ?? null;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
    <span> / </span>
    <a href="<?php echo $this->escape(RouteHelper::category((int) $this->product->category_id)); ?>"><?php echo $this->escape($this->product->category_title); ?></a>
</nav>
<article class="com-minimarket-product">
    <h1><?php echo $this->escape($this->product->title); ?></h1>
    <?php if ($main !== null) : ?>
        <div class="mm-product-gallery" data-mm-product-gallery>
            <a class="mm-product-gallery__main-link" data-mm-gallery-main-link
               href="<?php echo $this->escape($main['url']); ?>" target="_blank" rel="noopener"
               aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_GALLERY_OPEN_ORIGINAL')); ?>">
                <img class="mm-product-gallery__main" data-mm-gallery-main
                     src="<?php echo $this->escape($main['url']); ?>"
                     alt="<?php echo $this->escape($main['alt']); ?>">
            </a>
            <?php if (count($images) > 1) : ?>
                <div class="mm-product-gallery__thumbs" role="group"
                     aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_GALLERY_IMAGES')); ?>">
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
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (trim((string) ($this->product->introtext ?? '')) !== '') : ?>
        <div class="com-minimarket-product__intro">
            <?php echo InputFilter::getInstance()->clean((string) $this->product->introtext, 'HTML'); ?>
        </div>
    <?php endif; ?>
    <dl>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></dt>
        <dd><?php echo $this->escape($this->product->sku); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></dt>
        <dd><?php echo $this->escape((string) $this->product->price); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></dt>
        <dd><?php echo (int) $this->product->stock; ?></dd>
    </dl>
    <?php if (trim((string) ($this->product->description ?? '')) !== '') : ?>
        <div class="com-minimarket-product__description">
            <?php echo InputFilter::getInstance()->clean((string) $this->product->description, 'HTML'); ?>
        </div>
    <?php endif; ?>
    <p><?php echo Text::_('COM_MINIMARKET_CART_NOT_READY'); ?></p>
</article>
