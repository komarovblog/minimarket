<?php
/** MySQL 8.4 isolated migration for MiniMarket 0.6.22. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM622_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz622_revisions','zz622_plans'];
foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
$ok=static function(bool $value,string $label):void{
 if(!$value)throw new RuntimeException('MM622_MYSQL84_FAIL: '.$label);
};
try{
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.22.sql');
 $sql=strtr($sql,['#__minimarket_sales_plan_revisions'=>'zz622_revisions',
   '#__minimarket_sales_plans'=>'zz622_plans',
   'fk_mm_sales_plan_revision'=>'fk_mm622_plan_rev']);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)
  if(trim($statement)!=='')$db->query(trim($statement));
 $db->query('INSERT INTO zz622_plans VALUES(2026,11,100000,1,UTC_TIMESTAMP())');
 $db->query('INSERT INTO zz622_revisions
  (period_year,period_month,previous_target_cents,target_goods_cents,actor_user_id,created_at)
  VALUES(2026,11,NULL,100000,1,UTC_TIMESTAMP())');
 $row=$db->query('SELECT target_goods_cents FROM zz622_plans
  WHERE period_year=2026 AND period_month=11')->fetch_row();
 $ok((int)$row[0]===100000,'new monthly goal saved in cents');
 $db->query('UPDATE zz622_plans SET target_goods_cents=125000 WHERE period_year=2026 AND period_month=11');
 $db->query('INSERT INTO zz622_revisions
  (period_year,period_month,previous_target_cents,target_goods_cents,actor_user_id,created_at)
  VALUES(2026,11,100000,125000,2,UTC_TIMESTAMP())');
 $audit=$db->query('SELECT previous_target_cents,target_goods_cents FROM zz622_revisions
  WHERE period_year=2026 AND period_month=11 ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 $ok(count($audit)===2&&$audit[0]['previous_target_cents']===null
  &&(int)$audit[1]['previous_target_cents']===100000
  &&(int)$audit[1]['target_goods_cents']===125000,'immutable revisions preserved');
 try{
  $db->query('INSERT INTO zz622_plans VALUES(2026,11,150000,2,UTC_TIMESTAMP())');
  $ok(false,'duplicate month accepted');
 }catch(mysqli_sql_exception){$ok(true,'unique period');}
 try{
  $db->query('INSERT INTO zz622_revisions
   (period_year,period_month,previous_target_cents,target_goods_cents,actor_user_id,created_at)
   VALUES(2040,1,NULL,100000,3,UTC_TIMESTAMP())');
  $ok(false,'orphan revision accepted');
 }catch(mysqli_sql_exception){$ok(true,'foreign key enforced');}
 $count=(int)$db->query('SELECT COUNT(*) FROM zz622_plans')->fetch_row()[0];
 $ok($count===1,'no fabricated additional plan rows');
 echo "MM622_PLANFACT_MYSQL84_PASS\n";
}finally{
 foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
 $db->close();
}
