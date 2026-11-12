<?php
/** MiniMarket 0.5.97: checkout extensions, native UI and incremental catalog. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'site/src/Service/CheckoutExtraFields.php';
require $root.'site/src/Service/CheckoutSettings.php';
use MiniMarket\Component\Minimarket\Site\Service\CheckoutExtraFields;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
$n=0;
$check=static function(bool $true,string $label)use(&$n):void{
    ++$n;if(!$true)throw new RuntimeException('MM97_FAIL: '.$label);
};
$reject=static function(callable $call,string $label)use($check):void{
    try{$call();$check(false,$label);}catch(DomainException){$check(true,$label);}
};
$p=new class {
    public array $data=[
        'checkout_custom_fields'=>[
            ['code'=>'entrance','title'=>'Entrance','type'=>'text','required'=>1,'mask'=>'AA-99','state'=>1],
            ['code'=>'extra_color','title'=>'Color','type'=>'select','required'=>0,'options'=>"Blue\nRed",'state'=>1]
        ],
        'checkout_phone_mode'=>'optional'
    ];
    public function get(string $key,mixed $default=null):mixed {return $this->data[$key]??$default;}
};
$defs=CheckoutExtraFields::definitions($p);
$check(count($defs)===2 && $defs['entrance']['required'],'declared custom fields');
$check(CheckoutExtraFields::maskMatches('AA-99','AB-23'),'mask accepts valid value');
$check(!CheckoutExtraFields::maskMatches('AA-99','AA-XX'),'mask rejects invalid value');
$valid=CheckoutExtraFields::validate(['entrance'=>'AB-23','extra_color'=>'Blue'],$defs);
$check($valid[0]['value']==='AB-23'&&$valid[1]['value']==='Blue','validated snapshot');
$reject(fn()=>CheckoutExtraFields::validate(['entrance'=>'XX-42','extra_color'=>'Purple'],$defs),
    'reject choice outside configured values');
$reject(fn()=>CheckoutExtraFields::validate(['entrance'=>'','extra_color'=>'Red'],$defs),
    'reject missing required field');
$check(!CheckoutSettings::skipBuyer($p, (object)['id'=>3,'name'=>'Test Buyer','email'=>'a@example.org','block'=>0],$defs),
    'required custom field blocks skipping buyer');
$p->data['checkout_custom_fields']=[];
$check(CheckoutSettings::skipBuyer($p,(object)['id'=>3,'name'=>'Test Buyer','email'=>'a@example.org','block'=>0],[]),
    'verified signed-in identity can skip optional buyer step');
$check(!CheckoutSettings::skipBuyer($p,(object)['id'=>0,'email'=>'','block'=>0],[]),
    'guests cannot bypass contact form');
$cfg=simplexml_load_file($root.'admin/config.xml');
$check($cfg!==false,'XML parses');
foreach(['mm_pagination_note','category_navigation_mode','category_products_per_page',
    'category_load_trigger'] as $key){
    $check(count($cfg->xpath('//fieldset[@name="basic"]/field[@name="'.$key.'"]'))===1,
        'pagination field '.$key);
}
foreach(['checkout_custom_fields','checkout_phone_mask','checkout_digital_description',
    'checkout_consent_text','checkout_show_login_button'] as $key){
    $check(count($cfg->xpath('//fieldset[@name="checkout"]/field[@name="'.$key.'"]'))===1,
        'checkout field '.$key);
}
$check(count($cfg->xpath('//fieldset[@name="basic"]/field[@name="mm_price_section"]'))===0,
    'no duplicate system attribute heading');
$check(!str_contains((string)file_get_contents($root.'media/js/admin-display-groups.js'),'createElement'),
    'no custom card panel injection');
$reviews=$cfg->xpath('//field[@name="reviews_moderation"]/option');
$check(array_map(static fn($x)=>(string)$x['value'],$reviews)===['1','0','2'],
    'backwards-compatible three-state review moderation');
$checkout=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$check(str_contains($checkout,'CheckoutExtraFields::validate')
    &&str_contains($checkout,"'custom_fields'"),
    'custom fields validated and passed to transactional order storage');
$storage=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$check(str_contains($storage,'#__minimarket_order_custom_values')
    &&str_contains($storage,'transactionCommit'),'persisted with order transaction');
$view=(string)file_get_contents($root.'site/tmpl/category/default.php');
$js=(string)file_get_contents($root.'media/js/faceted-catalog.js');
$check(str_contains($view,'data-mm-navigation-mode')
    &&str_contains($js,'incrementalNav')&&str_contains($js,'prepend:true'),
    'server pages and two-way product loading');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migrate=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.97.sql');
$check(str_contains($schema,'#__minimarket_order_custom_values')
    &&str_contains($migrate,'#__minimarket_order_custom_values')
    &&!preg_match('/\\b(?:DROP\\s+TABLE|TRUNCATE\\s+TABLE|DELETE\\s+FROM)\\b/i',$migrate),
    'fresh schema and additive migration');
echo "MM97_PASS: $n assertions\n";
