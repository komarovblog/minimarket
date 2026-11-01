/* Accessible thumbnail selector. Original images remain normal links. */
(() => {
  'use strict';
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-mm-gallery-thumb]');
    if (!button) return;
    const gallery = button.closest('[data-mm-product-gallery]');
    if (!gallery) return;

    const main = gallery.querySelector('[data-mm-gallery-main]');
    const link = gallery.querySelector('[data-mm-gallery-main-link]');
    if (!main || !link) return;

    const src = button.dataset.mmGallerySrc;
    const alt = button.dataset.mmGalleryAlt || '';
    if (!src) return;
    main.src = src;
    main.alt = alt;
    link.href = src;
    gallery.querySelectorAll('[data-mm-gallery-thumb]').forEach((thumb) => {
      thumb.setAttribute('aria-pressed', thumb === button ? 'true' : 'false');
    });
  });
})();
