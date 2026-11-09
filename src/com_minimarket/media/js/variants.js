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
  const cartVariant = document.querySelector('[data-mm-cart-variant]');
  const cartButton = document.querySelector('[data-mm-cart-add]');
  const startId = Number(root.dataset.mmSelected || 0);
  let lastSelected = variants.find(v => v.id === startId) || variants[0];
  const details = {
    sku: document.querySelector('[data-mm-variant-sku]'),
    price: document.querySelector('[data-mm-variant-price]'),
    stock: document.querySelector('[data-mm-variant-stock]'),
    availability: root.querySelector('[data-mm-variant-availability]'),
  };
  const sharedStock = root.dataset.mmStockMode === '0';
  const inventoryEnabled = root.dataset.mmInventoryEnabled !== '0';
  const baseStock = Number(root.dataset.mmStockBase || 0);
  const availableText = root.dataset.mmInStock || 'In stock';
  const unavailableText = root.dataset.mmOutOfStock || 'Out of stock';
  const gallery = document.querySelector('[data-mm-product-gallery]');
  const main = gallery?.querySelector('[data-mm-gallery-main]');
  const mainLink = gallery?.querySelector('[data-mm-gallery-main-link]');
  const thumbs = gallery?.querySelector('[data-mm-gallery-thumb]')?.parentElement;
  const fallback = main ? { url: main.src, alt: main.alt } : null;
  const intro=document.querySelector('[data-mm-variant-intro]');
  const description=document.querySelector('[data-mm-variant-description]');
  const modelVideos=document.querySelector('[data-mm-model-videos]');
  const skuVideos=document.querySelector('[data-mm-sku-videos]');
  const skuVideoList=skuVideos?.querySelector('[data-mm-sku-video-list]');
  const baseIntro=root.dataset.mmIntroBase||'';
  const baseDescription=root.dataset.mmDescriptionBase||'';
  const rootUrl = root.dataset.mmRoot || document.baseURI;
  const makeUrl = (path) => {
    if (!path || path.includes('..') || !path.startsWith('images/')) return '';
    return new URL(path, rootUrl).href;
  };
  const updateGallery = (variant) => {
    if (!main || !mainLink) return;
    const imgs = [];
    const seen = new Set();
    const add = (path, alt) => {
      const url = makeUrl(path);
      if (!url || seen.has(url)) return;
      seen.add(url);
      imgs.push({ url, alt: alt || '' });
    };
    const selectedGallery = variant.gallery || [];
    const hasCover = selectedGallery.some(photo => photo.cover && photo.path);
    for (const photo of selectedGallery) {
      if (photo.cover) add(photo.path, photo.alt);
    }
    if (!hasCover && variant.image) add(variant.image, variant.image_alt);
    // SKU-specific gallery covers suppress the product-wide fallback image.
    for (const img of (hasCover ? [] : baseImages)) {
      if (!img?.url || seen.has(img.url)) continue;
      seen.add(img.url);
      imgs.push(img);
    }
    for (const photo of selectedGallery) add(photo.path, photo.alt);
    for (const photo of variant.photos || []) add(photo.path, photo.alt);
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
  const updateDescriptions=(variant)=>{
    const own=Number(variant.description_mode)===1;
    const updates=[
      [intro,own?(variant.introtext_override??''):baseIntro],
      [description,own?(variant.description_override??''):baseDescription]
    ];
    for(const [node,html] of updates){
      if(!node)continue;
      // HTML is already passed through Joomla InputFilter on the PHP side.
      if(node.innerHTML!==html)node.innerHTML=html;
      node.hidden=String(html).trim()==='';
    }
  };
  const updateVideos=(variant)=>{
    const mode=Number(variant.video_mode||0);
    if(modelVideos)modelVideos.hidden=mode!==0;
    if(!skuVideos || !skuVideoList)return;
    skuVideoList.replaceChildren();
    if(mode!==1){skuVideos.hidden=true;return;}
    for(const item of variant.videos||[]){
      if(!['file','embed'].includes(item.kind)||typeof item.url!=='string')continue;
      let uri;
      try{
        uri=item.url.startsWith('images/')||item.url.startsWith('media/')
          ?new URL(item.url,rootUrl):new URL(item.url);
      }catch(_){continue;}
      const isHttps=uri.protocol==='https:';
      const local=uri.href.startsWith(rootUrl) &&
        ['images/','media/'].some(start=>item.url.startsWith(start));
      if(item.kind==='file' && !local && !isHttps)continue;
      if(item.kind==='embed' && !(isHttps &&
        ['www.youtube-nocookie.com','rutube.ru'].includes(uri.hostname)))continue;
      const row=document.createElement('div');
      row.className='mm-product-videos__item';
      if(item.title){
        const heading=document.createElement('h3');heading.textContent=item.title;
        row.appendChild(heading);
      }
      const media=document.createElement(item.kind==='embed'?'iframe':'video');
      if(item.kind==='embed'){
        media.title=item.title||'Video';
        media.loading='lazy';
        media.setAttribute('allowfullscreen','');
        media.referrerPolicy='strict-origin-when-cross-origin';
      }else{
        media.controls=true;media.preload='metadata';
        if(item.poster){
          const poster=makeUrl(item.poster);
          if(poster)media.poster=poster;
        }
      }
      media.src=uri.href;
      media.style.width='100%';
      media.style.maxWidth='720px';
      if(item.kind==='embed')media.style.aspectRatio='16 / 9';
      row.appendChild(media);
      skuVideoList.appendChild(row);
    }
    skuVideos.hidden=skuVideoList.children.length===0;
  };
  // Options are ordered: earlier choices constrain later ones. For example,
  // Color -> Size -> Material. A color remains selectable when it has any
  // available combination, even if the previously chosen size is invalid.
  // Once color is chosen, unavailable sizes are disabled immediately.
  const stockFor = (variant) => sharedStock ? baseStock : Number(variant.stock);
  const canOrder = (variant) => !inventoryEnabled || stockFor(variant) > 0;
  const available = variants.filter(canOrder);
  const current = () => Object.fromEntries(
    selects.map(select => [select.dataset.mmVariantAxis, select.value])
  );
  const compatible = (variant, axisIndex, values, choice) =>
    selects.slice(0, axisIndex).every(select =>
      variant.selection[select.dataset.mmVariantAxis] === values[select.dataset.mmVariantAxis]
    ) && variant.selection[selects[axisIndex].dataset.mmVariantAxis] === choice;

  const refreshOptions = (selected) => {
    const selectedValues = selected.selection;
    selects.forEach((select, index) => {
      for (const option of select.options) {
        // Published but out-of-stock combinations are kept in the payload to
        // show availability when directly linked, not offered for new orders.
        option.disabled = !available.some(variant =>
          compatible(variant, index, selectedValues, option.value)
        );
      }
    });
  };

  const choose = (changed) => {
    let selected = lastSelected;
    if (changed) {
      const index = selects.indexOf(changed);
      if (index >= 0) {
        const values = current();
        const candidates = available.filter(variant =>
          compatible(variant, index, values, changed.value)
        );
        if (candidates.length) {
          // Preserve as many of the remaining choices as possible. When the
          // new color has only size S, selecting it automatically chooses S.
          const tail = selects.slice(index + 1);
          const score = variant => tail.reduce((sum, select) =>
            sum + Number(variant.selection[select.dataset.mmVariantAxis] ===
              values[select.dataset.mmVariantAxis]), 0);
          selected = candidates.reduce((best, candidate) =>
            score(candidate) > score(best) ? candidate : best
          );
        }
      }
    }
    lastSelected = selected;
    if (cartVariant) cartVariant.value = String(selected.id);
    if (cartButton) cartButton.disabled = !canOrder(selected);
    selects.forEach(select => {
      select.value = selected.selection[select.dataset.mmVariantAxis] || '';
    });
    if (details.sku) details.sku.textContent = selected.sku;
    // Price belongs to the product, never to the selected combination.
    const stock = stockFor(selected);
    if (details.stock) details.stock.textContent = inventoryEnabled ? String(stock) : '—';
    if (details.availability) details.availability.textContent =
      !inventoryEnabled || stock > 0 ? availableText : unavailableText;
    refreshOptions(selected);
    updateGallery(selected);
    updateDescriptions(selected);
    updateVideos(selected);
  };
  selects.forEach(s => s.addEventListener('change', () => choose(s)));
  choose(null);
})();
