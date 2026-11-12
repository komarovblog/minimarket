<?php
/** Apply MiniMarket 0.6.0 ADD COLUMN/CREATE TABLE to 0.5.99 shadow data. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
    ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
    throw new RuntimeException('MM060_MYSQL_REFUSED_OUTSIDE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$names=['zz60_digital_keys','zz60_order_items','zz60_orders','zz60_products'];
foreach($names as $table)$db->query('DROP TABLE IF EXISTS '.$table);
$n=0;
$check=static function(bool $ok,string $why)use(&$n):void{
    ++$n;if(!$ok)throw new RuntimeException('MM060_MYSQL_FAIL: '.$why);
};
try{
    $db->query("CREATE TABLE zz60_products (
        id INT UNSIGNED PRIMARY KEY,
        delivery_type VARCHAR(12) NOT NULL DEFAULT 'physical',
        digital_asset_key VARCHAR(80) NOT NULL DEFAULT '',
        digital_filename VARCHAR(255) NOT NULL DEFAULT '',
        title VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE zz60_orders (
        id INT UNSIGNED PRIMARY KEY,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        payment_status VARCHAR(16) NOT NULL DEFAULT 'unpaid',
        email VARCHAR(254) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE zz60_order_items (
        id BIGINT UNSIGNED PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        digital_asset_key VARCHAR(80) NOT NULL DEFAULT '',
        digital_filename VARCHAR(255) NOT NULL DEFAULT '',
        delivery_type VARCHAR(12) NOT NULL DEFAULT 'physical',
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        title VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT INTO zz60_products VALUES(7,'physical','','','Old chair'),(8,'digital','abc.pdf','manual.pdf','Legacy download')");
    $db->query("INSERT INTO zz60_orders VALUES(11,'new','unpaid','buyer@example.org')");
    $db->query("INSERT INTO zz60_order_items VALUES(21,11,7,'','','physical',1,'Chair'),(22,11,8,'abc.pdf','manual.pdf','digital',1,'Download')");
    $sql=(string)file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.6.0.sql');
    $sql=str_replace([
        '#__minimarket_products','#__minimarket_orders','#__minimarket_order_items',
        '#__minimarket_digital_keys','fk_mm_digital_key_product'
    ],[
        'zz60_products','zz60_orders','zz60_order_items',
        'zz60_digital_keys','fk_mm60_digital_key_product'
    ],$sql);
    $sql=(string)preg_replace('/^[ \t]*--[^\r\n]*$/m','',$sql);
    foreach(explode(';',$sql) as $statement){
        $statement=trim($statement);
        if($statement!=='')$db->query($statement);
    }
    $old=$db->query("SELECT delivery_type,digital_delivery_mode,digital_delivery_note
        FROM zz60_products WHERE id=7")->fetch_assoc();
    $check($old!==null&&$old['delivery_type']==='physical'
        &&$old['digital_delivery_mode']==='file'&&$old['digital_delivery_note']==='',
        'existing physical products preserved');
    $file=$db->query("SELECT digital_asset_key,digital_delivery_mode
        FROM zz60_products WHERE id=8")->fetch_assoc();
    $check($file['digital_asset_key']==='abc.pdf'&&$file['digital_delivery_mode']==='file',
        'legacy digital file stays as file mode');
    $order=$db->query("SELECT status,payment_status,email,digital_recipient_email
        FROM zz60_orders WHERE id=11")->fetch_assoc();
    $check($order['status']==='new'&&$order['payment_status']==='unpaid'
        &&$order['digital_recipient_email']==='',
        'old orders preserved without invented payment confirmation');
    $item=$db->query("SELECT digital_delivery_mode,digital_fulfilled_at
        FROM zz60_order_items WHERE id=22")->fetch_assoc();
    $check($item['digital_delivery_mode']==='file'
        &&$item['digital_fulfilled_at']===null,
        'previous file order items remain pending');
    $db->query("INSERT INTO zz60_digital_keys
        (product_id,fingerprint,encrypted_code,created_at)
        VALUES (8,REPEAT('a',64),'encrypted-only',UTC_TIMESTAMP())");
    $check((int)$db->query('SELECT COUNT(*) FROM zz60_digital_keys WHERE order_item_id IS NULL')
        ->fetch_row()[0]===1,'new private key inventory created');
    try{
        $db->query("INSERT INTO zz60_digital_keys
            (product_id,fingerprint,encrypted_code,created_at)
            VALUES (8,REPEAT('a',64),'duplicate',UTC_TIMESTAMP())");
        $check(false,'duplicate codes must be refused');
    }catch(mysqli_sql_exception){$check(true,'duplicate codes must be refused');}
    try{
        $db->query("INSERT INTO zz60_digital_keys
            (product_id,fingerprint,encrypted_code,created_at)
            VALUES (999,REPEAT('b',64),'invalid product',UTC_TIMESTAMP())");
        $check(false,'orphan license refused');
    }catch(mysqli_sql_exception){$check(true,'orphan license refused');}
    echo "MM060_SCHEMA_MYSQL84_PASS: $n checks\n";
}finally{
    foreach($names as $table)$db->query('DROP TABLE IF EXISTS '.$table);
    $db->close();
}
