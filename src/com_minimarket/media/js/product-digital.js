/* Contextual digital product options; all fields are ordinary native Joomla form elements. */
document.addEventListener('DOMContentLoaded',()=>{
    const type=document.querySelector('[name="jform[delivery_type]"]');
    const mode=document.querySelector('[name="jform[digital_delivery_mode]"]');
    const upload=document.querySelector('[data-mm-digital-upload]');
    const codes=document.querySelector('[data-mm-digital-license]');
    if(!type||!mode)return;
    const sync=()=>{
        if(upload)upload.hidden=type.value!=='digital'||mode.value!=='file';
        if(codes)codes.hidden=type.value!=='digital'||mode.value!=='license';
    };
    type.addEventListener('change',sync);
    mode.addEventListener('change',sync);
    sync();
});
