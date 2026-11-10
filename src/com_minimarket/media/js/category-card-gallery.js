/* Event delegation survives AJAX facet/pagination DOM replacements. */
(() => {
 'use strict';
 document.addEventListener('click',event=>{
  const btn=event.target.closest('[data-mm-card-gallery-step]');
  if(!btn)return;
  const gallery=btn.closest('[data-mm-card-gallery]');
  if(!gallery)return;
  let photos;
  try{photos=JSON.parse(gallery.dataset.mmGalleryPhotos||'[]');}catch(_){return;}
  if(!Array.isArray(photos)||photos.length<2)return;
  const image=gallery.querySelector('[data-mm-card-gallery-image]');
  if(!image)return;
  const old=Number(gallery.dataset.mmGalleryIndex||0);
  const step=Number(btn.dataset.mmCardGalleryStep||0);
  const index=((old+step)%photos.length+photos.length)%photos.length;
  const photo=photos[index];
  if(!photo||typeof photo.url!=='string')return;
  gallery.dataset.mmGalleryIndex=String(index);
  image.src=photo.url;image.alt=String(photo.alt||'');
  const indicator=gallery.querySelector('[data-mm-card-gallery-indicator]');
  if(indicator)indicator.textContent=(index+1)+' / '+photos.length;
 });
})();
