<?php
/** Disposable 0.6.18 MySQL 8.4 CRM/support relational contract. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM618_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz618_messages','zz618_tickets','zz618_notes','zz618_orders'];
foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
try {
 $db->query("CREATE TABLE zz618_orders(id INT UNSIGNED PRIMARY KEY,user_id INT UNSIGNED NOT NULL)
 ENGINE=InnoDB");
 $db->query("INSERT INTO zz618_orders VALUES(1,7)");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.18.sql');
 $sql=strtr($sql,[
 '#__minimarket_customer_notes'=>'zz618_notes',
 '#__minimarket_support_tickets'=>'zz618_tickets',
 '#__minimarket_support_messages'=>'zz618_messages',
 '#__minimarket_orders'=>'zz618_orders',
 'fk_mm_ticket_order'=>'fk_mm618_ticket_order',
 'fk_mm_support_message_ticket'=>'fk_mm618_message_ticket'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $db->query("INSERT INTO zz618_tickets(order_id,user_id,subject,status,created_by,created_at)
   VALUES(1,7,'Wrong delivery','new',10,UTC_TIMESTAMP())");
 $tid=(int)$db->insert_id;
 $db->query("INSERT INTO zz618_messages(ticket_id,body,internal,author_user_id,created_at)
  VALUES($tid,'Verify tracking',1,10,UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz618_notes(user_id,body,author_user_id,created_at)
  VALUES(7,'Call customer on Monday',10,UTC_TIMESTAMP())");
 $n=(int)$db->query('SELECT COUNT(*) FROM zz618_messages WHERE ticket_id='.$tid)->fetch_row()[0];
 if($n!==1)throw new RuntimeException('MM618_MESSAGES_MISSING');
 try {
  $db->query("INSERT INTO zz618_messages(ticket_id,body,author_user_id,created_at)
   VALUES(999,'orphan',10,UTC_TIMESTAMP())");
  throw new RuntimeException('MM618_ORPHAN_ACCEPTED');
 }catch(mysqli_sql_exception){}
 $db->query("UPDATE zz618_tickets SET status='closed' WHERE id=$tid");
 if($db->query('SELECT status FROM zz618_tickets WHERE id='.$tid)->fetch_row()[0]!=='closed')
  throw new RuntimeException('MM618_CANNOT_CLOSE_CASE');
 echo "MM618_CRM_MYSQL84_PASS\n";
}finally{
 foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
 $db->close();
}
