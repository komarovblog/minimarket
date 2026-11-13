<?php
/** Isolated MySQL 8.4 check: nonfiscal document and provider-neutral audit constraints. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM623_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz623_fiscal_events','zz623_fiscal_operations','zz623_documents',
 'zz623_returns','zz623_payments','zz623_orders'];
foreach($tables as $name)$db->query('DROP TABLE IF EXISTS '.$name);
$ok=static function(bool $yes,string $label):void{
 if(!$yes)throw new RuntimeException('MM623_MYSQL_FAIL: '.$label);
};
try{
 $db->query('CREATE TABLE zz623_orders(id INT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
 $db->query('CREATE TABLE zz623_payments(id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
 $db->query('CREATE TABLE zz623_returns(id INT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
 $db->query('INSERT INTO zz623_orders VALUES(11)');
 $db->query('INSERT INTO zz623_payments VALUES(22)');
 $db->query('INSERT INTO zz623_returns VALUES(33)');
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.23.sql');
 $sql=strtr($sql,[
 '#__minimarket_fiscal_operation_events'=>'zz623_fiscal_events',
 '#__minimarket_fiscal_operations'=>'zz623_fiscal_operations',
 '#__minimarket_order_documents'=>'zz623_documents',
 '#__minimarket_payment_records'=>'zz623_payments',
 '#__minimarket_returns'=>'zz623_returns',
 '#__minimarket_orders'=>'zz623_orders',
 'fk_mm_document_order'=>'fk_zz623_document_order',
 'fk_mm_fiscal_order'=>'fk_zz623_fiscal_order',
 'fk_mm_fiscal_payment'=>'fk_zz623_fiscal_payment',
 'fk_mm_fiscal_return'=>'fk_zz623_fiscal_return',
 'fk_mm_fiscal_event_operation'=>'fk_zz623_fiscal_event_operation'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $ok((int)$db->query('SELECT COUNT(*) FROM zz623_documents')->fetch_row()[0]===0,
 'migration does not fabricate documents');
 $ok((int)$db->query('SELECT COUNT(*) FROM zz623_fiscal_operations')->fetch_row()[0]===0,
 'migration does not fabricate fiscal receipts');
 $db->query("INSERT INTO zz623_documents
  (order_id,document_type,document_number,snapshot_json,snapshot_sha256,total_cents,currency,created_by,created_at)
  VALUES(11,'invoice','MM-INV-11','{}',REPEAT('a',64),1300,'RUB',7,UTC_TIMESTAMP())");
 try{$db->query("INSERT INTO zz623_documents
  (order_id,document_type,document_number,snapshot_json,snapshot_sha256,total_cents,currency,created_by,created_at)
  VALUES(11,'invoice','MM-INV-DUP','{}',REPEAT('a',64),1300,'RUB',7,UTC_TIMESTAMP())");
  $ok(false,'duplicate invoice accepted');}catch(mysqli_sql_exception){$ok(true,'unique order/type');}
 try{$db->query("INSERT INTO zz623_documents
  (order_id,document_type,document_number,snapshot_json,snapshot_sha256,total_cents,currency,created_by,created_at)
  VALUES(99,'invoice','MM-INV-99','{}',REPEAT('a',64),1300,'RUB',7,UTC_TIMESTAMP())");
  $ok(false,'orphan invoice accepted');}catch(mysqli_sql_exception){$ok(true,'order FK');}
 $db->query("INSERT INTO zz623_fiscal_operations
  (order_id,payment_record_id,operation_type,provider_code,request_key,status,created_at)
  VALUES(11,22,'sale','test-provider',REPEAT('b',64),'pending',UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz623_fiscal_events
  (fiscal_operation_id,event_key,status,occurred_at)
  VALUES(1,REPEAT('c',64),'pending',UTC_TIMESTAMP())");
 try{$db->query("INSERT INTO zz623_fiscal_events
  (fiscal_operation_id,event_key,status,occurred_at)
  VALUES(1,REPEAT('c',64),'pending',UTC_TIMESTAMP())");
  $ok(false,'duplicate fiscal event accepted');}catch(mysqli_sql_exception){$ok(true,'unique event idempotency');}
 try{$db->query("INSERT INTO zz623_fiscal_operations
  (order_id,operation_type,provider_code,request_key,status,created_at)
  VALUES(11,'sale','test-provider',REPEAT('b',64),'pending',UTC_TIMESTAMP())");
  $ok(false,'duplicate fiscal request accepted');}catch(mysqli_sql_exception){$ok(true,'unique fiscal request');}
 echo "MM623_DOCUMENTS_MYSQL84_PASS\n";
}finally{
 foreach($tables as $name)$db->query('DROP TABLE IF EXISTS '.$name);
 $db->close();
}
