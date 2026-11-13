<?php
/** Genuine MySQL 8.4: sessions, active vs converted, 60m threshold and 30d retention. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM626_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz626_cart_snapshots','zz626_orders'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$ok=static function(bool $valid,string $msg):void{
 if(!$valid)throw new RuntimeException('MM626_DB_FAIL: '.$msg);
};
try{
 $db->query('CREATE TABLE zz626_orders(id INT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
 $db->query('INSERT INTO zz626_orders VALUES(4)');
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.26.sql');
 $sql=strtr($sql,[
  '#__minimarket_cart_snapshots'=>'zz626_cart_snapshots',
  '#__minimarket_orders'=>'zz626_orders',
  'fk_mm_cart_converted'=>'fk_zz626_cart_converted'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $command)if(trim($command)!=='')$db->query(trim($command));
 $db->query("INSERT INTO zz626_cart_snapshots
  (cart_key,user_id,items_json,item_units,cart_status,created_at,updated_at) VALUES
  (REPEAT('a',64),0,'{\"1:0\":2}',2,'active',
   DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 MINUTE),
   DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 MINUTE)),
  (REPEAT('b',64),7,'{\"2:0\":1}',1,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP()),
  (REPEAT('c',64),0,'{\"3:0\":1}',1,'active',
   DATE_SUB(UTC_TIMESTAMP(),INTERVAL 45 DAY),
   DATE_SUB(UTC_TIMESTAMP(),INTERVAL 45 DAY))");
 $pred="cart_status='active' AND item_units>0
  AND updated_at<=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 MINUTE)
  AND updated_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)";
 $n=(int)$db->query('SELECT COUNT(*) FROM zz626_cart_snapshots WHERE '.$pred)->fetch_row()[0];
 $ok($n===1,'only old unconverted and nonexpired active cart is abandoned');
 $db->query("UPDATE zz626_cart_snapshots SET cart_status='converted',converted_order_id=4,
  updated_at=UTC_TIMESTAMP() WHERE cart_key=REPEAT('a',64)");
 $n=(int)$db->query('SELECT COUNT(*) FROM zz626_cart_snapshots WHERE '.$pred)->fetch_row()[0];
 $ok($n===0,'converted cart cannot trigger abandoned messaging');
 try{
  $db->query("UPDATE zz626_cart_snapshots SET converted_order_id=123 WHERE cart_key=REPEAT('a',64)");
  $ok(false,'orphan order accepted');
 }catch(mysqli_sql_exception){$ok(true,'genuine foreign key');}
 $db->query("DELETE FROM zz626_cart_snapshots
  WHERE updated_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY) LIMIT 100");
 $ok((int)$db->query('SELECT COUNT(*) FROM zz626_cart_snapshots')->fetch_row()[0]===2,
 'bounded 30-day retention cleanup');
 echo "MM626_CARTS_MYSQL84_PASS\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
