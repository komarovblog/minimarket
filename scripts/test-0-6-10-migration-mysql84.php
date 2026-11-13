<?php
/** Real MySQL 8.4 non-destructive migration test on disposable zz610_* tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM610_DB_TEST_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$names=['zz610_notifylog','zz610_prefs','zz610_allocations',
 'zz610_shipments','zz610_orders','zz610_stock'];
$n=0;
$check=static function(bool $yes,string $label)use(&$n):void{
 ++$n;if(!$yes)throw new RuntimeException('MM610_DB: '.$label);
};
foreach($names as $name)$db->query('DROP TABLE IF EXISTS '.$name);
try {
 $db->query("CREATE TABLE zz610_orders (
   id INT UNSIGNED NOT NULL PRIMARY KEY,
   status VARCHAR(20) NOT NULL DEFAULT 'new',
   payment_status VARCHAR(16) NOT NULL DEFAULT 'unpaid',
   fulfillment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
   created_at DATETIME NOT NULL
 ) ENGINE=InnoDB");
 $db->query("CREATE TABLE zz610_shipments (
   id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
   order_id INT UNSIGNED NOT NULL,
   status VARCHAR(16) NOT NULL DEFAULT 'new'
 ) ENGINE=InnoDB");
 $db->query("INSERT INTO zz610_orders VALUES
   (11,'new','unpaid','pending',UTC_TIMESTAMP()),
   (12,'confirmed','paid','shipped',UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz610_shipments(order_id,status) VALUES(11,'new')");
 $sql=file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.10.sql');
 $map=[
   '#__minimarket_notification_preferences'=>'zz610_prefs',
   '#__minimarket_notification_log'=>'zz610_notifylog',
   '#__minimarket_stock_allocations'=>'zz610_allocations',
   '#__minimarket_shipments'=>'zz610_shipments',
   '#__minimarket_orders'=>'zz610_orders',
   'fk_mm_stock_allocation_order'=>'fk_mm610_allocation',
   'fk_mm_notify_order'=>'fk_mm610_notify'
 ];
 $sql=strtr($sql,$map);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)
  if(trim($statement)!=='')$db->query(trim($statement));
 $old=$db->query("SELECT status,payment_status,fulfillment_status,
   reservation_state,reserved_until,accepted_at
   FROM zz610_orders WHERE id=11")->fetch_assoc();
 $check($old!==null&&$old['status']==='new'&&$old['payment_status']==='unpaid'
   &&$old['fulfillment_status']==='awaiting_assembly'&&$old['reservation_state']==='legacy'
   &&$old['reserved_until']===null&&$old['accepted_at']===null,
   'legacy order preserved without fictitious stock hold');
 $old2=$db->query("SELECT status,payment_status,fulfillment_status
    FROM zz610_orders WHERE id=12")->fetch_assoc();
 $check($old2['status']==='confirmed'&&$old2['payment_status']==='paid'
   &&$old2['fulfillment_status']==='in_transit',
   'paid historical order not marked completed');
 $s=$db->query("SELECT status FROM zz610_shipments WHERE id=1")->fetch_row();
 $check($s!==null&&$s[0]==='awaiting_assembly','old shipment updated safely');
 $db->query("INSERT INTO zz610_allocations
    (order_id,source_kind,source_id,quantity,created_at)
    VALUES(11,'variant',22,1,UTC_TIMESTAMP())");
 try{$db->query("INSERT INTO zz610_allocations
    (order_id,source_kind,source_id,quantity,created_at)
    VALUES(11,'variant',22,1,UTC_TIMESTAMP())");
    $check(false,'duplicate allocation incorrectly accepted');
 }catch(mysqli_sql_exception){$check(true,'unique order-stock source enforced');}
 try{$db->query("INSERT INTO zz610_allocations
    (order_id,source_kind,source_id,quantity,created_at)
    VALUES(999,'variant',22,1,UTC_TIMESTAMP())");
    $check(false,'orphan allocation incorrectly accepted');
 }catch(mysqli_sql_exception){$check(true,'orphan allocation blocked');}
 $db->query("INSERT INTO zz610_prefs
    VALUES(7,'pickup_ready','email',1,UTC_TIMESTAMP())");
 $prefs=$db->query("SELECT enabled FROM zz610_prefs
    WHERE user_id=7 AND event_code='pickup_ready'")->fetch_row();
 $check((int)$prefs[0]===1,'Joomla user preference stored');
 $db->query("INSERT INTO zz610_notifylog
    (order_id,event_code,channel_code,event_reference,created_at)
    VALUES(11,'pickup_ready','email','1',UTC_TIMESTAMP())");
 try{$db->query("INSERT INTO zz610_notifylog
    (order_id,event_code,channel_code,event_reference,created_at)
    VALUES(11,'pickup_ready','email','1',UTC_TIMESTAMP())");
    $check(false,'duplicate notification accepted');
 }catch(mysqli_sql_exception){$check(true,'idempotent send identity enforced');}
 $db->query("CREATE TABLE zz610_stock (id INT PRIMARY KEY,stock INT UNSIGNED NOT NULL)");
 $db->query("INSERT INTO zz610_stock VALUES(1,1)");
 $db->begin_transaction();
 $db->query("UPDATE zz610_stock SET stock=stock-1 WHERE id=1 AND stock>=1");
 $check($db->affected_rows===1,'first concurrent stock claim succeeds');
 $db->query("UPDATE zz610_stock SET stock=stock-1 WHERE id=1 AND stock>=1");
 $check($db->affected_rows===0,'second claim of final unit fails');
 $db->rollback();
 $check((int)$db->query("SELECT stock FROM zz610_stock WHERE id=1")->fetch_row()[0]===1,
   'rollback restores stock after failed multi-line checkout');
 echo "MM610_SCHEMA_MYSQL84_PASS: $n checks\n";
}finally{
 foreach($names as $name)$db->query('DROP TABLE IF EXISTS '.$name);
 $db->close();
}
