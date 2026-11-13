<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
?>
<section class="mm-cart" data-mm-cart-page>
    <h1><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></h1>
    <p><a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_CONTINUE'); ?></a></p>
    <?php if (!$this->cart['lines']) : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CART_EMPTY'); ?></p>
    <?php else : ?>
        <?php if (!$this->cart['available']) : ?>
            <div class="alert alert-warning" role="alert"><?php echo Text::_(
                $this->cart['error'] ?: 'COM_MINIMARKET_CART_NOT_AVAILABLE'); ?></div>
        <?php endif; ?>
        <p class="alert alert-warning" role="status" data-mm-cart-feedback hidden></p>
        <p data-mm-cart-empty hidden><?php echo Text::_('COM_MINIMARKET_CART_EMPTY'); ?></p>
        <ul class="mm-cart__lines list-unstyled" data-mm-cart-lines>
        <?php foreach ($this->cart['lines'] as $line) : ?>
            <li class="mm-cart__line" data-mm-cart-line data-mm-cart-key="<?php echo (int)$line['product_id']; ?>:<?php echo (int)$line['variant_id']; ?>">
                <?php if ($line['image']) : ?>
                    <img src="<?php echo $this->escape(Uri::root().$line['image']); ?>" alt=""
                         width="96" height="96" loading="lazy">
                <?php endif; ?>
                <div class="mm-cart__info">
                    <?php if ($line['valid']) : ?>
                        <a href="<?php echo $this->escape(RouteHelper::product((int)$line['product_id'])); ?>">
                            <?php echo $this->escape($line['title']); ?></a>
                        <?php if ($line['sku'] !== '') : ?><div class="small"><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?>:
                            <?php echo $this->escape($line['sku']); ?></div><?php endif; ?>
                        <div><?php echo Text::_('COM_MINIMARKET_CART_UNIT_PRICE'); ?>:
                            <?php echo CartState::format($line['unit_cents']); ?>
                            <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></div>
                    <?php else : ?>
                        <strong><?php echo Text::_('COM_MINIMARKET_CART_NOT_AVAILABLE'); ?></strong>
                    <?php endif; ?>
                    <div class="mm-cart__controls">
                        <form action="<?php echo $this->escape(RouteHelper::cartAction('update')); ?>" method="post" data-mm-cart-quantity-form>
                            <input type="hidden" name="product_id" value="<?php echo (int)$line['product_id']; ?>">
                            <input type="hidden" name="variant_id" value="<?php echo (int)$line['variant_id']; ?>">
                            <label><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?>
                                <input class="form-control" name="quantity" type="number"
                                    min="1" max="<?php echo max(1,(int)$line['max_quantity']); ?>"
                                    step="1" value="<?php echo (int)$line['quantity']; ?>" required
                                    data-mm-cart-quantity>
                            </label>
                            <?php echo HTMLHelper::_('form.token'); ?>
                            <noscript><button class="btn btn-outline-primary" type="submit"><?php echo Text::_('COM_MINIMARKET_CART_UPDATE'); ?></button></noscript>
                        </form>
                        <form action="<?php echo $this->escape(RouteHelper::cartAction('remove')); ?>" method="post" data-mm-cart-remove-form>
                            <input type="hidden" name="product_id" value="<?php echo (int)$line['product_id']; ?>">
                            <input type="hidden" name="variant_id" value="<?php echo (int)$line['variant_id']; ?>">
                            <?php echo HTMLHelper::_('form.token'); ?>
                            <button class="btn btn-outline-danger" type="submit"><?php echo Text::_('COM_MINIMARKET_CART_REMOVE'); ?></button>
                        </form>
                    </div>
                </div>
                <strong data-mm-line-total><?php echo CartState::format($line['line_cents']); ?>
                    <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong>
            </li>
        <?php endforeach; ?>
        </ul>
        <p class="mm-cart__total"><strong><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?>:
            <span data-mm-cart-units class="visually-hidden"><?php echo (int)$this->cart['units']; ?></span>
            <span data-mm-cart-total><?php echo CartState::format($this->cart['total_cents']); ?>
            <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></span></strong></p>
        <form class="mm-cart-promo my-3" action="<?php echo $this->escape(RouteHelper::cartAction('promo')); ?>" method="post">
            <label for="mm-cart-promo" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_CODE'); ?></label>
            <div class="input-group">
                <input id="mm-cart-promo" class="form-control" name="promo_code"
                    pattern="[A-Za-z0-9-]{3,64}" maxlength="64"
                    value="<?php echo $this->escape($this->promoCode); ?>">
                <button class="btn btn-primary" type="submit"><?php echo Text::_('COM_MINIMARKET_PROMO_APPLY'); ?></button>
            </div>
            <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PROMO_CART_NOTE'); ?></p>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
        <?php if($this->promoPreview!==null||$this->autoPreview!==null):
            $autoCents=(int)($this->autoPreview['discount_cents']??0);
            $promoCents=(int)($this->promoPreview['discount_cents']??0);
            $discount=max($autoCents,$promoCents);
            $estimated=max(0,(int)$this->cart['total_cents']-$discount);
        ?>
        <div data-mm-cart-promo-preview class="mm-cart-promo-preview my-3">
            <p class="mb-1"><?php echo Text::_($autoCents>$promoCents
                 ?'COM_MINIMARKET_AUTO_DISCOUNT_BEST_APPLIED':'COM_MINIMARKET_PROMO_DISCOUNT'); ?>:
                <strong>−<?php echo CartState::format($discount); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong></p>
            <p class="mb-1"><?php echo Text::_('COM_MINIMARKET_PROMO_ESTIMATED_TOTAL'); ?>:
                <strong><?php echo CartState::format($estimated); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong></p>
            <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PROMO_ESTIMATED_NOTE'); ?></p>
        </div>
        <?php elseif($this->promoMessage!==''): ?>
          <p class="alert alert-warning" role="status"><?php echo $this->escape($this->promoMessage); ?></p>
        <?php endif; ?>
        <?php if ($this->cart['available']) : ?>
        <p data-mm-cart-checkout><a class="btn btn-primary" href="<?php echo $this->escape(RouteHelper::checkout()); ?>">
            <?php echo Text::_('COM_MINIMARKET_CHECKOUT_TITLE'); ?></a></p>
        <?php endif; ?>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_CART_CHECKOUT_NEXT'); ?></p>
    <?php endif; ?>
</section>
