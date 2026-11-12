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
  let previousObserver = null;
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
  const incrementalNav = (container, source, url, direction) => {
    const oldNav = container.querySelector('[data-mm-category-pages]');
    const remoteNav = source.querySelector('[data-mm-category-pages]');
    if (!oldNav || !remoteNav) return;
    const nav = document.importNode(remoteNav, true);
    const minOld=Number(oldNav.dataset.mmMinPage)||1;
    const maxOld=Number(oldNav.dataset.mmMaxPage)||1;
    const page=Number(new URL(url, location.href).searchParams.get('mm_page'))||1;
    const min=direction==='previous'?Math.min(minOld,page):minOld;
    const max=direction==='next'?Math.max(maxOld,page):maxOld;
    const count=Number(nav.dataset.mmPageCount)||1;
    const perPage=Number(nav.dataset.mmPageSize)||10;
    const total=Number(nav.dataset.mmTotalItems)||0;
    nav.dataset.mmMinPage=String(min);
    nav.dataset.mmMaxPage=String(max);
    for (const link of nav.querySelectorAll('[data-mm-page-link]')) link.remove();
    const makeLink=(page,direction,label,count)=>{
      if(page<1||page>Number(nav.dataset.mmPageCount))return;
      const href=new URL(url, location.href);
      href.searchParams.set('mm_page',String(page));
      const a=document.createElement('a');
      a.href=href.toString();a.dataset.mmPageLink='';
      a.dataset[direction==='next'?'mmNextPage':'mmPrevPage']='';
      a.rel=direction==='next'?'next':'prev';
      a.textContent=label.replace('%d',String(count));
      nav.appendChild(a);
    };
    const status=nav.querySelector('span');
    if(status)status.textContent=min===max?min+' / '+count:min+'–'+max+' / '+count;
    if(min>1 && nav.dataset.mmAllowPrevious==='1')
      makeLink(min-1,'previous',nav.dataset.mmPrevLabel||'Previous %d',Math.min(perPage,(min-1)*perPage));
    if(max<count)
      makeLink(max+1,'next',nav.dataset.mmNextLabel||'Next %d',Math.min(perPage,total-max*perPage));
    oldNav.replaceWith(nav);
  };
  const load = async (url, {push=true, append=false, prepend=false}={}) => {
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
      if (append || prepend) {
        const destination = current.querySelector('.mm-category-products');
        const source = remote.querySelector('.mm-category-products');
        if (!destination || !source) throw new Error('Missing next product page');
        const previousHeight=document.documentElement.scrollHeight;
        const fragment=document.createDocumentFragment();
        for (const product of source.querySelectorAll(':scope > li')) {
          fragment.appendChild(document.importNode(product,true));
        }
        if (prepend) {
          destination.prepend(fragment);
          window.scrollBy(0,document.documentElement.scrollHeight-previousHeight);
        } else {
          destination.appendChild(fragment);
        }
        incrementalNav(current,remote,url,prepend?'previous':'next');
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
    previousObserver?.disconnect();
    const nav=root.querySelector('[data-mm-category-pages]');
    if (!nav || nav.dataset.mmNavigationMode!=='load_more'
        || nav.dataset.mmLoadTrigger!=='scroll'
        || !('IntersectionObserver' in window)) return;
    const next=nav.querySelector('[data-mm-next-page]');
    if(next){
      observer=new IntersectionObserver(entries=>{
        if(!entries.some(entry=>entry.isIntersecting)||loadingMore||request)return;
        loadingMore=true;observer.disconnect();
        load(new URL(next.href,location.href),{push:false,append:true});
      },{rootMargin:'150px'});
      observer.observe(next);
    }
    const previous=nav.querySelector('[data-mm-prev-page]');
    const sentinel=root.querySelector('[data-mm-prev-sentinel]');
    if(previous && sentinel){
      previousObserver=new IntersectionObserver(entries=>{
        if(!entries.some(entry=>entry.isIntersecting)||loadingMore||request)return;
        loadingMore=true;previousObserver.disconnect();
        load(new URL(previous.href,location.href),{push:false,prepend:true});
      },{rootMargin:'0px'});
      previousObserver.observe(sentinel);
    }
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
      const nav=link.closest('[data-mm-category-pages]');
      if (nav?.dataset.mmNavigationMode==='load_more') {
        if (request) return;
        loadingMore=true;
        load(new URL(link.href,location.href),{
          push:false,append:link.hasAttribute('data-mm-next-page'),
          prepend:link.hasAttribute('data-mm-prev-page')
        });
      } else {
        load(new URL(link.href,location.href));
      }
    }
  });
  window.addEventListener('popstate',()=>load(new URL(location.href),{push:false}));
  ranges();
  observeNext();
})();
