<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\CartState;

$firstDelivery=array_key_first($this->deliveryMethods);
$firstPayment=array_key_first($this->paymentMethods);
$mmMethodParams=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
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
        <?php if ((int)\Joomla\CMS\Factory::getApplication()->getIdentity()->id<1): ?>
          <p class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_users&view=login',false)); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_JOOMLA_LOGIN'); ?></a>
            <?php if((int)\Joomla\CMS\Component\ComponentHelper::getParams('com_users')->get('allowUserRegistration',0)===1): ?>
              <a class="btn btn-outline-primary" href="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_users&view=registration',false)); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_JOOMLA_REGISTER'); ?></a>
            <?php endif; ?>
          </p>
        <?php endif; ?>
        <p><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BACK'); ?></a></p>
    <?php else : ?>
        <?php if($this->showLoginButton && (int)\Joomla\CMS\Factory::getApplication()->getIdentity()->id<1): ?>
            <p><a class="btn btn-outline-primary" href="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_users&view=login',false)); ?>"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_JOOMLA_LOGIN'); ?></a></p>
        <?php endif; ?>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_NOTICE'); ?></p>
        <?php if($this->needPurchaseVerification): ?>
        <form method="post" class="mb-4" action="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_minimarket&task=checkout.sendCode',false)); ?>">
            <div class="alert alert-info">
                <label class="form-label"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PURCHASE_VERIFICATION'); ?></label>
                <p><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PURCHASE_CODE_HELP'); ?>
                    <strong><?php echo $this->escape($this->verificationEmail); ?></strong></p>
                <input type="hidden" name="checkout_key" value="<?php echo $this->escape($this->checkoutKey); ?>">
                <input type="hidden" name="verification_scope" value="purchase">
                <?php echo HTMLHelper::_('form.token'); ?>
                <button type="submit" class="btn btn-outline-primary"><?php echo Text::_('COM_MINIMARKET_OTP_SEND'); ?></button>
            </div>
        </form>
        <?php endif; ?>
        <form action="<?php echo $this->escape(RouteHelper::checkoutAction()); ?>" method="post"
              class="mm-checkout-form row g-4" autocomplete="on"
              data-items-cents="<?php echo (int)$this->cart['total_cents']; ?>"
              data-promo-preview-url="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_minimarket&task=checkout.preview',false)); ?>"
              data-otp-url="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_minimarket&task=checkout.sendCode',false)); ?>">
            <div class="col-12 col-lg-8">
                <?php if($this->skipBuyer): ?>
                    <input type="hidden" name="customer_name" value="<?php echo $this->escape($this->customerName); ?>">
                    <input type="hidden" name="email" value="<?php echo $this->escape($this->customerEmail); ?>">
                <?php else: ?>
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-buyer">
                    <h2 id="mm-checkout-buyer" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_BUYER'); ?></h2>
                    <?php if($this->buyerDescription!==''): ?><p class="text-muted"><?php echo InputFilter::getInstance()->clean($this->buyerDescription, 'HTML'); ?></p><?php endif; ?>
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
                                   maxlength="32" autocomplete="tel" <?php if($this->phoneMask!==''): ?>data-mm-input-mask="<?php echo $this->escape($this->phoneMask); ?>"<?php endif; ?>
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
                    
                        <?php foreach($this->customFields as $field):
                            $key=$field['code'];
                            $id='mm-extra-'.$key;
                            $req=$field['required']?'required':'';
                        ?>
                        <div class="col-12 <?php echo $field['type']==='textarea'?'':'col-md-6'; ?>">
                            <label class="form-label" for="<?php echo $this->escape($id); ?>"><?php echo $this->escape($field['title']); ?><?php echo $field['required']?' *':''; ?></label>
                            <?php if($field['type']==='textarea'): ?>
                                <textarea id="<?php echo $this->escape($id); ?>" name="extra[<?php echo $this->escape($key); ?>]" class="form-control" maxlength="2000" rows="3" <?php echo $req; ?> placeholder="<?php echo $this->escape($field['placeholder']); ?>"></textarea>
                            <?php elseif($field['type']==='select'): ?>
                                <select id="<?php echo $this->escape($id); ?>" name="extra[<?php echo $this->escape($key); ?>]" class="form-select" <?php echo $req; ?>>
                                    <option value=""></option>
                                    <?php foreach($field['choices'] as $choice): ?><option value="<?php echo $this->escape($choice); ?>"><?php echo $this->escape($choice); ?></option><?php endforeach; ?>
                                </select>
                            <?php elseif($field['type']==='checkbox'): ?>
                                <div class="form-check">
                                    <input id="<?php echo $this->escape($id); ?>" type="checkbox" value="1" name="extra[<?php echo $this->escape($key); ?>]" class="form-check-input" <?php echo $req; ?>>
                                </div>
                            <?php else: ?>
                                <input id="<?php echo $this->escape($id); ?>" type="<?php echo $this->escape($field['type']); ?>" name="extra[<?php echo $this->escape($key); ?>]" class="form-control" maxlength="2000" <?php echo $req; ?>
                                    <?php if($field['mask']!==''): ?>data-mm-input-mask="<?php echo $this->escape($field['mask']); ?>"<?php endif; ?>
                                    placeholder="<?php echo $this->escape($field['placeholder']); ?>">
                            <?php endif; ?>
                            <?php if($field['description']!==''): ?><div class="form-text"><?php echo $this->escape($field['description']); ?></div><?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </section>
                <?php endif; ?>

                <?php if($this->skipDelivery): ?>
                    <?php $automaticDelivery=array_key_first($this->deliveryMethods); ?>
                    <input type="hidden" name="delivery_method"
                           value="<?php echo $automaticDelivery??($this->needsPhysical?'pending':'digital'); ?>"
                           data-fee-cents="<?php echo $automaticDelivery==='pickup'
                               ?(int)($this->deliveryMethods['pickup']['fee_cents']??0):0; ?>">
                    <?php if($this->needsPhysical && $automaticDelivery===null): ?>
                        <p class="form-text"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_DELIVERY_PENDING_NOTICE'); ?></p>
                    <?php endif; ?>
                    <?php if(!$this->needsPhysical): ?>
                        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_DIGITAL_NO_SHIPPING'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-delivery">
                    <h2 id="mm-checkout-delivery" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_RECEIPT'); ?></h2>
                    <?php if($this->deliveryDescription!==''): ?><p class="text-muted"><?php echo InputFilter::getInstance()->clean($this->deliveryDescription, 'HTML'); ?></p><?php endif; ?>
                    <div class="d-flex flex-wrap gap-3" role="group" aria-label="<?php echo Text::_('COM_MINIMARKET_CHECKOUT_RECEIPT'); ?>">
                        <?php foreach ($this->deliveryMethods as $method=>$settings) : ?>
                        <div class="form-check mm-checkout-option" data-mm-delivery-choice>
                            <input class="form-check-input" type="radio" name="delivery_method" id="mm-delivery-<?php echo $method; ?>"
                                   value="<?php echo $method; ?>" data-fee-cents="<?php echo (int)($settings['fee_cents']??0); ?>"
                                   <?php echo $method===$firstDelivery?'checked':''; ?> required>
                            <label class="form-check-label" for="mm-delivery-<?php echo $method; ?>">
                                <?php echo Text::_('COM_MINIMARKET_DELIVERY_'.strtoupper($method)); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($this->deliveryMethods['pickup'])) : ?>
                    <div class="mm-delivery-details mt-3" data-delivery-panel="pickup">
                        <p class="mb-1"><?php echo (int)($this->deliveryMethods['pickup']['fee_cents']??0)>0
                         ?Text::_('COM_MINIMARKET_PICKUP_FEE').': '.\MiniMarket\Component\Minimarket\Site\Service\CartState::format((int)$this->deliveryMethods['pickup']['fee_cents']).' '.Text::_('COM_MINIMARKET_CURRENCY_SYMBOL')
                         :Text::_('COM_MINIMARKET_DELIVERY_PICKUP_FREE'); ?></p>
                        <?php if ($this->pickupAddress!=='') : ?>
                            <p class="text-muted mb-0"><?php echo $this->escape($this->pickupAddress); ?></p>
                        <?php endif; ?>
                        <?php if($this->pickupSchedule!==''): ?>
                         <p class="text-muted mb-0"><?php echo $this->escape($this->pickupSchedule); ?></p>
                        <?php endif; ?>
                        <?php if($this->pickupMapUrl!=='' && filter_var($this->pickupMapUrl,FILTER_VALIDATE_URL)
                          && strtolower((string)parse_url($this->pickupMapUrl,PHP_URL_SCHEME))==='https'
                          && parse_url($this->pickupMapUrl,PHP_URL_USER)===null
                          && parse_url($this->pickupMapUrl,PHP_URL_PASS)===null): ?>
                         <a target="_blank" rel="noopener noreferrer" href="<?php echo $this->escape($this->pickupMapUrl); ?>">
                           <?php echo Text::_('COM_MINIMARKET_PICKUP_SHOW_MAP'); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($this->deliveryMethods['courier'])) : ?>
                    <div class="mm-delivery-details mt-3" data-delivery-panel="courier">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="mm-delivery-city" class="form-label"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CITY'); ?> *</label>
                                <?php if($this->zonesMode): ?>
                                <input id="mm-delivery-city" name="delivery_city" class="form-control"
                                       list="mm-zone-city-list" maxlength="120" autocomplete="address-level2" required>
                                <datalist id="mm-zone-city-list">
                                 <?php foreach($this->zoneCities as $name): ?><option value="<?php echo $this->escape($name); ?>"></option><?php endforeach; ?>
                                </datalist>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </div>
                            <?php if($this->zonesMode): ?>
                            <div class="col-12 col-md-6">
                             <label for="mm-delivery-country" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_COUNTRY'); ?></label>
                             <input id="mm-delivery-country" name="delivery_country" class="form-control"
                                    maxlength="2" pattern="[A-Za-z]{2}" value="RU" required>
                            </div>
                            <div class="col-12 col-md-6">
                             <label for="mm-delivery-region" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_REGION'); ?></label>
                             <input id="mm-delivery-region" name="delivery_region" class="form-control" maxlength="120"
                                    autocomplete="address-level1">
                            </div>
                            <div class="col-12 col-md-6">
                             <label for="mm-delivery-postcode" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_POSTCODE'); ?></label>
                             <input id="mm-delivery-postcode" name="delivery_postcode" class="form-control"
                                    maxlength="16" autocomplete="postal-code">
                            </div>
                            <?php endif; ?>
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
                <?php endif; ?>

                <?php if($this->showDigitalStep): ?>
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-digital">
                    <h2 id="mm-checkout-digital" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_DIGITAL_STEP'); ?></h2>
                    <?php if($this->digitalDescription!==''): ?>
                        <div class="mb-2"><?php echo InputFilter::getInstance()->clean($this->digitalDescription,'HTML'); ?></div>
                    <?php endif; ?>
                    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_RECIPIENT_HELP'); ?></p>
                    <label class="form-label" for="mm-digital-recipient"><?php echo Text::_('COM_MINIMARKET_DIGITAL_RECIPIENT'); ?></label>
                    <input class="form-control" type="email" maxlength="254"
                           id="mm-digital-recipient" name="digital_recipient_email"
                           value="<?php echo $this->escape($this->digitalRecipientEmail); ?>"
                           autocomplete="email" placeholder="<?php echo Text::_('COM_MINIMARKET_DIGITAL_USE_BUYER_EMAIL'); ?>">
                    <?php if((int)\Joomla\CMS\Factory::getApplication()->getIdentity()->id>0): ?>
                    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_ALTERNATE_HELP'); ?></p>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="mm-recipient-code"><?php echo Text::_('COM_MINIMARKET_OTP_RECIPIENT_CODE'); ?></label>
                            <input class="form-control" id="mm-recipient-code" name="recipient_code"
                                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                        </div>
                        <div class="col-12 col-sm-6 align-self-end">
                            <button type="button" class="btn btn-outline-secondary"
                                    data-mm-otp-send="recipient"><?php echo Text::_('COM_MINIMARKET_OTP_SEND_RECIPIENT'); ?></button>
                        </div>
                    </div>
                    <div class="form-text" data-mm-otp-status role="status" aria-live="polite"></div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if($this->skipPayment): ?>
                    <input type="hidden" name="payment_method"
                           data-allowed-deliveries="<?php echo $this->escape(implode(',',\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::allowedForPayment($mmMethodParams,(string)($firstPayment??'pending')))); ?>"
                           value="<?php echo $this->escape((string)($firstPayment??'pending')); ?>">
                    <?php if($firstPayment===null): ?>
                        <p class="form-text"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PAYMENT_PENDING_NOTICE'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                <section class="mm-checkout-section mb-4" aria-labelledby="mm-checkout-payment">
                    <h2 id="mm-checkout-payment" class="h4"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PAYMENT'); ?></h2>
                    <?php if($this->paymentDescription!==''): ?><p class="text-muted"><?php echo InputFilter::getInstance()->clean($this->paymentDescription, 'HTML'); ?></p><?php endif; ?>
                    <?php foreach ($this->paymentMethods as $method=>$label) : ?>
                    <div class="form-check mb-2" data-mm-payment-choice>
                        <input class="form-check-input" type="radio" name="payment_method" id="mm-payment-<?php echo $method; ?>"
                               data-allowed-deliveries="<?php echo $this->escape(implode(',',\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::allowedForPayment($mmMethodParams,$method))); ?>"
                               value="<?php echo $method; ?>" <?php echo $method===$firstPayment?'checked':''; ?> required>
                        <label class="form-check-label" for="mm-payment-<?php echo $method; ?>"><?php echo Text::_($label); ?></label>
                    </div>
                    <?php endforeach; ?>
                    <p class="small text-muted"><?php echo Text::_('COM_MINIMARKET_CHECKOUT_PAYMENT_MANUAL'); ?></p>
                    <p class="text-danger small" data-mm-compatibility-error hidden><?php echo Text::_('COM_MINIMARKET_PAYMENT_INCOMPATIBLE'); ?></p>
                </section>
                <?php endif; ?>
                <?php if (!$this->skipBuyer && ($this->fields['comment']??'hidden')!=='hidden') : ?>
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
                        <dt><?php echo Text::_('COM_MINIMARKET_PROMO_DISCOUNT'); ?></dt>
                        <dd data-promo-discount aria-live="polite">0 <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></dd>
                        <dt><?php echo Text::_('COM_MINIMARKET_CHECKOUT_DELIVERY_TOTAL'); ?></dt>
                        <dd data-shipping-total aria-live="polite">0 <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></dd>
                        <dt><strong><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?></strong></dt>
                        <dd><strong data-order-total aria-live="polite"><?php echo CartState::format($this->cart['total_cents']); ?> <?php echo Text::_('COM_MINIMARKET_CURRENCY_SYMBOL'); ?></strong></dd>
                    </dl>
                    <div class="mb-3">
                      <label class="form-label" for="mm-checkout-promo"><?php echo Text::_('COM_MINIMARKET_PROMO_CODE'); ?></label>
                      <input id="mm-checkout-promo" name="promo_code" class="form-control" maxlength="64"
                             pattern="[A-Za-z0-9-]{3,64}" value="<?php echo $this->escape($this->promoCode); ?>">
                      <p class="form-text" data-promo-feedback role="status"><?php echo Text::_('COM_MINIMARKET_PROMO_CART_NOTE'); ?></p>
                    </div>
                    <div style="position:absolute;left:-9999px" aria-hidden="true">
                        <label for="mm-customer-website">Website</label>
                        <input id="mm-customer-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <?php if($this->needPurchaseVerification): ?>
                        <div class="mb-3">
                            <label for="mm-purchase-code" class="form-label"><?php echo Text::_('COM_MINIMARKET_OTP_PURCHASE_CODE'); ?></label>
                            <input type="text" id="mm-purchase-code" name="purchase_code"
                                   class="form-control" pattern="[0-9]{6}" inputmode="numeric"
                                   maxlength="6" autocomplete="one-time-code" required>
                        </div>
                    <?php endif; ?>
                     <?php if($this->consentText!==''): ?>
                         <div class="mb-3 mm-checkout-consent">
                             <?php echo InputFilter::getInstance()->clean($this->consentText,'HTML'); ?>
                         </div>
                     <?php endif; ?>
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
