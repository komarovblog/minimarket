<?php
/** Fresh MiniMarket 0.5.73 catalog and repeated demo, MySQL 8.4 only. */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
    fwrite(STDERR,"CANONICAL_FRESH_REFUSED\n");exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',
    getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
function run73(mysqli $db,string $sql,string $stage):void {
    try{
        $db->multi_query($sql);
        do{
            $result=$db->store_result();
            if($result instanceof mysqli_result)$result->free();
            if(!$db->more_results())break;
        }while($db->next_result());
    }catch(\Throwable $e) {
        throw new RuntimeException('CANONICAL_FRESH_SQL_'.$stage.': '.$e->getMessage(),0,$e);
    }
}
function count73(mysqli $db,string $suffix):int {
    return (int)$db->query('SELECT COUNT(*) FROM nnop3_minimarket_'.$suffix)->fetch_row()[0];
}
function assert73(bool $ok,string $why):void{
    global $n;$n++;
    if(!$ok)throw new RuntimeException('CANONICAL_FRESH_FAIL: '.$why);
}
$n=0;
$schema=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/install.mysql.sql');
$demo=file_get_contents(__DIR__.'/../sample-data/minimarket_mysql84_nnop3_update_0_5_73.sql');
assert73(is_string($schema)&&is_string($demo),'both SQL files present');
foreach(['product_attribute_multi_values','input_mode','number_options','text_options'] as $legacy) {
    assert73(!str_contains($schema,$legacy),'fresh schema excludes '.$legacy);
    assert73(!str_contains($demo,$legacy),'current demo excludes '.$legacy);
}
run73($db,str_replace('#__','nnop3_',$schema),'schema');
$demo=str_replace('USE '.chr(96).'minimarket'.chr(96).';',
    'USE '.chr(96).'minimarket_ci_canonical73'.chr(96).';',$demo);
run73($db,$demo,'seed');
foreach(['attributes'=>12,'attribute_values'=>52,
    'product_attribute_options'=>496,'product_attribute_scalars'=>462] as $name=>$expected) {
    assert73(count73($db,$name)===$expected,$name.' total');
}
assert73($db->query("SHOW TABLES LIKE 'nnop3_minimarket_product_attribute_multi_values'")->num_rows===0,
    'no retired table');
foreach(['input_mode','number_options','text_options'] as $col){
    assert73($db->query("SHOW COLUMNS FROM nnop3_minimarket_attributes LIKE '$col'")->num_rows===0,
        'no old metadata '.$col);
}
assert73((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_attributes
 WHERE value_source NOT IN ('manual','preset')")->fetch_row()[0]===0,'all sources canonical');
assert73((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_attribute_values
 WHERE id BETWEEN 301 AND 313")->fetch_row()[0]===13,'original color IDs retained');
assert73(count73($db,'products')>=70 && count73($db,'categories')>=20,
    'full catalog present');
$before=count73($db,'products');
$db->query('UPDATE nnop3_minimarket_products SET price=1234.56 WHERE id=1001');
run73($db,$demo,'repeat');
assert73(count73($db,'products')===$before,'no duplicate products');
assert73(count73($db,'product_attribute_options')===496,'no duplicate dictionary links');
assert73((float)$db->query('SELECT price FROM nnop3_minimarket_products WHERE id=1001')
    ->fetch_row()[0]===1234.56,'manual price retained');
echo "CANONICAL_FRESH_MYSQL84_OK: $n checks\n";
