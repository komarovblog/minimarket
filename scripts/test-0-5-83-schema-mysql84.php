<?php
/** MySQL 8.4 isolated shadow migration test for new reviews/SEO tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
 ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM83_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['mm83_reviews','mm83_seo_filters','mm83_products','mm83_categories'] as $name)
 $db->query('DROP TABLE IF EXISTS `'.$name.'`');
try{
 $db->query("CREATE TABLE mm83_products (id INT UNSIGNED NOT NULL PRIMARY KEY)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("CREATE TABLE mm83_categories (id INT UNSIGNED NOT NULL PRIMARY KEY)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("INSERT INTO mm83_products VALUES (7)");
 $db->query("INSERT INTO mm83_categories VALUES (9)");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.83.sql');
 foreach(['#__minimarket_reviews'=>'mm83_reviews',
 '#__minimarket_seo_filters'=>'mm83_seo_filters',
 '#__minimarket_products'=>'mm83_products',
 '#__minimarket_categories'=>'mm83_categories',
 'fk_mm_review_product'=>'fk_mm83_review_product',
 'fk_mm_seofilter_category'=>'fk_mm83_seofilter_category'] as $from=>$to)
  $sql=str_replace($from,$to,$sql);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement){
  $statement=trim($statement);if($statement!=='')$db->query($statement);
 }
 $db->query("INSERT INTO mm83_reviews
 (review_key,product_id,user_id,created_by,author_name,rating,title,body,source,state,created_at)
 VALUES ('0123456789abcdef0123456789abcdef',7,0,1,'Test Customer',5,'Great','Customer review text','manual',0,UTC_TIMESTAMP())");
 $db->query("INSERT INTO mm83_seo_filters
 (category_id,alias,title,heading,filters_json,created_at)
 VALUES (9,'red-shoes','Red shoes','Red shoes','{\"price\":{\"min\":\"100\"}}',UTC_TIMESTAMP())");
 $rows=$db->query("SELECT (SELECT COUNT(*) FROM mm83_reviews) AS reviews,
 (SELECT COUNT(*) FROM mm83_seo_filters) AS pages")->fetch_assoc();
 if((int)$rows['reviews']!==1||(int)$rows['pages']!==1)
    throw new RuntimeException('MM83_SHADOW_MIGRATION_FAIL');
 $db->query("DELETE FROM mm83_products WHERE id=7");
 $db->query("DELETE FROM mm83_categories WHERE id=9");
 $remain=$db->query("SELECT (SELECT COUNT(*) FROM mm83_reviews) AS reviews,
 (SELECT COUNT(*) FROM mm83_seo_filters) AS pages")->fetch_assoc();
 if((int)$remain['reviews']!==0||(int)$remain['pages']!==0)
    throw new RuntimeException('MM83_CASCADE_MIGRATION_FAIL');
 echo "MM83_MYSQL84_REVIEWS_SEO_PASS\n";
}finally{
 foreach(['mm83_reviews','mm83_seo_filters','mm83_products','mm83_categories'] as $name)
  $db->query('DROP TABLE IF EXISTS `'.$name.'`');
}
