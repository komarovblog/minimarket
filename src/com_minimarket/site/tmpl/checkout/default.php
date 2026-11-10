<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
?>
<section class="mm-cart mm-checkout">
    <h1><?php echo Text::_('COM_MINIMARKET_CHECKOUT_TITLE'); ?></h1>
    <?php if (!$this->enabled || !$this->cart['lines']) : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CHECKOUT_UNAVAILABLE'); ?></p>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></a></p>
    <?php elseif (!$this->cart['available']) : ?>
        <div class="alert alert-warning" role="alert"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_CART_INVALID'); ?></div>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a></p>
    <?php elseif (!$this->authenticated) : ?>
        <div class="alert alert-info" role="status"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_LOGIN_REQUIRED'); ?></div>
        <p><?php echo Text::_('COM_MINIMARKET_CHECKOUT_LOGIN_PENDING'); ?></p>
        <a class="btn btn-outline-secondary" href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a>
    <?php else : ?>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_NOTICE'); ?></p>
        <ul class="list-unstyled">
            <?php foreach ($this->cart['lines'] as $line) : ?>
            <li class="d-flex justify-content-between border-bottom py-2">
                <span><?php echo $this->escape($line['title']); ?> × <?php echo (int)$line['quantity']; ?></span>
                <strong><?php echo CartState::format($line['line_cents']); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong>
            </li>
            <?php endforeach; ?>
        </ul>
        <p><strong><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?>:
            <?php echo CartState::format($this->cart['total_cents']); ?>
            <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong></p>
        <form action="<?php echo $this->escape(RouteHelper::checkoutAction()); ?>" method="post" class="mm-checkout-form" autocomplete="on">
            <div class="mb-3">
                <label for="mm-customer-name" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_NAME'); ?> *</label>
                <input id="mm-customer-name" type="text" name="customer_name" class="form-control"
                       required maxlength="120" minlength="2" autocomplete="name"
                       value="<?php echo $this->escape($this->customerName); ?>">
            </div>
            <div class="mb-3">
                <label for="mm-customer-phone" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PHONE'); ?> *</label>
                <input id="mm-customer-phone" type="tel" name="phone" class="form-control"
                       required maxlength="32" autocomplete="tel">
            </div>
            <div class="mb-3">
                <label for="mm-customer-email" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_EMAIL'); ?></label>
                <input id="mm-customer-email" type="email" name="email" class="form-control"
                       maxlength="254" autocomplete="email"
                       value="<?php echo $this->escape($this->customerEmail); ?>">
            </div>
            <div class="mb-3">
                <label for="mm-customer-comment" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_COMMENT'); ?></label>
                <textarea id="mm-customer-comment" name="comment" class="form-control" maxlength="2000" rows="3"></textarea>
            </div>
            <div style="position:absolute;left:-9999px" aria-hidden="true">
                <label for="mm-customer-website">Website</label><input id="mm-customer-website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <input type="hidden" name="checkout_key" value="<?php echo $this->escape($this->checkoutKey); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_SUBMIT'); ?></button>
            <a class="btn btn-outline-secondary" href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a>
        </form>
    <?php endif; ?>
</section>
