<?php
/** MySQL 8.4 shadow test for additive order source and visibility migration. */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
 || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
 throw new RuntimeException('MM82_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
 getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
 'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['mm82_orders','mm82_attributes','mm82_order_options'] as $table)
 $db->query("DROP TABLE IF EXISTS `$table`");
try {
 $db->query("CREATE TABLE mm82_orders(
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 payment_method VARCHAR(24) NOT NULL DEFAULT 'on_receipt',
 customer_name VARCHAR(120) NOT NULL,
 total_cents BIGINT UNSIGNED NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $db->query("CREATE TABLE mm82_attributes(
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 color_display VARCHAR(12) NOT NULL DEFAULT 'both',
 title VARCHAR(255) NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $db->query("CREATE TABLE mm82_order_options(
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(100) NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $db->query("INSERT INTO mm82_orders(customer_name,total_cents) VALUES ('Historical',20000)");
 $db->query("INSERT INTO mm82_attributes(title) VALUES ('Weight')");
 $db->query("INSERT INTO mm82_order_options(code) VALUES ('color')");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.82.sql');
 foreach(['#__minimarket_orders'=>'mm82_orders','#__minimarket_attributes'=>'mm82_attributes',
          '#__minimarket_order_options'=>'mm82_order_options'] as $before=>$after)
  $sql=str_replace($before,$after,$sql);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement){
  $statement=trim($statement);
  if($statement!=='')$db->query($statement);
 }
 $order=$db->query('SELECT order_source,customer_name,total_cents FROM mm82_orders LIMIT 1')->fetch_assoc();
 $attr=$db->query('SELECT display_location,title FROM mm82_attributes LIMIT 1')->fetch_assoc();
 $option=$db->query('SELECT display_location,code FROM mm82_order_options LIMIT 1')->fetch_assoc();
 if ($order['order_source']!=='site' || $order['customer_name']!=='Historical'
  || (int)$order['total_cents']!==20000 ||
  $attr['display_location']!=='inherit' || $attr['title']!=='Weight'
  || $option['display_location']!=='inherit' || $option['code']!=='color') {
    throw new RuntimeException('MM82_ADDITIVE_MIGRATION_DAMAGED_DATA');
 }
 echo "MM82_MYSQL_MIGRATION_PASS\n";
}finally{
 foreach(['mm82_orders','mm82_attributes','mm82_order_options'] as $table)
  $db->query("DROP TABLE IF EXISTS `$table`");
}
