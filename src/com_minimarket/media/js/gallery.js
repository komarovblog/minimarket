/* MiniMarket product gallery. No third-party dependency or public-image proxy.
 * The original full-size image link remains usable if JavaScript is unavailable. */
(() => {
  'use strict';
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
  const galleries = document.querySelectorAll('[data-mm-product-gallery]');
  if (!galleries.length) return;
  for (const gallery of galleries) {
    const main = gallery.querySelector('[data-mm-gallery-main]');
    const link = gallery.querySelector('[data-mm-gallery-main-link]');
    if (!main || !link) continue;
    const zoom = gallery.dataset.mmGalleryEffect === 'zoom';
    if (zoom) gallery.classList.add('mm-product-gallery--zoom');

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-mm-gallery-thumb]');
      if (!button || !gallery.contains(button)) return;
      const src = button.dataset.mmGallerySrc || '';
      if (!src) return;
      main.src = src;
      main.alt = button.dataset.mmGalleryAlt || '';
      link.href = src;
      gallery.querySelectorAll('[data-mm-gallery-thumb]').forEach((thumb) =>
        thumb.setAttribute('aria-pressed', thumb === button ? 'true' : 'false'));
    });

    if (zoom) {
      const move = (event) => {
        if (!finePointer.matches) return;
        const bounds = link.getBoundingClientRect();
        const x = Math.max(0,Math.min(100,(event.clientX-bounds.left)/bounds.width*100));
        const y = Math.max(0,Math.min(100,(event.clientY-bounds.top)/bounds.height*100));
        main.style.transformOrigin = x + '% ' + y + '%';
      };
      link.addEventListener('pointermove',move,{passive:true});
      link.addEventListener('pointerleave',() => {main.style.transformOrigin='center';});
    }
    // Native modal with a usable Escape key, focus restoration and a mobile
    // fallback. Source URLs are already validated gallery image URLs in HTML.
    if (typeof HTMLDialogElement === 'undefined') continue;
    const modal = document.createElement('dialog');
    modal.className = 'mm-gallery-lightbox';
    modal.setAttribute('aria-label',gallery.dataset.mmGalleryTitle || 'Product images');
    modal.innerHTML = '<div class="mm-gallery-lightbox__frame">'
      + '<button type="button" class="mm-gallery-lightbox__close" aria-label="Close">×</button>'
      + '<button type="button" class="mm-gallery-lightbox__prev" aria-label="Previous image">‹</button>'
      + '<img class="mm-gallery-lightbox__image" alt="">'
      + '<button type="button" class="mm-gallery-lightbox__next" aria-label="Next image">›</button></div>';
    document.body.appendChild(modal);
    const image = modal.querySelector('img');
    const close = modal.querySelector('.mm-gallery-lightbox__close');
    const prev = modal.querySelector('.mm-gallery-lightbox__prev');
    const next = modal.querySelector('.mm-gallery-lightbox__next');
    let index=0, urls=[];
    function collect() {
      urls=[...gallery.querySelectorAll('[data-mm-gallery-thumb]')].map(x=>({
        url:x.dataset.mmGallerySrc||'',alt:x.dataset.mmGalleryAlt||''
      })).filter(x=>x.url);
      if (!urls.length) urls=[{url:link.href,alt:main.alt}];
      const match=urls.findIndex(x=>x.url===main.src||x.url===link.href);
      index=match>=0?match:0;
    }
    function show() {
      if (!urls.length)return;
      index=(index+urls.length)%urls.length;
      image.src=urls[index].url;
      image.alt=urls[index].alt;
      prev.hidden=next.hidden=urls.length<2;
    }
    link.addEventListener('click',(e)=>{
      if (e.ctrlKey||e.metaKey||e.shiftKey||e.altKey||e.button!==0)return;
      e.preventDefault();
      collect();show();modal.showModal();close.focus();
    });
    const step=(delta)=>{index+=delta;show();};
    prev.addEventListener('click',()=>step(-1));
    next.addEventListener('click',()=>step(1));
    close.addEventListener('click',()=>modal.close());
    modal.addEventListener('click',e=>{if(e.target===modal)modal.close();});
    modal.addEventListener('keydown',(e)=>{
      if(e.key==='ArrowLeft'){e.preventDefault();step(-1);}
      if(e.key==='ArrowRight'){e.preventDefault();step(1);}
    });
    modal.addEventListener('close',()=>{image.removeAttribute('src');link.focus({preventScroll:true});});
  }
})();
