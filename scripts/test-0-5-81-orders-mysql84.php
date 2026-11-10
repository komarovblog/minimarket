<?php
/** MySQL 8.4 check for safe additive delivery snapshots and legacy data. */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
 || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
 throw new RuntimeException('MM81_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$db->query('DROP TABLE IF EXISTS mm81_orders');
try {
 $db->query("CREATE TABLE mm81_orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  checkout_key CHAR(32) NOT NULL,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(254) NOT NULL DEFAULT '',
  customer_comment TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  currency CHAR(3) NOT NULL DEFAULT 'RUB',
  total_cents BIGINT UNSIGNED NOT NULL,
  item_count INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("INSERT INTO mm81_orders
 (checkout_key,customer_name,phone,total_cents,item_count,created_at)
 VALUES ('00112233445566778899aabbccddeeff','Old buyer','+70000000000',125550,2,UTC_TIMESTAMP())");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.81.sql');
 $sql=str_replace('#__minimarket_orders','mm81_orders',$sql);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement) {
  $statement=trim($statement);if($statement==='')continue;
  $db->query($statement);
 }
 $historical=$db->query("SELECT items_total_cents,total_cents,delivery_fee_cents,delivery_method
 FROM mm81_orders WHERE id=1")->fetch_assoc();
 if ((int)$historical['items_total_cents']!==125550
  || (int)$historical['total_cents']!==125550
  || (int)$historical['delivery_fee_cents']!==0
  || $historical['delivery_method']!=='pickup') throw new RuntimeException('MM81_LEGACY_TOTAL_LOST');
 $db->query("INSERT INTO mm81_orders
 (checkout_key,customer_name,phone,total_cents,items_total_cents,delivery_fee_cents,
 delivery_method,delivery_city,delivery_address,delivery_rate_source,payment_method,
 item_count,created_at) VALUES
 ('ffeeddccbbaa00998877665544332211','Customer','+79990000001',175550,125550,50000,
 'courier','Москва','улица, дом 1','directory','bank_transfer',2,UTC_TIMESTAMP())");
 $new=$db->query("SELECT items_total_cents,total_cents,delivery_fee_cents,delivery_city,
 payment_method FROM mm81_orders WHERE id=2")->fetch_assoc();
 if ((int)$new['total_cents']!==(int)$new['items_total_cents']+(int)$new['delivery_fee_cents']
  || $new['delivery_city']!=='Москва'
  || $new['payment_method']!=='bank_transfer')throw new RuntimeException('MM81_SNAPSHOT_ERROR');
 echo "MM81_ORDERS_MYSQL84_PASS\n";
}finally{$db->query('DROP TABLE IF EXISTS mm81_orders');}
