/* Non-collapsible groups in the Joomla global Options/Display tab. */
(() => {
 'use strict';
 const start = () => {
  const groups = [
   ['category_show_children','category_show_description','mm_display_categories_note'],
   ['category_product_layout','category_product_gallery_limit','mm_display_products_note'],
   ['attribute_display_location','system_display_location','mm_display_card_note'],
   ['filter_price_mode','filter_stock_enabled','mm_system_filters_note'],
  ];
  for (const [first,last,note] of groups) {
   const from=document.getElementById('jform_'+first), to=document.getElementById('jform_'+last);
   if(!from||!to)continue;
   const a=from.closest('.control-group')||from.closest('.form-group');
   const b=to.closest('.control-group')||to.closest('.form-group');
   if(!a||!b||a.parentElement!==b.parentElement)continue;
   const parent=a.parentElement, siblings=[...parent.children];
   const startAt=siblings.indexOf(a),endAt=siblings.indexOf(b);
   if(startAt<0||endAt<startAt)continue;
   const noteNode=document.getElementById('jform_'+note);
   const title=noteNode?.closest('.control-group')?.textContent?.trim()
      ||(document.documentElement.lang?.startsWith('ru')?
      ({category_show_children:'Категории',category_product_layout:'Товары в категории',
      attribute_display_location:'Карточка товара',filter_price_mode:'Системные атрибуты в фильтре'}[first]||'Настройки'):
      ({category_show_children:'Categories',category_product_layout:'Products in category',
      attribute_display_location:'Product card',filter_price_mode:'System filter attributes'}[first]||'Settings'));
   const panel=document.createElement('section');
   panel.className='mm-settings-group card mb-3';
   const heading=document.createElement('div');
   heading.className='card-header';heading.textContent=title;
   const body=document.createElement('div');body.className='card-body';
   panel.append(heading,body);parent.insertBefore(panel,a);
   for(const row of siblings.slice(startAt,endAt+1))body.appendChild(row);
   const noteRow=noteNode?.closest('.control-group');
   if(noteRow&&noteRow.parentElement===parent)noteRow.hidden=true;
  }
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
