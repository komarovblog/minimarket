<?php
/** Isolated MySQL 8.4 schema check, no real MiniMarket data touched. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM625_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz625_category_recommendations','zz625_categories'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
$ok=static function(bool $yes,string $msg):void{
 if(!$yes)throw new RuntimeException('MM625_DB_FAIL: '.$msg);
};
try{
 $db->query('CREATE TABLE zz625_categories(id INT UNSIGNED PRIMARY KEY,parent_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB');
 $db->query('INSERT INTO zz625_categories VALUES(1,0),(2,1),(3,0)');
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.25.sql');
 $sql=strtr($sql,[
  '#__minimarket_category_recommendations'=>'zz625_category_recommendations',
  '#__minimarket_categories'=>'zz625_categories',
  'fk_mm_cat_recommendation'=>'fk_zz625_cat_recommendation'
 ]);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $st)if(trim($st)!=='')$db->query(trim($st));
 $ok((int)$db->query('SELECT COUNT(*) FROM zz625_category_recommendations')->fetch_row()[0]===0,
  'no fabricated policy for existing categories');
 $db->query("INSERT INTO zz625_category_recommendations
  (category_id,similar_scope,similar_categories,similar_attributes,together_scope,together_categories,updated_at)
  VALUES(1,'selected','2,3','4,5','own','',UTC_TIMESTAMP())");
 $db->query("UPDATE zz625_category_recommendations SET together_scope='selected',together_categories='3'
  WHERE category_id=1");
 $row=$db->query('SELECT similar_scope,similar_categories,similar_attributes,together_categories
  FROM zz625_category_recommendations WHERE category_id=1')->fetch_assoc();
 $ok($row['similar_scope']==='selected'&&$row['similar_categories']==='2,3'
  &&$row['similar_attributes']==='4,5'&&$row['together_categories']==='3',
  'both recommendations are independent');
 try{$db->query("INSERT INTO zz625_category_recommendations(category_id,updated_at)
   VALUES(999,UTC_TIMESTAMP())");$ok(false,'orphan category accepted');}
 catch(mysqli_sql_exception){$ok(true,'foreign key rejects orphan');}
 try{$db->query("INSERT INTO zz625_category_recommendations(category_id,updated_at)
   VALUES(1,UTC_TIMESTAMP())");$ok(false,'duplicate rule accepted');}
 catch(mysqli_sql_exception){$ok(true,'one row per category');}
 echo "MM625_CATEGORY_RECOMMENDATIONS_MYSQL84_PASS\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
