/* MiniMarket category filtering, sorting and lazy pages.
 * Filter/sort is calculated against the FULL published catalog on the server;
 * only one page of cards is delivered to the browser. Without JS, GET works.
 */
(() => {
  'use strict';
  const root = document.querySelector('.com-minimarket-category');
  if (!root || !root.querySelector('[data-mm-ajax-content]')) return;

  let request = null;
  let debounce = null;
  let observer = null;
  let loadingMore = false;

  const form = () => root.querySelector('[data-mm-facet-form]');
  const sort = () => root.querySelector('[data-mm-sort]');
  const allValues = () => {
    for (const search of root.querySelectorAll('[data-mm-choice-search]')) {
      const group = search.closest('[data-mm-facet]');
      const needle = search.value.trim().toLocaleLowerCase();
      for (const row of group?.querySelectorAll('[data-mm-facet-choice-row]') || []) {
        const label = row.querySelector('label')?.textContent?.toLocaleLowerCase() || '';
        row.hidden = needle !== '' && !label.includes(needle);
      }
    }
  };
  const ranges = () => {
    for (const range of root.querySelectorAll('[data-mm-dual-range]')) {
      const field = range.closest('[data-mm-facet]');
      const low = field?.querySelector('[data-mm-facet-bound="min"]');
      const high = field?.querySelector('[data-mm-facet-bound="max"]');
      const sliders = range.querySelectorAll('[data-mm-facet-slider]');
      if (!low || !high || sliders.length !== 2) continue;
      const base = Number(sliders[0].min);
      const ceiling = Number(sliders[0].max);
      if (!(ceiling > base)) continue;
      let min = Number(low.value), max = Number(high.value);
      if (!Number.isFinite(min)) min = base;
      if (!Number.isFinite(max)) max = ceiling;
      min = Math.max(base, Math.min(ceiling, min));
      max = Math.max(min, Math.min(ceiling, max));
      low.value = String(min);
      high.value = String(max);
      sliders[0].value = String(min);
      sliders[1].value = String(max);
      range.style.setProperty('--mm-range-start', (100 * (min - base) / (ceiling - base)) + '%');
      range.style.setProperty('--mm-range-end', (100 * (max - base) / (ceiling - base)) + '%');
    }
  };
  const urlFor = () => {
    const f = form();
    const url = new URL(f?.action || window.location.href, window.location.href);
    if (f) {
      for (const [key,value] of new FormData(f)) {
        if (String(value).trim() !== '') url.searchParams.append(key, String(value));
      }
    }
    const order = sort()?.value;
    if (order && order !== 'newest') url.searchParams.set('mm_order',order);
    url.searchParams.delete('mm_page'); // Filter and sort always start on page 1.
    return url;
  };
  const load = async (url, {push=true, append=false}={}) => {
    if (request) request.abort();
    const controller = new AbortController();
    request = controller;
    root.setAttribute('aria-busy','true');
    try {
      const response = await fetch(url, {
        credentials:'same-origin',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        signal:controller.signal
      });
      if (!response.ok) throw new Error('Request failed');
      const html = new DOMParser().parseFromString(await response.text(),'text/html');
      const remote = html.querySelector('.com-minimarket-category [data-mm-ajax-content]');
      const current = root.querySelector('[data-mm-ajax-content]');
      if (!remote || !current) throw new Error('Missing catalog content');
      if (append) {
        const destination = current.querySelector('.mm-category-products');
        const source = remote.querySelector('.mm-category-products');
        if (!destination || !source) throw new Error('Missing next product page');
        for (const product of source.querySelectorAll(':scope > li')) {
          destination.appendChild(document.importNode(product,true));
        }
        const currentNav = current.querySelector('[data-mm-category-pages]');
        const nextNav = remote.querySelector('[data-mm-category-pages]');
        if (currentNav && nextNav) {
          currentNav.replaceWith(document.importNode(nextNav,true));
        } else if (currentNav) {
          currentNav.remove();
        }
      } else {
        current.replaceWith(document.importNode(remote,true));
        if (push) history.pushState(null,'',url);
        allValues();
        ranges();
      }
      observeNext();
    } catch (error) {
      if (error.name !== 'AbortError') {
        // Real server-side GET fallback is preferable to silently showing
        // the wrong subset of products.
        window.location.assign(url);
      }
    } finally {
      if (request === controller) {
        request = null;
        root.removeAttribute('aria-busy');
      }
      loadingMore = false;
    }
  };
  const afterInput = (wait=250) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => load(urlFor()),wait);
  };
  const observeNext = () => {
    observer?.disconnect();
    if (!('IntersectionObserver' in window)) return;
    const anchor = root.querySelector('[data-mm-next-page]');
    if (!anchor) return;
    observer = new IntersectionObserver(entries => {
      if (!entries.some(entry=>entry.isIntersecting) || loadingMore || request) return;
      loadingMore = true;
      observer.disconnect();
      load(new URL(anchor.href,location.href),{push:false,append:true});
    }, {rootMargin:'200px'});
    observer.observe(anchor);
  };
  root.addEventListener('input', event => {
    if (event.target.matches('[data-mm-choice-search]')) {
      allValues();
      return;
    }
    if (event.target.matches('[data-mm-facet-slider]')) {
      const thumb = event.target, group = thumb.closest('[data-mm-facet]');
      const field = group?.querySelector('[data-mm-facet-bound="'+thumb.dataset.mmFacetSlider+'"]');
      if (field) {
        const other = group.querySelector('[data-mm-facet-bound="'+(thumb.dataset.mmFacetSlider==='min'?'max':'min')+'"]');
        let value = Number(thumb.value);
        if (other) value = thumb.dataset.mmFacetSlider==='min'
          ?Math.min(value,Number(other.value)):Math.max(value,Number(other.value));
        field.value = String(value);
      }
      ranges();
      afterInput(250);
    } else if (event.target.matches('[data-mm-facet-bound]')) {
      // Do not submit in the middle of typing a number.
      afterInput(650);
    } else if (event.target.matches('[data-mm-facet-text]')) {
      afterInput(400);
    }
  });
  root.addEventListener('change', event => {
    const el = event.target;
    if (el.matches('[data-mm-sort]')) {
      load(urlFor());
      return;
    }
    if (el.matches('[data-mm-facet-value]') && el.type==='checkbox' && el.checked) {
      const group = el.closest('[data-mm-facet]');
      if (group?.dataset.mmFacetMultiple==='0') {
        for (const input of group.querySelectorAll('[data-mm-facet-value]')) {
          if (input !== el) input.checked = false;
        }
      }
    }
    if (el.matches('[data-mm-facet-bound],[data-mm-facet-slider]')) ranges();
    if (el.matches('[data-mm-facet-value],[data-mm-facet-bound],[data-mm-facet-slider],[data-mm-facet-text]')) {
      clearTimeout(debounce);
      load(urlFor());
    }
  });
  root.addEventListener('submit',event=>{
    if (event.target.matches('[data-mm-facet-form],.mm-sort-bar')) {
      event.preventDefault();
      clearTimeout(debounce);
      load(urlFor());
    }
  });
  root.addEventListener('click',event=>{
    const reset = event.target.closest('[data-mm-facet-reset]');
    if (reset) {
      event.preventDefault();
      const url = new URL(reset.href, location.href);
      const selectedSort = sort()?.value;
      if (selectedSort && selectedSort !== 'newest')
        url.searchParams.set('mm_order',selectedSort);
      load(url);
    }
    const link = event.target.closest('[data-mm-page-link]');
    if (link) {
      event.preventDefault();
      load(new URL(link.href,location.href));
    }
  });
  window.addEventListener('popstate',()=>load(new URL(location.href),{push:false}));
  ranges();
  observeNext();
})();
