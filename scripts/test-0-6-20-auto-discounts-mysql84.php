<?php
/** MySQL 8.4: additive 0.6.20 migration on disposable zz620_* tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM620_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['zz620_auto_discounts','zz620_orders'] as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$check=static function(bool $ok,string $name):void{
 if(!$ok)throw new RuntimeException('MM620_MYSQL_FAIL: '.$name);
};
try{
 $db->query("CREATE TABLE zz620_orders (
 id INT UNSIGNED NOT NULL PRIMARY KEY,
 promo_code_id INT UNSIGNED NULL,
 promo_code VARCHAR(64) NOT NULL DEFAULT '',
 discount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB");
 $db->query("INSERT INTO zz620_orders VALUES(7,2,'NOVEMBER',10000)");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.20.sql');
 $sql=strtr($sql,['#__minimarket_auto_discounts'=>'zz620_auto_discounts',
                  '#__minimarket_orders'=>'zz620_orders']);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $order=$db->query('SELECT promo_code,discount_cents,auto_discount_id,auto_discount_title
   FROM zz620_orders WHERE id=7')->fetch_assoc();
 $check($order['promo_code']==='NOVEMBER'&&$order['discount_cents']==='10000'
  &&$order['auto_discount_id']===null&&$order['auto_discount_title']==='',
  'historical coupon order preserved and no fake automatic discount');
 $db->query("INSERT INTO zz620_auto_discounts
 (title,state,discount_type,basis_points,applies_to,target_ids_json,created_at)
 VALUES('Пятница',1,'percent',1250,'category','[4,5]',UTC_TIMESTAMP())");
 $id=$db->insert_id;
 $db->query("UPDATE zz620_orders SET auto_discount_id=$id,auto_discount_title='Пятница',
   promo_code='',promo_code_id=NULL WHERE id=7");
 $q=$db->query('SELECT auto_discount_id,auto_discount_title,discount_cents FROM zz620_orders WHERE id=7')->fetch_assoc();
 $check((int)$q['auto_discount_id']===$id&&$q['auto_discount_title']==='Пятница'
  &&$q['discount_cents']==='10000','explicit rule snapshot stored independently');
 echo "MM620_AUTO_DISCOUNTS_MYSQL84_PASS\n";
}finally{
 foreach(['zz620_auto_discounts','zz620_orders'] as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
