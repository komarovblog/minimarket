<?php
/** Real 0.6.15 migration on disposable zz615_* MySQL 8.4 tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM615_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz615_revisions','zz615_shipments','zz615_order_items','zz615_products'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$n=0;$check=static function(bool $v,string $x)use(&$n):void{
 ++$n;if(!$v)throw new RuntimeException('MM615_DB_FAIL: '.$x);
};
try{
 $db->query('CREATE TABLE zz615_products (
 id INT UNSIGNED PRIMARY KEY, price DECIMAL(15,2) NOT NULL DEFAULT 0.00) ENGINE=InnoDB');
 $db->query('CREATE TABLE zz615_order_items (
 id BIGINT UNSIGNED PRIMARY KEY, order_id INT UNSIGNED NOT NULL,
 line_cents BIGINT UNSIGNED NOT NULL) ENGINE=InnoDB');
 $db->query("CREATE TABLE zz615_shipments (
 id INT UNSIGNED PRIMARY KEY, order_id INT UNSIGNED NOT NULL,
 tracking_number VARCHAR(120) NOT NULL DEFAULT '') ENGINE=InnoDB");
 $db->query("INSERT INTO zz615_products VALUES(1,1500.00)");
 $db->query("INSERT INTO zz615_order_items VALUES(1,15,150000)");
 $db->query("INSERT INTO zz615_shipments VALUES(1,15,'TRK-1')");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.15.sql');
 $sql=strtr($sql,[
 '#__minimarket_products'=>'zz615_products',
 '#__minimarket_order_items'=>'zz615_order_items',
 '#__minimarket_shipments'=>'zz615_shipments',
 '#__minimarket_shipment_cost_revisions'=>'zz615_revisions',
 'fk_mm_shipment_cost_revision'=>'fk_mm615_revision'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $p=$db->query('SELECT price,cost_price FROM zz615_products WHERE id=1')->fetch_assoc();
 $check($p['price']==='1500.00'&&$p['cost_price']===null,
  'legacy selling price intact, unknown purchase cost stays NULL');
 $item=$db->query('SELECT line_cents,unit_cost_cents,line_cost_cents FROM zz615_order_items WHERE id=1')->fetch_assoc();
 $check($item['line_cents']==='150000'&&$item['unit_cost_cents']===null
  &&$item['line_cost_cents']===null,'legacy order has unknown historical cost');
 $shipment=$db->query('SELECT tracking_number,actual_cost_cents,cost_reference FROM zz615_shipments WHERE id=1')->fetch_assoc();
 $check($shipment['tracking_number']==='TRK-1'&&$shipment['actual_cost_cents']===null
  &&$shipment['cost_reference']==='','legacy shipping expense remains unknown');
 $db->query("UPDATE zz615_products SET cost_price=0.00 WHERE id=1");
 $check($db->query('SELECT cost_price FROM zz615_products WHERE id=1')->fetch_row()[0]==='0.00',
  'explicit zero cost distinct from unknown');
 $db->query("UPDATE zz615_shipments
   SET actual_cost_cents=12345,cost_reference='ACT-123',cost_confirmed_at=UTC_TIMESTAMP(),
   cost_confirmed_by=1 WHERE id=1");
 $db->query("INSERT INTO zz615_revisions
  (shipment_id,previous_cost_cents,confirmed_cost_cents,evidence_reference,actor_user_id,created_at)
  VALUES(1,NULL,12345,'ACT-123',1,UTC_TIMESTAMP())");
 $db->query("UPDATE zz615_shipments SET actual_cost_cents=11995,cost_reference='ACT-124' WHERE id=1");
 $db->query("INSERT INTO zz615_revisions
  (shipment_id,previous_cost_cents,confirmed_cost_cents,evidence_reference,actor_user_id,created_at)
  VALUES(1,12345,11995,'ACT-124',1,UTC_TIMESTAMP())");
 $current=(int)$db->query('SELECT actual_cost_cents FROM zz615_shipments WHERE id=1')->fetch_row()[0];
 $history=(int)$db->query('SELECT COUNT(*) FROM zz615_revisions WHERE shipment_id=1')->fetch_row()[0];
 $check($current===11995&&$history===2,'sum current cost once, two revisions retained');
 try{
  $db->query("INSERT INTO zz615_revisions
   (shipment_id,confirmed_cost_cents,evidence_reference,actor_user_id,created_at)
   VALUES(999,123,'BAD',1,UTC_TIMESTAMP())");
  $check(false,'orphan revision accepted');
 }catch(mysqli_sql_exception){$check(true,'foreign key rejects orphan expense history');}
 echo "MM615_COMMERCE_MYSQL84_PASS: $n checks\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
