<?php
/** 0.5.99 genuine additive upgrade against disposable MySQL 8.4 shadow tables. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
    ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
    throw new RuntimeException('MM99_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$tables=['zz99_digital_grants','zz99_order_events','zz99_order_items','zz99_orders','zz99_products'];
foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
$n=0;
$check=static function(bool $ok,string $why)use(&$n):void {
    ++$n;if(!$ok)throw new RuntimeException('MM99_MYSQL_FAILED: '.$why);
};
try{
    $db->query("CREATE TABLE zz99_products (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        delivery_type VARCHAR(12) NOT NULL DEFAULT 'physical',
        title VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE zz99_orders (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        customer_name VARCHAR(120) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE zz99_order_items (
        id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        sku VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT INTO zz99_products VALUES (7,'physical','Old chair')");
    $db->query("INSERT INTO zz99_orders VALUES (11,'confirmed','Existing buyer')");
    $db->query("INSERT INTO zz99_order_items VALUES (21,11,7,'CHAIR-7','Old chair')");
    $migration=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.99.sql');
    $migration=str_replace([
        '#__minimarket_products','#__minimarket_orders','#__minimarket_order_items',
        '#__minimarket_digital_grants','#__minimarket_order_events',
        'fk_mm_digital_grant_item','fk_mm_order_event_parent'
    ],[
        'zz99_products','zz99_orders','zz99_order_items',
        'zz99_digital_grants','zz99_order_events',
        'fk_mm99_digital_grant_item','fk_mm99_order_event_parent'
    ],$migration);
    $migration=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$migration);
    foreach(explode(';',$migration) as $sql){
        $sql=trim($sql);
        if($sql!=='')$db->query($sql);
    }
    $product=$db->query("SELECT title,delivery_type,digital_asset_key,digital_filename
        FROM zz99_products WHERE id=7")->fetch_assoc();
    $check($product!==null && $product['title']==='Old chair'
        &&$product['delivery_type']==='physical'
        &&$product['digital_asset_key']==='' &&$product['digital_filename']==='',
        'existing product preserved with safe defaults');
    $order=$db->query("SELECT status,customer_name,payment_status,paid_at,fulfillment_status
        FROM zz99_orders WHERE id=11")->fetch_assoc();
    $check($order!==null&&$order['status']==='confirmed'
        &&$order['customer_name']==='Existing buyer'
        &&$order['payment_status']==='unpaid'&&$order['paid_at']===null
        &&$order['fulfillment_status']==='pending',
        'old orders must never be silently treated as paid');
    $item=$db->query("SELECT sku,title,delivery_type,digital_asset_key
        FROM zz99_order_items WHERE id=21")->fetch_assoc();
    $check($item!==null&&$item['sku']==='CHAIR-7'
        &&$item['delivery_type']==='physical'
        &&$item['digital_asset_key']==='', 'previous order snapshots intact');
    $db->query("INSERT INTO zz99_digital_grants
        (order_item_id,token_nonce,expires_at,created_at)
        VALUES (21,REPEAT('a',32),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY),UTC_TIMESTAMP())");
    $db->query("INSERT INTO zz99_order_events
        (order_id,actor_user_id,event_type,old_value,new_value,created_at)
        VALUES (11,1,'payment','unpaid','paid',UTC_TIMESTAMP())");
    $count=(int)$db->query("SELECT COUNT(*) FROM zz99_digital_grants WHERE order_item_id=21")->fetch_row()[0];
    $events=(int)$db->query("SELECT COUNT(*) FROM zz99_order_events WHERE order_id=11")->fetch_row()[0];
    $check($count===1&&$events===1,'new entitlement and audit tables usable');
    try{
        $db->query("INSERT INTO zz99_digital_grants
            (order_item_id,token_nonce,expires_at,created_at)
            VALUES (900,REPEAT('b',32),UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        $check(false,'orphan digital grant rejected');
    }catch(mysqli_sql_exception){$check(true,'orphan digital grant rejected');}
    $db->query('DELETE FROM zz99_orders WHERE id=11');
    $check((int)$db->query('SELECT COUNT(*) FROM zz99_order_events')->fetch_row()[0]===0,
        'order event history foreign key cascade');
    echo "MM99_SCHEMA_MYSQL84_PASS: $n checks\n";
}finally{
    foreach($tables as $table)$db->query('DROP TABLE IF EXISTS '.$table);
    $db->close();
}
