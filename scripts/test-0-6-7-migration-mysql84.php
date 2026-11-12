<?php
/** Disposable MySQL 8.4 migration test: existing shipment survives, second is allowed. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM067_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$names=['zz67_return_items','zz67_returns','zz67_shipments','zz67_order_items','zz67_orders'];
foreach($names as $name)$db->query('DROP TABLE IF EXISTS '.$name);
$n=0;
$check=static function(bool $yes,string $label)use(&$n):void {
 ++$n;if(!$yes)throw new RuntimeException('MM067_MYSQL_FAIL: '.$label);
};
try {
 $db->query("CREATE TABLE zz67_orders (
    id INT UNSIGNED NOT NULL PRIMARY KEY,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
    payment_status VARCHAR(16) NOT NULL DEFAULT 'unpaid'
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("CREATE TABLE zz67_order_items (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("CREATE TABLE zz67_shipments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    carrier VARCHAR(16) NOT NULL DEFAULT 'own',
    status VARCHAR(16) NOT NULL DEFAULT 'new',
    UNIQUE KEY idx_mm_shipment_one_per_order(order_id),
    CONSTRAINT fk_mm67_shipping_order FOREIGN KEY(order_id)
     REFERENCES zz67_orders(id) ON DELETE RESTRICT
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("INSERT INTO zz67_orders(id,status,payment_status) VALUES(11,'confirmed','paid')");
 $db->query("INSERT INTO zz67_order_items VALUES(25,11,2)");
 $db->query("INSERT INTO zz67_shipments(order_id,carrier,status) VALUES(11,'cdek','delivered')");
 $sql=file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.7.sql');
 $replace=[
  '#__minimarket_return_items'=>'zz67_return_items',
  '#__minimarket_returns'=>'zz67_returns',
  '#__minimarket_shipments'=>'zz67_shipments',
  '#__minimarket_order_items'=>'zz67_order_items',
  '#__minimarket_orders'=>'zz67_orders',
  'fk_mm_return_order'=>'fk_mm67_return_order',
  'fk_mm_return_shipment'=>'fk_mm67_return_shipment',
  'fk_mm_return_item_return'=>'fk_mm67_return_item_return',
  'fk_mm_return_item_order_item'=>'fk_mm67_return_item_order_item'
 ];
 $sql=strtr($sql,$replace);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $old=$db->query("SELECT s.order_id,s.status,s.reason,o.status AS order_status,
   o.payment_status,o.payment_reference,o.payment_url
   FROM zz67_shipments s JOIN zz67_orders o ON o.id=s.order_id WHERE s.id=1")->fetch_assoc();
 $check($old!==null&&$old['order_id']==='11'
    &&$old['status']==='delivered'&&$old['reason']==='initial'
    &&$old['payment_status']==='paid'&&$old['order_status']==='confirmed'
    &&$old['payment_reference']===''&&$old['payment_url']==='',
    'legacy order, existing delivery and payment status unchanged');
 $db->query("INSERT INTO zz67_shipments(order_id,carrier,status,reason)
   VALUES(11,'own','new','replacement')");
 $check((int)$db->query("SELECT COUNT(*) FROM zz67_shipments WHERE order_id=11")
   ->fetch_row()[0]===2,'second delivery with independent reason');
 $db->query("INSERT INTO zz67_returns(order_id,shipment_id,reason,status,
   note,resolution,created_at) VALUES(11,1,'defective','new',
   'broken item','pending',UTC_TIMESTAMP())");
 $id=(int)$db->insert_id;
 $db->query("INSERT INTO zz67_return_items(return_id,order_item_id,quantity) VALUES($id,25,1)");
 $row=$db->query("SELECT r.reason,r.status,r.shipment_id,ri.quantity
   FROM zz67_returns r JOIN zz67_return_items ri ON ri.return_id=r.id
   WHERE r.id=$id")->fetch_assoc();
 $check($row!==null&&$row['reason']==='defective'&&$row['status']==='new'
   &&$row['shipment_id']==='1'&&$row['quantity']==='1',
   'defective item attached to original shipment');
 try{
  $db->query("INSERT INTO zz67_returns(order_id,shipment_id,reason,status,
     created_at) VALUES(11,999,'defective','new',UTC_TIMESTAMP())");
  $check(false,'orphan shipment accepted');
 }catch(mysqli_sql_exception){$check(true,'foreign key rejects orphan shipment');}
 try{
  $db->query("INSERT INTO zz67_return_items(return_id,order_item_id,quantity)
     VALUES($id,999,1)");
  $check(false,'orphan order line accepted');
 }catch(mysqli_sql_exception){$check(true,'foreign key rejects unknown order line');}
 $check((int)$db->query('SELECT COUNT(*) FROM zz67_returns')->fetch_row()[0]===1,
  'failed inserts left existing return intact');
 echo "MM067_SCHEMA_MYSQL84_PASS: $n checks\n";
}finally{
 foreach($names as $name)$db->query('DROP TABLE IF EXISTS '.$name);
 $db->close();
}
