<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartState;

$firstDelivery=array_key_first($this->deliveryMethods);
$firstPayment=array_key_first($this->paymentMethods);
?>
<section class="mm-cart mm-checkout">
    <h1><?php echo Text::_('COM_MINIMARKET_CHECKOUT_TITLE'); ?></h1>
    <?php if (!$this->enabled || !$this->cart['lines']) : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CHECKOUT_UNAVAILABLE'); ?></p>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></a></p>
    <?php elseif (!$this->cart['available']) : ?>
        <div class="alert alert-warning" role="alert"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_CART_INVALID'); ?></div>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a></p>
    <?php elseif ($this->blockedMessage!=='') : ?>
        <div class="alert alert-warning" role="alert"><?php echo $this->escape($this->blockedMessage); ?></div>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a></p>
    <?php else : ?>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_NOTICE'); ?></p>
        <form action="<?php echo $this->escape(RouteHelper::checkoutAction()); ?>" method="post"
              class="mm-checkout-form row g-4" autocomplete="on"
              data-items-cents="<?php echo (int)$this->cart['total_cents']; ?>">
            <div class="col-12 col-lg-8">
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-buyer">
                    <h2 id="mm-checkout-buyer" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BUYER'); ?></h2>
                    <div class="row g-3">
                        <?php if (($this->fields['name']??'hidden')!=='hidden') : ?>
                        <div class="col-12 col-md-6">
                            <label for="mm-customer-name" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_NAME'); ?>
                                <?php echo $this->fields['name']==='required'?' *':''; ?></label>
                            <input id="mm-customer-name" type="text" name="customer_name" class="form-control"
                                   maxlength="120" minlength="2" autocomplete="name"
                                   <?php echo $this->fields['name']==='required'?'required':''; ?>
                                   value="<?php echo $this->escape($this->customerName); ?>">
                        </div>
                        <?php endif; ?>
                        <?php if (($this->fields['phone']??'hidden')!=='hidden') : ?>
                        <div class="col-12 col-md-6">
                            <label for="mm-customer-phone" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PHONE'); ?>
                                <?php echo $this->fields['phone']==='required'?' *':''; ?></label>
                            <input id="mm-customer-phone" type="tel" name="phone" class="form-control"
                                   maxlength="32" autocomplete="tel"
                                   <?php echo $this->fields['phone']==='required'?'required':''; ?>>
                        </div>
                        <?php endif; ?>
                        <?php if (($this->fields['email']??'hidden')!=='hidden') : ?>
                        <div class="col-12 col-md-6">
                            <label for="mm-customer-email" class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_EMAIL'); ?>
                                <?php echo $this->fields['email']==='required'?' *':''; ?></label>
                            <input id="mm-customer-email" type="email" name="email" class="form-control"
                                   maxlength="254" autocomplete="email"
                                   <?php echo $this->fields['email']==='required'?'required':''; ?>
                                   value="<?php echo $this->escape($this->customerEmail); ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-delivery">
                    <h2 id="mm-checkout-delivery" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_RECEIPT'); ?></h2>
                    <div class="d-flex flex-wrap gap-3" role="group" aria-label="<?php echo Text::_('COM_MINIMARKET_CHECKOUT_RECEIPT'); ?>">
                        <?php foreach ($this->deliveryMethods as $method=>$settings) : ?>
                        <div class="form-check mm-checkout-option">
                            <input class="form-check-input" type="radio" name="delivery_method" id="mm-delivery-<?php echo $method; ?>"
                                   value="<?php echo $method; ?>" <?php echo $method===$firstDelivery?'checked':''; ?> required>
                            <label class="form-check-label" for="mm-delivery-<?php echo $method; ?>">
                                <?php echo Text::_('COM_MINIMARKET_DELIVERY_'.strtoupper($method)); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($this->deliveryMethods['pickup'])) : ?>
                    <div class="mm-delivery-details mt-3" data-delivery-panel="pickup">
                        <p class="mb-1"><?php echo Text::_('COM_MINIMARKET_DELIVERY_PICKUP_FREE'); ?></p>
                        <?php if ($this->pickupAddress!=='') : ?>
                            <p class="text-muted mb-0"><?php echo $this->escape($this->pickupAddress); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($this->deliveryMethods['courier'])) : ?>
                    <div class="mm-delivery-details mt-3" data-delivery-panel="courier">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="mm-delivery-city" class="form-label"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CITY'); ?> *</label>
                                <select id="mm-delivery-city" name="delivery_city" class="form-select">
                                    <option value=""><?php echo Text::_('COM_MINIMARKET_DELIVERY_CHOOSE_CITY'); ?></option>
                                    <?php foreach ($this->cityRates as $rate) : ?>
                                        <option value="<?php echo $this->escape($rate['city']); ?>"
                                                data-fee-cents="<?php echo (int)$rate['fee_cents']; ?>"
                                                data-free-from-cents="<?php echo (int)$rate['free_from_cents']; ?>">
                                            <?php echo $this->escape($rate['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="mm-delivery-address" class="form-label"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ADDRESS'); ?> *</label>
                                <textarea id="mm-delivery-address" name="delivery_address" class="form-control"
                                          minlength="5" maxlength="512" rows="2"
                                          placeholder="<?php echo $this->escape(Text::_('COM_MINIMARKET_DELIVERY_ADDRESS_HINT')); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($this->cdekPending) : ?>
                        <p class="small text-muted mt-2 mb-0"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CDEK_PENDING'); ?></p>
                    <?php endif; ?>
                </section>

                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-payment">
                    <h2 id="mm-checkout-payment" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PAYMENT'); ?></h2>
                    <?php foreach ($this->paymentMethods as $method=>$label) : ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="mm-payment-<?php echo $method; ?>"
                               value="<?php echo $method; ?>" <?php echo $method===$firstPayment?'checked':''; ?> required>
                        <label class="form-check-label" for="mm-payment-<?php echo $method; ?>"><?php echo Text::_($label); ?></label>
                    </div>
                    <?php endforeach; ?>
                    <p class="small text-muted"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PAYMENT_MANUAL'); ?></p>
                </section>
                <?php if (($this->fields['comment']??'hidden')!=='hidden') : ?>
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-note">
                    <h2 id="mm-checkout-note" class="h5"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_COMMENT'); ?>
                        <?php echo $this->fields['comment']==='required'?' *':''; ?></h2>
                    <textarea id="mm-customer-comment" name="comment" class="form-control" maxlength="2000" rows="3"
                              <?php echo $this->fields['comment']==='required'?'required':''; ?>></textarea>
                </section>
                <?php endif; ?>
            </div>
            <div class="col-12 col-lg-4">
                <section class="mm-checkout-summary" aria-label="<?php echo Text::_('COM_MINIMARKET_CHECKOUT_SUMMARY'); ?>">
                    <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_SUMMARY'); ?></h2>
                    <ul class="list-unstyled">
                        <?php foreach ($this->cart['lines'] as $line) : ?>
                            <li class="d-flex justify-content-between border-bottom py-2 gap-2">
                                <span><?php echo $this->escape($line['title']); ?> × <?php echo (int)$line['quantity']; ?></span>
                                <strong class="text-nowrap"><?php echo CartState::format($line['line_cents']); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="mm-checkout-totals">
                        <dt><?php echo Text::_('COM_MINIMARKET_CHECKOUT_ITEMS_TOTAL'); ?></dt>
                        <dd><?php echo CartState::format($this->cart['total_cents']); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></dd>
                        <dt><?php echo Text::_('COM_MINIMARKET_CHECKOUT_DELIVERY_TOTAL'); ?></dt>
                        <dd data-shipping-total aria-live="polite">0 <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></dd>
                        <dt><strong><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?></strong></dt>
                        <dd><strong data-order-total aria-live="polite"><?php echo CartState::format($this->cart['total_cents']); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong></dd>
                    </dl>
                    <div style="position:absolute;left:-9999px" aria-hidden="true">
                        <label for="mm-customer-website">Website</label>
                        <input id="mm-customer-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="checkout_key" value="<?php echo $this->escape($this->checkoutKey); ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn btn-primary w-100"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_SUBMIT'); ?></button>
                    <a class="btn btn-outline-secondary mt-2 w-100" href="<?php echo $this->escape(RouteHelper::cart()); ?>">
                        <?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a>
                    <p class="small text-muted mt-3 mb-0"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_FINAL_SERVER'); ?></p>
                </section>
            </div>
        </form>
    <?php endif; ?>
</section>
