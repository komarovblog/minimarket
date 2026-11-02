/* Choose real SKU combinations on the same product URL. */
(() => {
  'use strict';
  const root = document.querySelector('[data-mm-variants]');
  if (!root) return;
  let variants = [], baseImages = [];
  try { variants = JSON.parse(root.dataset.mmVariantsData || '[]'); } catch (_) {}
  try { baseImages = JSON.parse(root.dataset.mmGalleryBase || '[]'); } catch (_) {}
  if (!Array.isArray(variants) || !variants.length) return;
  const selects = Array.from(root.querySelectorAll('[data-mm-variant-axis]'));
  const details = {
    sku: document.querySelector('[data-mm-variant-sku]'),
    price: document.querySelector('[data-mm-variant-price]'),
    stock: document.querySelector('[data-mm-variant-stock]'),
    availability: root.querySelector('[data-mm-variant-availability]'),
  };
  const availableText = root.dataset.mmInStock || 'In stock';
  const unavailableText = root.dataset.mmOutOfStock || 'Out of stock';
  const gallery = document.querySelector('[data-mm-product-gallery]');
  const main = gallery?.querySelector('[data-mm-gallery-main]');
  const mainLink = gallery?.querySelector('[data-mm-gallery-main-link]');
  const thumbs = gallery?.querySelector('[data-mm-gallery-thumb]')?.parentElement;
  const fallback = main ? { url: main.src, alt: main.alt } : null;
  const rootUrl = root.dataset.mmRoot || document.baseURI;
  const makeUrl = (path) => {
    if (!path || path.includes('..') || !path.startsWith('images/')) return '';
    return new URL(path, rootUrl).href;
  };
  const updateGallery = (variant) => {
    if (!main || !mainLink) return;
    const imgs = [];
    if (variant.image) imgs.push({ url: makeUrl(variant.image), alt: variant.image_alt || '' });
    for (const photo of variant.photos || []) {
      if (photo.path) imgs.push({ url: makeUrl(photo.path), alt: photo.alt || '' });
    }
    if (!imgs.length) imgs.push(...baseImages);
    if (!imgs.length && fallback) imgs.push(fallback);
    const first = imgs[0];
    if (!first || !first.url) return;
    main.src = first.url;
    main.alt = first.alt;
    mainLink.href = first.url;
    if (thumbs) {
      thumbs.replaceChildren();
      imgs.forEach((img, i) => {
        if (!img.url) return;
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'mm-product-gallery__thumb';
        button.dataset.mmGalleryThumb = '1';
        button.dataset.mmGallerySrc = img.url;
        button.dataset.mmGalleryAlt = img.alt;
        button.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
        const picture = document.createElement('img');
        picture.src = img.url; picture.alt = ''; picture.loading = 'lazy';
        button.appendChild(picture); thumbs.appendChild(button);
      });
      thumbs.hidden = thumbs.children.length < 2;
    }
  };
  const current = () => Object.fromEntries(selects.map(s => [s.dataset.mmVariantAxis, s.value]));
  const choose = (changed) => {
    const values = current();
    let selected = variants.find(v => selects.every(s =>
      v.selection[s.dataset.mmVariantAxis] === values[s.dataset.mmVariantAxis]));
    if (!selected && changed) {
      const choices = variants.filter(v => v.selection[changed.dataset.mmVariantAxis] === changed.value);
      selected = choices.find(v => v.stock > 0) || choices[0] || variants[0];
    }
    if (!selected) selected = variants.find(v => v.stock > 0) || variants[0];
    selects.forEach(s => { s.value = selected.selection[s.dataset.mmVariantAxis] || ''; });
    if (details.sku) details.sku.textContent = selected.sku;
    if (details.price) details.price.textContent = selected.price;
    if (details.stock) details.stock.textContent = String(selected.stock);
    if (details.availability) details.availability.textContent =
      selected.stock > 0 ? availableText : unavailableText;
    selects.forEach(s => {
      for (const option of s.options) {
        option.disabled = !variants.some(v =>
          v.selection[s.dataset.mmVariantAxis] === option.value);
      }
    });
    updateGallery(selected);
  };
  selects.forEach(s => s.addEventListener('change', () => choose(s)));
  choose(null);
})();
