/* Simple native dialog quick view, with catalog Ajax rerender support. */
(() => {
  'use strict';
  document.addEventListener('click',event=>{
    const button=event.target.closest('[data-mm-quick-view]');
    if(!button)return;
    const id=button.dataset.mmQuickView;
    if(!/^\d{1,10}$/.test(id))return;
    const dialog=document.getElementById('mm-category-preview-'+id);
    if(dialog instanceof HTMLDialogElement && !dialog.open)dialog.showModal();
  });
})();
