<?php
/** Actual business-report SQL semantics on disposable MySQL 8.4 records. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
 throw new RuntimeException('MM619_DB_REFUSES_REAL_DATA');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',getenv('MM_CI_DB_USER')?:'',
 getenv('MM_CI_DB_PASSWORD')?:'','minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz619_items','zz619_shipments','zz619_orders','zz619_returns','zz619_return_items'];
foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
try{
 $db->query("CREATE TABLE zz619_orders (id INT PRIMARY KEY,status VARCHAR(20),
 payment_status VARCHAR(20),deleted_at DATETIME NULL,items_total_cents BIGINT,
 discount_cents BIGINT,delivery_fee_cents BIGINT,created_at DATETIME) ENGINE=InnoDB");
 $db->query("CREATE TABLE zz619_items (id INT PRIMARY KEY,order_id INT,product_id INT,title VARCHAR(80),
 quantity INT,line_cents BIGINT,line_cost_cents BIGINT NULL) ENGINE=InnoDB");
 $db->query("CREATE TABLE zz619_shipments (id INT PRIMARY KEY,order_id INT,actual_cost_cents BIGINT NULL) ENGINE=InnoDB");
 $db->query("CREATE TABLE zz619_returns(id INT PRIMARY KEY,reason VARCHAR(32),status VARCHAR(20),created_at DATETIME) ENGINE=InnoDB");
 $db->query("CREATE TABLE zz619_return_items(id INT PRIMARY KEY,return_id INT,quantity INT) ENGINE=InnoDB");
 $db->query("INSERT INTO zz619_orders VALUES
  (1,'completed','paid',NULL,10000,1000,500,'2026-11-01'),
  (2,'completed','paid',NULL,20000,0,0,'2026-11-02'),
  (3,'cancelled','unpaid',NULL,100000,0,0,'2026-11-03')");
 $db->query("INSERT INTO zz619_items VALUES(1,1,4,'A',1,10000,5000),(2,2,5,'B',2,20000,NULL),
 (3,3,4,'A',5,100000,25000)");
 $db->query("INSERT INTO zz619_shipments VALUES(1,1,300),(2,2,NULL)");
 $where="o.status='completed' AND o.payment_status='paid' AND o.deleted_at IS NULL";
 $base=' FROM zz619_orders o WHERE '.$where;
 $row=$db->query('SELECT SUM(items_total_cents-discount_cents) goods,COUNT(*) orders'.$base)->fetch_assoc();
 if((int)$row['goods']!==29000||(int)$row['orders']!==2)
  throw new RuntimeException('MM619_REVENUE_COUNTS_UNPAID');
 $cost=$db->query('SELECT SUM(CASE WHEN oi.line_cost_cents IS NULL THEN 1 ELSE 0 END) missing,'
  .'SUM(oi.line_cost_cents) cost FROM zz619_items oi INNER JOIN zz619_orders o ON o.id=oi.order_id'
  .' WHERE '.$where)->fetch_assoc();
 if((int)$cost['cost']!==5000||(int)$cost['missing']!==1)
  throw new RuntimeException('MM619_UNKNOWN_COST_REPORTED_AS_ZERO');
 $shipping=$db->query('SELECT SUM(CASE WHEN s.actual_cost_cents IS NULL THEN 1 ELSE 0 END) missing,'
  .'SUM(s.actual_cost_cents) cost FROM zz619_shipments s INNER JOIN zz619_orders o ON o.id=s.order_id'
  .' WHERE '.$where)->fetch_assoc();
 if((int)$shipping['cost']!==300||(int)$shipping['missing']!==1)
  throw new RuntimeException('MM619_SHIPMENT_UNKNOWN_COUNT');
 $db->query("INSERT INTO zz619_returns VALUES(1,'defective','new','2026-11-05')");
 $db->query("INSERT INTO zz619_return_items VALUES(1,1,2),(2,1,1)");
 $cases=$db->query('SELECT r.reason,COUNT(*) cases_count,SUM(ri.items_count) units'
  .' FROM zz619_returns r LEFT JOIN (SELECT return_id,SUM(quantity) items_count'
  .' FROM zz619_return_items GROUP BY return_id) ri ON ri.return_id=r.id'
  .' GROUP BY r.reason')->fetch_assoc();
 if((int)$cases['cases_count']!==1||(int)$cases['units']!==3)
  throw new RuntimeException('MM619_RETURN_CASE_DOUBLED');
 echo "MM619_REPORT_MYSQL84_PASS\n";
}finally{
 foreach($tables as $t)$db->query('DROP TABLE IF EXISTS '.$t);
 $db->close();
}
