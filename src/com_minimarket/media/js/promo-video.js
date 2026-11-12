/* User-initiated promo; never autoplay or loop product videos. */
(() => {
 'use strict';
 function boot() {
  const root=document.querySelector('[data-mm-promo]');
  const player=root?.querySelector('[data-mm-promo-player]');
  if(!root || !player)return;
  const expand=root.querySelector('[data-mm-promo-expand]');
  const close=root.querySelector('[data-mm-promo-close]');
  const panel=root.querySelector('[data-mm-promo-panel]');
  const reduced=window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  const visible=()=>!document.hidden && !root.hidden;
  function play() {
   if(reduced || !visible())return;
   player.muted=true;
   const promise=player.play();
   promise?.catch?.(()=>{ /* user gesture may be required */ });
  }
  function toggle() {
   const opened=!root.classList.contains('is-expanded');
   root.classList.toggle('is-expanded',opened);
   panel.hidden=!opened;
   expand?.setAttribute('aria-expanded',String(opened));
   player.setAttribute('aria-expanded',String(opened));
   if(opened && !reduced) play(); else player.pause();
  }
  // Clicking the floating video itself is an explicit expansion gesture.
  player.addEventListener('click',toggle);
  player.addEventListener('keydown',event=>{
   if(event.key==='Enter'||event.key===' '){
    event.preventDefault();
    toggle();
   }
  });
  expand?.addEventListener('click',toggle);
  close?.addEventListener('click',()=>{
   root.hidden=true;player.pause();
  });
  document.addEventListener('visibilitychange',()=>{
   if(document.hidden)player.pause();
  });
  player.removeAttribute('autoplay');
  player.removeAttribute('loop');
  player.pause();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);
 else boot();
})();
