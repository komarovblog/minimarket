<?php
/** Disposable MySQL 8.4 checkout schema smoke. No real Joomla DB changes. */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
    throw new RuntimeException('MM78_SQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$db->query('DROP TABLE IF EXISTS mm78_order_items');
$db->query('DROP TABLE IF EXISTS mm78_orders');
try {
    $sql=file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.78.sql');
    if (!$sql) throw new RuntimeException('MM78_SCHEMA_MISSING');
    $sql=str_replace('#__minimarket_','mm78_',$sql);
    // MySQL foreign key constraint names are schema-global. The canonical
    // fresh install already has this name; use a distinct shadow test name.
    $sql=str_replace('fk_mm_order_line_parent','fk_mm78_order_line_parent',$sql);
    $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
    foreach(explode(';',$sql) as $statement){
        $statement=trim($statement);
        if($statement==='')continue;
        $prepared=$db->prepare($statement);
        $prepared->execute();
        $prepared->close();
    }
    $db->begin_transaction();
    $db->query("INSERT INTO mm78_orders
        (checkout_key,customer_name,phone,email,customer_comment,
        status,currency,total_cents,item_count,created_at)
        VALUES ('11223344556677889900aabbccddeeff','Тестовый покупатель',
        '+79001234567','','','new','RUB',500100,3,UTC_TIMESTAMP())");
    $id=$db->insert_id;
    $db->query("INSERT INTO mm78_order_items
        (order_id,product_id,variant_id,sku,title,unit_cents,quantity,line_cents)
        VALUES ($id,5,9,'SKU-M','Variant',150050,2,300100),
               ($id,8,0,'SET-8','Bundle',200000,1,200000)");
    $db->commit();
    $count=(int)$db->query("SELECT COUNT(*) FROM mm78_order_items WHERE order_id=$id")->fetch_row()[0];
    $total=(int)$db->query("SELECT SUM(line_cents) FROM mm78_order_items WHERE order_id=$id")->fetch_row()[0];
    if($count!==2 || $total!==500100) throw new RuntimeException('MM78_SQL_SNAPSHOT_MISMATCH');
    try{
        $db->query("INSERT INTO mm78_orders
            (checkout_key,customer_name,phone,total_cents,item_count,created_at)
            VALUES ('11223344556677889900aabbccddeeff','duplicate',
            '+79001234567',500100,3,UTC_TIMESTAMP())");
        throw new RuntimeException('MM78_DUPLICATE_CHECKOUT_ALLOWED');
    }catch(mysqli_sql_exception $e){if($e->getCode()!==1062)throw $e;}
    $db->begin_transaction();
    $db->query("UPDATE mm78_orders SET status='confirmed' WHERE id=$id");
    $db->rollback();
    $status=$db->query("SELECT status FROM mm78_orders WHERE id=$id")->fetch_row()[0];
    if($status!=='new')throw new RuntimeException('MM78_TRANSACTION_ROLLBACK_FAILED');
    $db->query("DELETE FROM mm78_orders WHERE id=$id");
    $children=(int)$db->query("SELECT COUNT(*) FROM mm78_order_items WHERE order_id=$id")->fetch_row()[0];
    if($children!==0)throw new RuntimeException('MM78_CASCADE_FAILED');
    echo "MM78_ORDERS_SQL_MYSQL84_PASS: prepared DDL, snapshots, unique nonce, rollback, FK\n";
}finally{
    $db->query('DROP TABLE IF EXISTS mm78_order_items');
    $db->query('DROP TABLE IF EXISTS mm78_orders');
}
