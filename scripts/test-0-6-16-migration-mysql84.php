<?php
/** Disposable MySQL 8.4 upgrade of legacy shipment into 0.6.16 model. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM616_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['zz616_events','zz616_shipments'] as $table)$db->query('DROP TABLE IF EXISTS '.$table);
$assert=static function(bool $ok,string $label):void{
 if(!$ok)throw new RuntimeException('MM616_DB_FAIL: '.$label);
};
try{
 $db->query("CREATE TABLE zz616_shipments(
 id INT UNSIGNED NOT NULL PRIMARY KEY,order_id INT UNSIGNED NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'ready') ENGINE=InnoDB");
 $db->query("INSERT INTO zz616_shipments(id,order_id,status) VALUES(7,42,'ready')");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.16.sql');
 $sql=strtr($sql,['#__minimarket_shipment_events'=>'zz616_events',
   '#__minimarket_shipments'=>'zz616_shipments',
   'fk_mm_shipment_event_parent'=>'fk_mm616_event_parent']);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $stmt)if(trim($stmt)!=='')$db->query(trim($stmt));
 $before=$db->query('SELECT status,problem_status,problem_code FROM zz616_shipments WHERE id=7')->fetch_assoc();
 $assert($before['status']==='ready'&&$before['problem_status']==='none'&&$before['problem_code']==='',
  'old status intact and no invented problem');
 $key='11111111111111111111111111111111';
 $db->query("INSERT INTO zz616_events
 (shipment_id,event_key,source,source_reference,event_code,occurred_at,created_at,actor_user_id)
 VALUES(7,'$key','manual','$key','handed_to_carrier',UTC_TIMESTAMP(),UTC_TIMESTAMP(),1)");
 try{
  $db->query("INSERT INTO zz616_events
  (shipment_id,event_key,source,source_reference,event_code,occurred_at,created_at)
  VALUES(7,'$key','manual','$key','handed_to_carrier',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
  $assert(false,'duplicate event accepted');
 }catch(mysqli_sql_exception){$assert(true,'duplicate event rejected');}
 try{
  $db->query("INSERT INTO zz616_events
  (shipment_id,event_key,source,source_reference,event_code,occurred_at,created_at)
  VALUES(99,'22222222222222222222222222222222','manual','another','note',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
  $assert(false,'orphan fact accepted');
 }catch(mysqli_sql_exception){$assert(true,'orphan fact rejected');}
 $db->query("UPDATE zz616_shipments SET problem_status='open',problem_code='delay' WHERE id=7");
 $row=$db->query("SELECT status,problem_status FROM zz616_shipments WHERE id=7")->fetch_assoc();
 $assert($row['status']==='ready'&&$row['problem_status']==='open','issue independent of order fulfillment');
 $assert((int)$db->query("SELECT COUNT(*) FROM zz616_events")->fetch_row()[0]===1,
  'one immutable event despite repeated insert');
 echo "MM616_MYSQL84_PASS\n";
}finally{
 foreach(['zz616_events','zz616_shipments'] as $table)$db->query('DROP TABLE IF EXISTS '.$table);
 $db->close();
}
