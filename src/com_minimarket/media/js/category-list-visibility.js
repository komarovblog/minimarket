/* Native Joomla card flags only apply to directly enabled category links. */
(() => {
  'use strict';
  const form=document.querySelector('#adminForm');
  if(!form)return;
  for(const mode of form.querySelectorAll('[data-mm-category-list-mode]')){
    const flag=mode.closest('tr')?.querySelector('[data-mm-category-list-flag]');
    if(!flag)continue;
    const sync=()=>{flag.disabled=mode.value!=='1'&&mode.value!=='2';};
    mode.addEventListener('change',sync);
    sync();
  }
})();
