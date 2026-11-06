/* User-initiated expansion and dismissal, muted autoplay, reduced-motion safety. */
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
   if(document.hidden)player.pause();else play();
  });
  // Avoid media auto-playing for users who prefer less animation.
  if(reduced){player.removeAttribute('autoplay');player.pause();}else play();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);
 else boot();
})();
