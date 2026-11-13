<?php
/** Real MySQL 8.4 migration of C08 using disposable recommendation tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new \RuntimeException('MM621_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['zz621_recommendation_links','zz621_recommendation_settings','zz621_products'] as $t)
 $db->query('DROP TABLE IF EXISTS '.$t);
$assert=static function(bool $yes,string $message):void{
 if(!$yes)throw new \RuntimeException('MM621_MYSQL_FAIL: '.$message);
};
try{
 $db->query("CREATE TABLE zz621_products(id INT UNSIGNED NOT NULL PRIMARY KEY,
 title VARCHAR(255) NOT NULL, state TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB");
 $db->query("INSERT INTO zz621_products VALUES(11,'First',1),(12,'Second',1),(13,'Third',0)");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.21.sql');
 $sql=strtr($sql,[
 '#__minimarket_recommendation_settings'=>'zz621_recommendation_settings',
 '#__minimarket_recommendation_links'=>'zz621_recommendation_links',
 '#__minimarket_products'=>'zz621_products',
 'fk_mm_recommendation_source'=>'fk_zz621_rec_source',
 'fk_mm_recommendation_link_source'=>'fk_zz621_link_source',
 'fk_mm_recommendation_link_target'=>'fk_zz621_link_target'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $assert((int)$db->query('SELECT COUNT(*) FROM zz621_recommendation_settings')->fetch_row()[0]===0,
   'legacy products have no fabricated recommendations');
 $db->query("INSERT INTO zz621_recommendation_settings VALUES
 (11,'manual','hybrid',UTC_TIMESTAMP())");
 $db->query("INSERT INTO zz621_recommendation_links
 (source_product_id,target_product_id,kind,sort_order) VALUES
 (11,12,'similar',0),(11,13,'together',0)");
 $row=$db->query('SELECT similar_mode,together_mode FROM zz621_recommendation_settings
  WHERE product_id=11')->fetch_assoc();
 $assert($row['similar_mode']==='manual'&&$row['together_mode']==='hybrid',
   'independent recommendation modes persisted');
 try {
  $db->query("INSERT INTO zz621_recommendation_links
   (source_product_id,target_product_id,kind) VALUES(11,12,'similar')");
  $assert(false,'duplicate edge accepted');
 }catch(mysqli_sql_exception){$assert(true,'unique edge enforced');}
 try {
  $db->query("INSERT INTO zz621_recommendation_links
   (source_product_id,target_product_id,kind) VALUES(11,999,'similar')");
  $assert(false,'orphan target accepted');
 }catch(mysqli_sql_exception){$assert(true,'product foreign key enforced');}
 echo "MM621_RECOMMENDATIONS_MYSQL84_PASS\n";
}finally {
 foreach(['zz621_recommendation_links','zz621_recommendation_settings','zz621_products'] as $t)
  $db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
