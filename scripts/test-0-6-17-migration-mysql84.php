<?php
/** Disposable 0.6.17 MySQL 8.4 upgrade, no destructive migrations. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM617_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
foreach(['zz617_points','zz617_orders'] as $name)$db->query('DROP TABLE IF EXISTS '.$name);
try{
 $db->query("CREATE TABLE zz617_orders (id INT UNSIGNED PRIMARY KEY,
 delivery_method VARCHAR(16) NOT NULL, delivery_address VARCHAR(512) NOT NULL) ENGINE=InnoDB");
 $db->query("INSERT INTO zz617_orders VALUES(1,'pickup','Old address from 2025')");
 $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.17.sql');
 $sql=strtr($sql,['#__minimarket_pickup_points'=>'zz617_points',
   '#__minimarket_orders'=>'zz617_orders']);
 $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$db->query(trim($statement));
 $before=$db->query('SELECT delivery_address,pickup_point_id FROM zz617_orders WHERE id=1')->fetch_assoc();
 if($before['delivery_address']!=='Old address from 2025'||$before['pickup_point_id']!==null)
    throw new RuntimeException('MM617_DB_LEGACY_SNAPSHOT_CHANGED');
 $db->query("INSERT INTO zz617_points(title,address,schedule,fee_cents,state,created_at)
  VALUES('Central','New street 10','10:00–20:00',12500,1,UTC_TIMESTAMP())");
 $id=(int)$db->insert_id;
 $db->query("INSERT INTO zz617_orders(id,delivery_method,delivery_address,pickup_point_id)
  VALUES(2,'pickup','New street 10',$id)");
 $db->query("UPDATE zz617_points SET address='Relocated site 5' WHERE id=$id");
 $new=$db->query('SELECT delivery_address,pickup_point_id FROM zz617_orders WHERE id=2')->fetch_assoc();
 if($new['delivery_address']!=='New street 10'||(int)$new['pickup_point_id']!==$id)
    throw new RuntimeException('MM617_DB_ORDER_ADDRESS_NOT_IMMUTABLE');
 echo "MM617_PICKUP_MYSQL84_PASS\n";
}finally{
 foreach(['zz617_points','zz617_orders'] as $name)$db->query('DROP TABLE IF EXISTS '.$name);
 $db->close();
}
