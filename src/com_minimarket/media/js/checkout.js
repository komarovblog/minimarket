(() => {
    'use strict';
    for (const form of document.querySelectorAll('.mm-checkout-form[data-items-cents]')) {
        const panels=[...form.querySelectorAll('[data-delivery-panel]')];
        const city=form.querySelector('[name="delivery_city"]');
        const address=form.querySelector('[name="delivery_address"]');
        const shipping=form.querySelector('[data-shipping-total]');
        const total=form.querySelector('[data-order-total]');
        const items=Number.parseInt(form.dataset.itemsCents||'0',10);
        const locale=document.documentElement.lang||'ru-RU';
        const format=(cents) => new Intl.NumberFormat(locale,{
            minimumFractionDigits:2, maximumFractionDigits:2
        }).format(cents/100)+' ₽';

        const update=() => {
            const checked=form.querySelector('input[name="delivery_method"]:checked');
            const method=checked?.value||'pickup';
            for (const panel of panels) {
                const active=panel.dataset.deliveryPanel===method;
                panel.hidden=!active;
                for (const input of panel.querySelectorAll('input,textarea,select')) {
                    input.disabled=!active;
                }
            }
            if (city) city.required=method==='courier';
            if (address) address.required=method==='courier';
            let fee=0;
            if (method==='courier') {
                const option=city?.selectedOptions?.[0];
                if (option && option.value) {
                    fee=Number.parseInt(option.dataset.feeCents||'0',10);
                    const freeFrom=Number.parseInt(option.dataset.freeFromCents||'0',10);
                    if (!Number.isSafeInteger(fee)||fee<0) fee=0;
                    if (freeFrom>0 && items>=freeFrom) fee=0;
                } else {
                    if (shipping) shipping.textContent='—';
                    if (total) total.textContent='—';
                    return;
                }
            }
            if(shipping) shipping.textContent=format(fee);
            if(total) total.textContent=format(items+fee);
        };
        for (const radio of form.querySelectorAll('input[name="delivery_method"]')) {
            radio.addEventListener('change',update);
        }
        city?.addEventListener('change',update);
        update();
    }
})();
