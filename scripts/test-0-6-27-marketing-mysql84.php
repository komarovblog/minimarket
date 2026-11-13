<?php
/** Real MySQL 8.4 isolated migration for marketing drafts, templates and admin inbox. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM627_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz627_flow_steps','zz627_flows','zz627_templates','zz627_inbox'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$ok=static function(bool $yes,string $why):void{if(!$yes)throw new RuntimeException('MM627_DB_FAIL: '.$why);};
try{
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.27.sql');
 $sql=strtr($sql,[
  '#__minimarket_marketing_flow_steps'=>'zz627_flow_steps',
  '#__minimarket_marketing_flows'=>'zz627_flows',
  '#__minimarket_message_templates'=>'zz627_templates',
  '#__minimarket_admin_inbox_reads'=>'zz627_inbox',
  'fk_mm_flow_step'=>'fk_zz627_flow_step',
  'fk_mm_flow_template'=>'fk_zz627_flow_template'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)
  if(trim($statement)!=='')$db->query(trim($statement));
 $db->query("INSERT INTO zz627_templates
   (id,title,audience,event_code,channel_code,subject,body,state,created_by,created_at)
   VALUES(7,'Recovery','marketing','cart_abandoned','email','Back?','Come back',1,2,UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz627_flows
   (id,title,trigger_code,state,stop_on_order,created_by,created_at)
   VALUES(9,'Recovery chain','cart_abandoned','draft',1,2,UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz627_flow_steps(flow_id,step_no,delay_minutes,template_id)
   VALUES(9,1,60,7),(9,2,1440,7)");
 $ok((int)$db->query('SELECT COUNT(*) FROM zz627_flow_steps WHERE flow_id=9')->fetch_row()[0]===2,
  'ordered marketing sequence stored');
 try{$db->query("INSERT INTO zz627_flow_steps(flow_id,step_no,delay_minutes,template_id)
   VALUES(9,1,150,7)");$ok(false,'duplicate step accepted');}
 catch(mysqli_sql_exception){$ok(true,'unique step per sequence');}
 try{$db->query("INSERT INTO zz627_flow_steps(flow_id,step_no,delay_minutes,template_id)
   VALUES(9,3,250,999)");$ok(false,'orphan template accepted');}
 catch(mysqli_sql_exception){$ok(true,'cannot reference missing template');}
 $db->query("INSERT INTO zz627_inbox(user_id,event_kind,event_id,read_at)
   VALUES(2,'order',12,UTC_TIMESTAMP()),(3,'order',12,UTC_TIMESTAMP())");
 $db->query("DELETE FROM zz627_inbox WHERE user_id=2 AND event_kind='order' AND event_id=12");
 $ok((int)$db->query("SELECT COUNT(*) FROM zz627_inbox WHERE user_id=3")->fetch_row()[0]===1
  &&(int)$db->query('SELECT COUNT(*) FROM zz627_inbox')->fetch_row()[0]===1,
  'personal read receipts independent');
 $ok($db->query('SELECT state FROM zz627_flows WHERE id=9')->fetch_row()[0]==='draft',
  'no active mailer without safe dispatcher');
 echo "MM627_MARKETING_MYSQL84_PASS\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
