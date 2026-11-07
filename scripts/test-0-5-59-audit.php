<?php
/** Cross-module wiring and global/category configuration parity checks. */
declare(strict_types=1);
$r=__DIR__.'/../src/com_minimarket/';
function audit(bool $ok,string $why):void {global $checks;++$checks;
 if(!$ok)throw new RuntimeException('AUDIT_FAIL: '.$why);}
$checks=0;
$c=simplexml_load_file($r.'admin/config.xml');
$f=simplexml_load_file($r.'admin/forms/category.xml');
$p=simplexml_load_file($r.'admin/forms/product.xml');
$v=simplexml_load_file($r.'admin/forms/product_video.xml');
audit($c!==false && $f!==false && $p!==false && $v!==false,'config/forms parse');
$mirrors=['category_show_children'=>'show_children','category_show_products'=>'show_products',
 'category_show_bundles'=>'show_bundles',
 'category_show_descendant_products'=>'show_descendant_products',
 'category_show_descendant_bundles'=>'show_descendant_bundles',
 'category_product_layout'=>'product_layout',
 'category_cards_xs'=>'cards_xs','category_cards_sm'=>'cards_sm',
 'category_cards_md'=>'cards_md','category_cards_lg'=>'cards_lg',
 'category_cards_xl'=>'cards_xl','category_cards_xxl'=>'cards_xxl'];
foreach($mirrors as $global=>$local) {
 audit(count($c->xpath("//fieldset[@name='categories']/field[@name='$global']"))===1,
  'missing global category option '.$global);
 audit(count($f->xpath("//fieldset[@name='display']/field[@name='$local']"))===1,
  'missing local category override '.$local);
}
foreach(['badge_sale_display','badge_benefit_display'] as $name)
 audit(count($p->xpath("//field[@name='$name']"))===1,'missing badge format '.$name);
audit(count($v->xpath("//field[@name='is_promo']"))===1,'video promo form missing');
$edit=file_get_contents($r.'admin/tmpl/product/edit.php');
audit(str_contains($edit,"renderField('badge_sale_display')")
 && str_contains($edit,"renderField('badge_benefit_display')"),
 'badge format fields do not render');
$catalog=file_get_contents($r.'site/src/Service/CatalogRepository.php');
audit(substr_count($catalog,"quoteName('p.badge_benefit_display')")===3 &&
 substr_count($catalog,"quoteName('p.bundle_discount_pct')")===3,
 'catalog/category/product reads omit price badge fields');
$front=file_get_contents($r.'site/tmpl/product/default.php');
$selector=file_get_contents($r.'media/js/variants.js');
audit(str_contains($front,'data-mm-variant-axis')
 && str_contains($selector,"addEventListener('change'")
 && str_contains($selector,'refreshOptions'),
 'SKU option selectors lack frontend wiring');
audit(str_contains($front,'data-mm-promo')
 && str_contains(file_get_contents($r.'media/js/promo-video.js'),'prefers-reduced-motion'),
 'promo video missing or inaccessible');
foreach(['ru-RU','en-GB'] as $lang) {
 $admin=parse_ini_file($r."admin/language/$lang/com_minimarket.ini");
 $site=parse_ini_file($r."site/language/$lang/com_minimarket.ini");
 audit(isset($admin['COM_MINIMARKET_CONFIG_CATEGORIES'])
  && isset($admin['COM_MINIMARKET_BADGE_DISPLAY'])
  && isset($admin['COM_MINIMARKET_VIDEO_PROMO']),"admin translation $lang");
 audit(isset($site['COM_MINIMARKET_VIDEO_PROMO_BUY'])
  && isset($site['COM_MINIMARKET_BADGE_SALE_MONEY']),"site translation $lang");
}
$schema=file_get_contents($r.'admin/sql/updates/mysql/0.5.59.sql');
audit(str_contains($schema,'ADD COLUMN `is_promo`')
 && str_contains($schema,'ADD COLUMN `badge_sale_display`')
 && !preg_match('/\\b(?:DROP|DELETE|TRUNCATE)\\b/i',$schema),
 'schema migration missing or destructive');
echo "MM_059_AUDIT_OK $checks checks\n";
