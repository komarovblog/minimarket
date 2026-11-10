/* MiniMarket 0.5.76 — progressive AJAX catalog sorting and lazy pagination.
 * Without JavaScript the regular GET form and prev/next links keep working.
 * Always fetch a page from the full server-sorted catalog, never sort just
 * the products already present in the browser.
 */
(() => {
  'use strict';
  const root = document.querySelector('.com-minimarket-catalog');
  if (!root || !root.querySelector('[data-mm-root-content]')) return;

  let pending = null;
  let observer = null;
  let appending = false;

  const content = () => root.querySelector('[data-mm-root-content]');
  const fetchPage = async (url, {append=false, push=false}={}) => {
    pending?.abort();
    const controller = new AbortController();
    pending = controller;
    root.setAttribute('aria-busy','true');
    try {
      const response = await fetch(url.toString(),{
        credentials:'same-origin',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        signal:controller.signal
      });
      if (!response.ok) throw new Error('Catalog request failed');
      const html = new DOMParser().parseFromString(await response.text(),'text/html');
      const remote = html.querySelector('.com-minimarket-catalog [data-mm-root-content]');
      const current = content();
      if (!remote || !current) throw new Error('Missing catalog page');
      if (append) {
        const list = current.querySelector('[data-mm-root-list]');
        const next = remote.querySelector('[data-mm-root-list]');
        if (!list || !next) throw new Error('Missing catalog product rows');
        for (const item of next.querySelectorAll(':scope > li')) {
          list.appendChild(document.importNode(item,true));
        }
        const beforeNav = current.querySelector('[data-mm-root-pages]');
        const afterNav = remote.querySelector('[data-mm-root-pages]');
        if (beforeNav) {
          if (afterNav) beforeNav.replaceWith(document.importNode(afterNav,true));
          else beforeNav.remove();
        }
      } else {
        current.replaceWith(document.importNode(remote,true));
        if (push) history.pushState(null,'',url.toString());
      }
      monitorNext();
    } catch (error) {
      if (error.name !== 'AbortError') {
        // A failing AJAX request must not leave stale results for the
        // selected sort order. Ordinary browser navigation is our fallback.
        window.location.assign(url.toString());
      }
    } finally {
      if (pending === controller) {
        pending = null;
        root.removeAttribute('aria-busy');
      }
      appending = false;
    }
  };

  const sortUrl = () => {
    const form = root.querySelector('[data-mm-root-sort-form]');
    const choice = root.querySelector('[data-mm-root-sort]');
    const url = new URL(form?.action || window.location.href, window.location.href);
    const order = choice?.value;
    if (order && order !== 'newest') url.searchParams.set('mm_order',order);
    url.searchParams.delete('mm_page');
    return url;
  };

  const monitorNext = () => {
    observer?.disconnect();
    if (!('IntersectionObserver' in window)) return;
    const link = root.querySelector('[data-mm-root-next]');
    if (!link) return;
    observer = new IntersectionObserver(entries => {
      if (!entries.some(entry=>entry.isIntersecting) || pending || appending) return;
      appending = true;
      observer.disconnect();
      fetchPage(new URL(link.href,window.location.href),{append:true});
    },{rootMargin:'200px'});
    observer.observe(link);
  };

  root.addEventListener('change',event=>{
    if (event.target.matches('[data-mm-root-sort]')) fetchPage(sortUrl(),{push:true});
  });
  root.addEventListener('submit',event=>{
    if (!event.target.matches('[data-mm-root-sort-form]')) return;
    event.preventDefault();
    fetchPage(sortUrl(),{push:true});
  });
  root.addEventListener('click',event=>{
    const link = event.target.closest('[data-mm-root-page]');
    if (!link) return;
    event.preventDefault();
    fetchPage(new URL(link.href,window.location.href),{push:true});
  });
  window.addEventListener('popstate',()=>{
    fetchPage(new URL(window.location.href),{push:false});
  });
  monitorNext();
})();
