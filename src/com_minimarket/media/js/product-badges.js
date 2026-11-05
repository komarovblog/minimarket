/* MiniMarket badges: optional price comparison, no form auto-submission. */
(() => {
  'use strict';
  function init() {
    const old = document.getElementById('jform_old_price');
    const sale = document.getElementById('jform_badge_sale');
    const newer = document.getElementById('jform_badge_new');
    const popular = document.getElementById('jform_badge_popular');
    const newDays = document.getElementById('jform_badge_new_days');
    const popularDays = document.getElementById('jform_badge_popular_days');
    if (!old || !sale) return;
    function update() {
      const oldHasValue = old.value.trim() !== '' && Number(old.value) > 0;
      const saleContainer = sale.closest('.control-group') || sale.parentElement;
      if (saleContainer) saleContainer.hidden = !oldHasValue;
      if (!oldHasValue) sale.checked = false;
      for (const [flag,days] of [[newer,newDays],[popular,popularDays]]) {
        if (!flag || !days) continue;
        const holder = days.closest('.control-group') || days.parentElement;
        if (holder) holder.hidden = !flag.checked;
      }
    }
    [old,newer,popular].forEach(el => el?.addEventListener('input',update));
    [sale,newer,popular].forEach(el => el?.addEventListener('change',update));
    update();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',init);
  else init();
})();
