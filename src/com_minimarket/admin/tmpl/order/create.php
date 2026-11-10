<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$firstDelivery=array_key_first($this->deliveryMethods);
$firstPayment=array_key_first($this->paymentMethods);
?>
<section class="mm-admin-panel card card-body">
    <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_TITLE'); ?></h2>
    <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_HELP'); ?></p>
    <?php if (!$this->deliveryMethods || !$this->paymentMethods) : ?>
        <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_METHODS_MISSING'); ?></div>
    <?php else : ?>
    <form id="mm-manual-order-form" method="post"
          action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=order.create',false)); ?>">
        <fieldset class="mb-4">
            <legend class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></legend>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mm-order-create-name"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?> *</label>
                    <input id="mm-order-create-name" name="customer_name" class="form-control" required minlength="2" maxlength="120">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mm-order-create-phone"><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?> *</label>
                    <input id="mm-order-create-phone" name="phone" type="tel" class="form-control" required maxlength="32">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mm-order-create-email"><?php echo Text::_('COM_MINIMARKET_ORDER_EMAIL'); ?></label>
                    <input id="mm-order-create-email" name="email" type="email" maxlength="254" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mm-order-create-comment"><?php echo Text::_('COM_MINIMARKET_ORDER_COMMENT'); ?></label>
                    <textarea id="mm-order-create-comment" name="comment" maxlength="2000" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </fieldset>
        <fieldset class="mb-4">
            <legend class="h5"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_ITEMS'); ?></legend>
            <div id="mm-manual-order-lines">
                <?php for ($i=0;$i<3;$i++) : ?>
                    <div class="row g-2 mb-2 align-items-end" data-mm-manual-line>
                        <div class="col-12 col-lg-9">
                            <label class="form-label" for="mm-manual-product-<?php echo $i; ?>"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_PRODUCT'); ?></label>
                            <select name="items[<?php echo $i; ?>][key]" id="mm-manual-product-<?php echo $i; ?>" class="form-select">
                                <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
                                <?php foreach ($this->productChoices as $item) : ?>
                                    <option value="<?php echo $this->escape($item['key']); ?>"><?php echo $this->escape($item['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label"><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></label>
                            <input type="number" name="items[<?php echo $i; ?>][quantity]" class="form-control" min="1" max="99" value="1">
                        </div>
                        <div class="col-6 col-lg-1">
                            <button type="button" class="btn btn-outline-danger w-100" data-mm-manual-remove aria-label="<?php echo $this->escape(Text::_('JREMOVE')); ?>">×</button>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="mm-manual-add-line">
                <?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_ADD_LINE'); ?></button>
        </fieldset>
        <fieldset class="mb-4">
            <legend class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></legend>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($this->deliveryMethods as $method=>$settings) : ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery_method"
                               id="mm-manual-<?php echo $method; ?>" value="<?php echo $method; ?>"
                               <?php echo $method===$firstDelivery?'checked':''; ?>>
                        <label class="form-check-label" for="mm-manual-<?php echo $method; ?>">
                            <?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper($method)); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($this->deliveryMethods['pickup'])) : ?>
                <p class="text-muted small mt-2" data-mm-manual-pickup>
                    <?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_FREE_PICKUP'); ?></p>
            <?php endif; ?>
            <?php if (isset($this->deliveryMethods['courier'])) : ?>
            <div data-mm-manual-courier class="row g-3 mt-1">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="mm-manual-city"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></label>
                    <select id="mm-manual-city" name="delivery_city" class="form-select">
                        <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
                        <?php foreach ($this->cityRates as $rate) : ?>
                            <option value="<?php echo $this->escape($rate['city']); ?>"><?php echo $this->escape($rate['city']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-7">
                    <label class="form-label" for="mm-manual-address"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_ADDRESS'); ?></label>
                    <textarea name="delivery_address" id="mm-manual-address" class="form-control" rows="2" maxlength="512" minlength="5"></textarea>
                </div>
            </div>
            <?php endif; ?>
        </fieldset>
        <fieldset class="mb-4">
            <legend class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></legend>
            <?php foreach ($this->paymentMethods as $method=>$key) : ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method"
                           id="mm-manual-payment-<?php echo $method; ?>" value="<?php echo $method; ?>"
                           <?php echo $method===$firstPayment?'checked':''; ?>>
                    <label class="form-check-label" for="mm-manual-payment-<?php echo $method; ?>"><?php echo Text::_($key); ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <?php echo HTMLHelper::_('form.token'); ?>
        <button class="btn btn-primary" type="submit"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_SAVE'); ?></button>
        <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>">
            <?php echo Text::_('JCANCEL'); ?></a>
    </form>
    <?php endif; ?>
</section>
