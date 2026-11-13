<?php
/** MiniMarket 0.6.13: native Joomla conditional field dependencies + server normalization. */
declare(strict_types=1);
define('_JEXEC', 1);
$root=__DIR__.'/../src/com_minimarket/';
$assert=static function(bool $ok,string $message):void{
    if(!$ok)throw new RuntimeException('MM613_CONDITIONAL_FAIL: '.$message);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$assert(version_compare((string)$manifest->version,'0.6.13','>='),'manifest version at least 0.6.13');
$xmls=[
 'config'=>'admin/config.xml',
 'category'=>'admin/forms/category.xml',
 'product'=>'admin/forms/product.xml',
 'attribute'=>'admin/forms/attribute.xml',
 'extra'=>'admin/forms/checkout_extra_field.xml',
 'variant'=>'admin/forms/product_variant.xml',
];
$fields=[];
foreach($xmls as $key=>$path){
    $xml=simplexml_load_file($root.$path);
    $assert($xml!==false,'valid XML '.$path);
    $fields[$key]=[];
    foreach($xml->xpath('//field') as $field){
        $name=(string)$field['name'];
        if($name!=='')$fields[$key][$name]=(string)$field['showon'];
    }
}
$check=static function(string $form,string $name,string $condition)use($fields,$assert):void{
    $assert(($fields[$form][$name]??null)===$condition,$form.'.'.$name.' condition');
};
$check('config','checkout_verification_method','checkout_auth_mode:guests,all');
$check('config','checkout_auth_hint','checkout_auth_mode:guests,all');
$check('config','checkout_phone_mask','checkout_step_buyer_enabled:1[AND]checkout_phone_mode!:hidden');
foreach(['checkout_name_mode','checkout_phone_mode','checkout_email_mode','checkout_comment_mode','checkout_custom_fields','checkout_buyer_description'] as $name)
    $check('config',$name,'checkout_step_buyer_enabled:1');
foreach(['delivery','payment','digital'] as $step)
    $check('config','checkout_'.$step.'_description','checkout_step_'.$step.'_enabled:1');
foreach(['reviews_author_mode','reviews_moderation','reviews_media_photo','reviews_media_video'] as $name)
    $check('config',$name,'reviews_enabled:1');
foreach(['subcategory_cards_xs','subcategory_cards_sm','subcategory_cards_md','subcategory_cards_lg','subcategory_cards_xl','subcategory_cards_xxl'] as $name)
    $check('config',$name,'category_show_children:1');
foreach(['category_cards_xs','category_cards_sm','category_cards_md','category_cards_lg','category_cards_xl','category_cards_xxl'] as $name)
    $check('config',$name,'category_show_products:1[AND]category_product_layout:grid');
$check('category','cards_xs','show_products:1,-1[AND]product_layout:grid,inherit');
$check('category','subcategory_cards_xs','show_children:1,-1');
$check('product','bundle_discount_pct','bundle_price_mode:discount');
$check('product','badge_new_days','badge_new:1');
$check('product','badge_popular_days','badge_popular:1');
$check('product','badge_sale_display','badge_sale:1');
$check('product','badge_benefit_display','badge_benefit:1');
$check('product','shipping_weight_grams','delivery_type:physical');
$check('attribute','unified_values','value_source:preset[AND]input_type!:boolean');
$check('extra','mask','type:text,tel');
$check('extra','options','type:select');
$check('variant','introtext_override','description_mode:1');
$check('variant','videos','video_mode:1');
require $root.'site/src/Service/CategoryDisplayOptions.php';
$display='MiniMarket\\Component\\Minimarket\\Site\\Service\\CategoryDisplayOptions';
$global=new class{
    private array $v=['category_show_products'=>0,'category_show_bundles'=>0,
        'category_show_descendant_products'=>1,'category_show_descendant_bundles'=>1,
        'filter_stock_enabled'=>1,'inventory_enabled'=>0];
    public function get(string $key,mixed $default=null):mixed{return $this->v[$key]??$default;}
};
$resolved=$display::resolve((object)[],$global);
$assert(!$resolved['show_products']&&!$resolved['show_bundles']
    && !$resolved['show_descendant_products']&&!$resolved['show_descendant_bundles'],
    'hidden descendant options cannot override disabled parent');
$assert(!$resolved['filter_stock_enabled'],'stock filter inactive when inventory disabled');
require $root.'site/src/Service/CheckoutExtraFields.php';
$service='MiniMarket\\Component\\Minimarket\\Site\\Service\\CheckoutExtraFields';
$from=static fn(array $entries)=>new class($entries){
    public function __construct(private array $entries){}
    public function get(string $key,mixed $default=null):mixed{
        return $key==='checkout_custom_fields'?$this->entries:$default;
    }
};
$base=['title'=>'Question','code'=>'question','state'=>1,'required'=>0];
$select=$service::definitions($from([$base+['type'=>'select','options'=>"A\nB",'mask'=>'999','placeholder'=>'old']]));
$assert($select['question']['mask']===''&&$select['question']['placeholder']===''&&$select['question']['choices']===['A','B'],
    'hidden text mask and placeholder ignored for select');
$text=$service::definitions($from([$base+['type'=>'text','options'=>implode("\n",range(1,45)),'mask'=>'999']]));
$assert($text['question']['mask']==='999'&&$text['question']['choices']===[],'hidden select choices ignored for text');
$choice=explode("\n",$select['question']['choices'][0]);
$assert($choice[0]==='A','valid selectable value');
$badges=file_get_contents($root.'media/js/product-badges.js');
$assert(str_contains($badges,'wrap.hidden=discounted')&&str_contains($badges,'wrap.hidden=!discounted'),
    'inapplicable badge toggles hidden, not disabled');
$zone=file_get_contents($root.'admin/tmpl/deliveryzone/edit.php');
$assert(str_contains($zone,"data-mm-zone-unit-rate")&&str_contains($zone,"mode.value==='fixed'"),
    'unit-rate input hidden when delivery zone uses fixed tariff');
$notify=file_get_contents($root.'admin/tmpl/notificationmethods/default.php');
$assert(str_contains($notify,'data-mm-notification-events')&&str_contains($notify,"enabled.value!=='1'"),
    'email event settings hidden while notifications disabled');
$assert(is_file($root.'admin/sql/updates/mysql/0.6.13.sql'),'versioned no-DB-change upgrade');
echo "MM613_CONDITIONAL_SETTINGS_PASS\n";
