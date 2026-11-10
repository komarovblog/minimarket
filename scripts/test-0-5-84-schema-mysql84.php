<?php
/** Isolated MySQL 8.4 migration smoke: orders stay intact, shipments and codes. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1' ||
 getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM84_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['mm84_shipments','mm84_promo_codes','mm84_orders'] as $name)
 $db->query('DROP TABLE IF EXISTS `'.$name.'`');
try {
 $db->query("CREATE TABLE mm84_orders (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_source VARCHAR(16) NOT NULL DEFAULT 'site',
 status VARCHAR(20) NOT NULL DEFAULT 'new'
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("INSERT INTO mm84_orders (status) VALUES ('cancelled')");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.84.sql');
 $sql=str_replace(['#__minimarket_orders','#__minimarket_shipments',
  '#__minimarket_promo_codes','fk_mm_shipment_order'],
  ['mm84_orders','mm84_shipments','mm84_promo_codes','fk_mm84_shipment_order'],$sql);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement){
  $statement=trim($statement);if($statement!=='')$db->query($statement);
 }
 $old=$db->query("SELECT deleted_at,status FROM mm84_orders WHERE id=1")->fetch_assoc();
 if($old===null||$old['deleted_at']!==null||$old['status']!=='cancelled')
  throw new RuntimeException('MM84_LEGACY_ORDER_CHANGED');
 $db->query("INSERT INTO mm84_shipments
 (order_id,carrier,tracking_number,status,created_at)
 VALUES (1,'cdek','MANUAL-123','new',UTC_TIMESTAMP())");
 $db->query("INSERT INTO mm84_promo_codes
 (code,amount_cents,created_at) VALUES ('WELCOME100',10000,UTC_TIMESTAMP())");
 $shipment=$db->query("SELECT tracking_number FROM mm84_shipments WHERE order_id=1")->fetch_assoc();
 $promo=$db->query("SELECT amount_cents FROM mm84_promo_codes WHERE code='WELCOME100'")->fetch_assoc();
 if($shipment['tracking_number']!=='MANUAL-123' || (int)$promo['amount_cents']!==10000)
  throw new RuntimeException('MM84_NEW_TABLES_INVALID');
 $duplicate=false;
 try{$db->query("INSERT INTO mm84_shipments (order_id,created_at)
 VALUES (1,UTC_TIMESTAMP())");}catch(mysqli_sql_exception $e){$duplicate=true;}
 if(!$duplicate)throw new RuntimeException('MM84_DUPLICATE_SHIPMENT_NOT_BLOCKED');
 echo "MM84_SCHEMA_MYSQL84_PASS\n";
} finally {
 foreach(['mm84_shipments','mm84_promo_codes','mm84_orders'] as $name)
  $db->query('DROP TABLE IF EXISTS `'.$name.'`');
}
