/* Product sections and bottom purchase bar. No dependencies on Joomla template colors.
 * Without JavaScript the full article, buttons and add-to-cart form remain usable.
 */
(() => {
  'use strict';
  const page = document.querySelector('[data-mm-product-page]');
  if (!page) return;
  const tabs = [...page.querySelectorAll('[data-mm-product-tab]')];
  const panels = [...page.querySelectorAll('[data-mm-tab-panel]')];
  const isTabs = page.dataset.mmPageLayout === 'tabs' && tabs.length > 0;
  const keys = new Set(tabs.map(t => t.dataset.mmProductTab));
  const selectTab = (key, focus = false) => {
    if (!isTabs || !keys.has(key)) return;
    for (const button of tabs) {
      const active = button.dataset.mmProductTab === key;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
      button.tabIndex = active ? 0 : -1;
      if (active && focus) button.focus();
    }
    for (const panel of panels) {
      const active = panel.dataset.mmTabPanel === key;
      panel.hidden = !active;
      panel.classList.toggle('is-active', active);
    }
  };
  if (isTabs) {
    for (const panel of panels) {
      const key = panel.dataset.mmTabPanel;
      if (!keys.has(key)) continue;
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', 'mm-product-tab-' + key);
    }
    const selected = location.hash.replace(/^#mm-product-/, '');
    selectTab(keys.has(selected) ? selected : tabs[0].dataset.mmProductTab);
    for (const button of tabs) {
      button.addEventListener('click', () => selectTab(button.dataset.mmProductTab));
      button.addEventListener('keydown', (event) => {
        if (!['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
        event.preventDefault();
        const index = tabs.indexOf(button);
        const last = tabs.length - 1;
        const next = event.key === 'Home' ? 0
          : event.key === 'End' ? last
          : event.key === 'ArrowRight' ? (index + 1) % tabs.length
          : (index + last) % tabs.length;
        selectTab(tabs[next].dataset.mmProductTab, true);
      });
    }
    window.addEventListener('hashchange', () => {
      const key = location.hash.replace(/^#mm-product-/, '');
      if (keys.has(key)) selectTab(key);
    });
  }
  for (const link of page.querySelectorAll('[data-mm-section-link]')) {
    if (!isTabs) continue;
    link.addEventListener('click', (event) => {
      const key = link.dataset.mmSectionLink;
      if (!keys.has(key)) return;
      event.preventDefault();
      selectTab(key);
      history.replaceState(null, '', '#mm-product-' + key);
      page.querySelector('#mm-product-tab-' + key)?.scrollIntoView({block:'start'});
    });
  }

  const sticky = page.querySelector('[data-mm-product-sticky]');
  const form = page.querySelector('[data-mm-cart-form]');
  if (!sticky || !form) return;
  const mainQty = form.querySelector('input[name="quantity"]');
  const originalButton = form.querySelector('[data-mm-cart-add]');
  const stickyQty = sticky.querySelector('[data-mm-sticky-quantity]');
  const stickyBuy = sticky.querySelector('[data-mm-sticky-buy]');
  const stickyImage = sticky.querySelector('[data-mm-sticky-image]');
  const mainImage = page.querySelector('[data-mm-gallery-main]');
  let busy = false;
  const update = () => {
    busy = false;
    // The bar is shown only AFTER the original purchase form has moved above
    // the viewport, never on first render while the form is further down.
    const past = form.getBoundingClientRect().bottom < 0;
    sticky.hidden = !past;
    document.body.classList.toggle('mm-product-sticky-visible', past);
    if (stickyBuy) stickyBuy.disabled = !originalButton || originalButton.disabled;
    if (stickyImage && mainImage) {
      if (stickyImage.src !== mainImage.src) stickyImage.src = mainImage.src;
    }
  };
  const schedule = () => {
    if (busy) return;
    busy = true;
    requestAnimationFrame(update);
  };
  window.addEventListener('scroll', schedule, {passive:true});
  window.addEventListener('resize', schedule, {passive:true});
  if (originalButton) {
    new MutationObserver(schedule).observe(originalButton, {attributes:true,attributeFilter:['disabled']});
  }
  if (mainImage && stickyImage) {
    new MutationObserver(schedule).observe(mainImage, {attributes:true,attributeFilter:['src']});
  }
  if (mainQty && stickyQty) {
    mainQty.addEventListener('input', () => {
      if (document.activeElement !== stickyQty) stickyQty.value = mainQty.value;
    });
    stickyQty.addEventListener('change', () => {
      mainQty.value = stickyQty.value;
    });
  }
  stickyBuy?.addEventListener('click', () => {
    if (stickyBuy.disabled || !originalButton || !mainQty || !stickyQty) return;
    if (!stickyQty.checkValidity()) {
      stickyQty.reportValidity();
      return;
    }
    mainQty.value = stickyQty.value;
    if (typeof form.requestSubmit === 'function') form.requestSubmit(originalButton);
    else originalButton.click();
  });
  update();
})();
