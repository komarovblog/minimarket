<?php
/** Real MySQL 8.4: additive ticket migration and personal read signatures. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM624_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz624_queue_reads','zz624_tickets','zz624_shipments','zz624_returns'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$ok=static function(bool $yes,string $why):void{
 if(!$yes)throw new RuntimeException('MM624_MYSQL84_FAIL: '.$why);
};
try{
 $db->query('CREATE TABLE zz624_shipments(id INT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
 $db->query('CREATE TABLE zz624_returns(id INT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
 $db->query('CREATE TABLE zz624_tickets (
  id INT UNSIGNED NOT NULL PRIMARY KEY,order_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,subject VARCHAR(180) NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT "new",created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,updated_at DATETIME NULL
 ) ENGINE=InnoDB');
 $db->query("INSERT INTO zz624_shipments VALUES(77)");
 $db->query("INSERT INTO zz624_returns VALUES(88)");
 $db->query("INSERT INTO zz624_tickets
  (id,order_id,subject,status,created_by,created_at)
  VALUES(1,11,'Original','new',2,UTC_TIMESTAMP())");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.24.sql');
 $sql=strtr($sql,[
  '#__minimarket_queue_reads'=>'zz624_queue_reads',
  '#__minimarket_support_tickets'=>'zz624_tickets',
  '#__minimarket_shipments'=>'zz624_shipments',
  '#__minimarket_returns'=>'zz624_returns',
  'fk_mm_ticket_shipment'=>'fk_zz624_ticket_shipment',
  'fk_mm_ticket_return'=>'fk_zz624_ticket_return'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)
  if(trim($statement)!=='')$db->query(trim($statement));
 $row=$db->query('SELECT status,priority,shipment_id,return_id FROM zz624_tickets WHERE id=1')
   ->fetch_assoc();
 $ok($row['status']==='new'&&$row['priority']==='normal'
   &&$row['shipment_id']===null&&$row['return_id']===null,
   'existing ticket keeps identity and default priority');
 $db->query("UPDATE zz624_tickets
  SET priority='high',shipment_id=77,return_id=88 WHERE id=1");
 $row=$db->query('SELECT priority,shipment_id,return_id FROM zz624_tickets WHERE id=1')
   ->fetch_assoc();
 $ok($row['priority']==='high'&&(int)$row['shipment_id']===77
  &&(int)$row['return_id']===88,'genuine reference fields persist');
 try{
  $db->query('UPDATE zz624_tickets SET shipment_id=999 WHERE id=1');
  $ok(false,'orphan shipment accepted');
 }catch(mysqli_sql_exception){$ok(true,'shipment foreign key');}
 try{
  $db->query('UPDATE zz624_tickets SET return_id=999 WHERE id=1');
  $ok(false,'orphan return accepted');
 }catch(mysqli_sql_exception){$ok(true,'return foreign key');}
 $db->query("INSERT INTO zz624_queue_reads VALUES
  (10,'ticket',1,REPEAT('a',64),UTC_TIMESTAMP()),
  (20,'ticket',1,REPEAT('a',64),UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz624_queue_reads
  (user_id,source_kind,source_id,source_signature,read_at)
  VALUES(10,'ticket',1,REPEAT('b',64),UTC_TIMESTAMP())
  ON DUPLICATE KEY UPDATE source_signature=VALUES(source_signature),
   read_at=VALUES(read_at)");
 $records=$db->query("SELECT user_id,source_signature FROM zz624_queue_reads
  WHERE source_kind='ticket' AND source_id=1 ORDER BY user_id")->fetch_all(MYSQLI_ASSOC);
 $ok(count($records)===2&&$records[0]['source_signature']===str_repeat('b',64)
  &&$records[1]['source_signature']===str_repeat('a',64),
  'source state changes reset only current operator');
 $db->query("DELETE FROM zz624_queue_reads WHERE user_id=10
  AND source_kind='ticket' AND source_id=1 AND source_signature=REPEAT('b',64)");
 $ok((int)$db->query("SELECT COUNT(*) FROM zz624_queue_reads")->fetch_row()[0]===1,
  'mark unread never alters another operator receipt');
 echo "MM624_QUEUE_CRM_MYSQL84_PASS\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
