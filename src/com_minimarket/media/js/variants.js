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
  const startId = Number(root.dataset.mmSelected || 0);
  let lastSelected = variants.find(v => v.id === startId) || variants[0];
  const details = {
    sku: document.querySelector('[data-mm-variant-sku]'),
    price: document.querySelector('[data-mm-variant-price]'),
    stock: document.querySelector('[data-mm-variant-stock]'),
    availability: root.querySelector('[data-mm-variant-availability]'),
  };
  const sharedStock = root.dataset.mmStockMode === '0';
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
  const current = () => Object.fromEntries(selects.map(s => [s.dataset.mmVariantAxis, s.value]));
  const choose = (changed) => {
    const values = current();
    let selected = variants.find(v => selects.every(s =>
      v.selection[s.dataset.mmVariantAxis] === values[s.dataset.mmVariantAxis]));
    if (!selected && changed) {
      // Never silently substitute an unavailable combination.
      selected = lastSelected;
    }
    if (!selected) selected = lastSelected;
    lastSelected = selected;
    selects.forEach(s => { s.value = selected.selection[s.dataset.mmVariantAxis] || ''; });
    if (details.sku) details.sku.textContent = selected.sku;
    if (details.price) details.price.textContent = selected.price;
    const stock = sharedStock ? baseStock : Number(selected.stock);
    if (details.stock) details.stock.textContent = String(stock);
    if (details.availability) details.availability.textContent =
      stock > 0 ? availableText : unavailableText;
    selects.forEach(s => {
      for (const option of s.options) {
        option.disabled = !variants.some(v =>
          v.selection[s.dataset.mmVariantAxis] === option.value);
      }
    });
    updateGallery(selected);
    updateDescriptions(selected);
    updateVideos(selected);
  };
  selects.forEach(s => s.addEventListener('change', () => choose(s)));
  choose(null);
})();
