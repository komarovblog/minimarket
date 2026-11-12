<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$firstDelivery=array_key_first($this->deliveryMethods);
$firstPayment=array_key_first($this->paymentMethods);
?>
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
 <button type="submit" form="mm-manual-order-form" class="btn btn-success"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
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
            <?php
            $mmReference=[
             'title'=>Text::_('COM_MINIMARKET_ORDER_JOOMLA_USER'),
             'input'=>'user_id','endpoint'=>'users','multiple'=>false,'selected'=>[],
             'placeholder'=>Text::_('COM_MINIMARKET_ORDER_USER_SEARCH'),
             'help'=>Text::_('COM_MINIMARKET_ORDER_USER_PICKER_HELP'),
             'edit_url_prefix'=>$this->canViewUsers?'index.php?option=com_users&task=user.edit&id=':''
            ];
            require dirname(__DIR__).'/shared/reference-picker.php';
            ?>
            <div class="row g-3 mt-2">
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
            <div id="mm-manual-order-lines"
                 data-mm-manual-picker-url="<?php echo $this->escape('index.php?option=com_minimarket&task=productpicker.manualItems&'.\Joomla\CMS\Session\Session::getFormToken().'=1'); ?>"
                 data-mm-manual-picker-add="<?php echo $this->escape(Text::_('COM_MINIMARKET_REF_ADD')); ?>"
                 data-mm-manual-picker-remove="<?php echo $this->escape(Text::_('COM_MINIMARKET_REF_REMOVE')); ?>"
                 data-mm-manual-picker-empty="<?php echo $this->escape(Text::_('COM_MINIMARKET_REF_EMPTY')); ?>"
                 data-mm-manual-picker-error="<?php echo $this->escape(Text::_('COM_MINIMARKET_REF_ERROR')); ?>">
                <div class="row g-2 mb-2 align-items-start" data-mm-manual-line>
                    <div class="col-12 col-lg-9">
                        <label class="form-label" for="mm-manual-search-0"><?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_PRODUCT'); ?></label>
                        <div class="input-group">
                          <input id="mm-manual-search-0" type="search" class="form-control" data-mm-manual-search
                                 maxlength="100" placeholder="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_SEARCH')); ?>">
                          <select class="form-select mm-manual-category-filter" data-mm-manual-category aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PRODUCT_CATEGORY')); ?>">
                            <option value=""><?php echo Text::_('COM_MINIMARKET_PRODUCT_ALL_CATEGORIES'); ?></option>
                            <?php foreach($this->pickerCategories as $category): ?>
                              <option value="<?php echo (int)$category->id; ?>"><?php echo $this->escape($category->title); ?></option>
                            <?php endforeach; ?>
                          </select>
                          <button type="button" class="btn btn-primary" data-mm-manual-query><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
                        </div>
                        <input type="hidden" name="items[0][key]" value="" data-mm-manual-key>
                        <div class="small mt-2" data-mm-manual-selected></div>
                        <div class="mm-manual-results mt-2" data-mm-manual-results></div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="mm-manual-qty-0"><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></label>
                        <input id="mm-manual-qty-0" type="number" name="items[0][quantity]" class="form-control"
                               min="1" max="99" value="1">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label class="form-label d-block" aria-hidden="true">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger w-100" data-mm-manual-remove
                                title="<?php echo $this->escape(Text::_('JREMOVE')); ?>"
                                aria-label="<?php echo $this->escape(Text::_('JREMOVE')); ?>"><span class="icon-trash" aria-hidden="true"></span></button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-success btn-sm" id="mm-manual-add-line">
                + <?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_ADD_LINE'); ?></button>
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
                    <?php if($this->zonesMode): ?>
                    <input id="mm-manual-city" name="delivery_city" class="form-control" maxlength="120"
                           list="mm-manual-zone-cities" required>
                    <datalist id="mm-manual-zone-cities">
                     <?php foreach($this->zoneCities as $city): ?><option value="<?php echo $this->escape($city); ?>"></option><?php endforeach; ?>
                    </datalist>
                    <?php else: ?>
                    <select id="mm-manual-city" name="delivery_city" class="form-select">
                        <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
                        <?php foreach ($this->cityRates as $rate) : ?>
                            <option value="<?php echo $this->escape($rate['city']); ?>"><?php echo $this->escape($rate['city']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <?php if($this->zonesMode): ?>
                <div class="col-12 col-md-4"><label class="form-label" for="mm-manual-country"><?php echo Text::_('COM_MINIMARKET_ZONE_COUNTRY'); ?></label>
                 <input id="mm-manual-country" name="delivery_country" class="form-control" maxlength="2" value="RU" pattern="[A-Za-z]{2}"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="mm-manual-region"><?php echo Text::_('COM_MINIMARKET_ZONE_REGION'); ?></label>
                 <input id="mm-manual-region" name="delivery_region" class="form-control" maxlength="120"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="mm-manual-postcode"><?php echo Text::_('COM_MINIMARKET_ZONE_POSTCODE'); ?></label>
                 <input id="mm-manual-postcode" name="delivery_postcode" class="form-control" maxlength="16"></div>
                <?php endif; ?>
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

    </form>
    <?php endif; ?>
</section>
