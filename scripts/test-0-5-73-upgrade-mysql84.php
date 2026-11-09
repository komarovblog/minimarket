<?php
/**
 * MiniMarket 0.5.73: run the actual Joomla update SQL on populated OLD-format
 * tables, on a disposable MySQL 8.4 shadow schema. Never touches Joomla tables.
 */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
    fwrite(STDERR, "MM73_UPGRADE_REFUSED\n");exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$prefix='zz73_';
$names=['attributes','attribute_values','product_attribute_scalars',
    'product_attribute_options','product_attribute_multi_values'];
foreach(array_reverse($names) as $name) $db->query('DROP TABLE IF EXISTS '.$prefix.$name);
$n=0;
function yes73(bool $okay,string $why):void {
    global $n;$n++;if(!$okay)throw new RuntimeException('MM73_UPGRADE_FAIL: '.$why);
}
function count73u(mysqli $db,string $table):int {
    return (int)$db->query('SELECT COUNT(*) FROM zz73_'.$table)->fetch_row()[0];
}
function run73u(mysqli $db,string $sql):void {
    // Exercise Joomla's prepared protocol, not the permissive multi_query().
    $parts=preg_split('/;(?=\\s*(?:\\r?\\n|$))/', $sql);
    if(!is_array($parts))throw new RuntimeException('MM73_SQL_SPLIT_FAILED');
    foreach($parts as $part) {
        $statement=trim($part);
        if($statement==='')continue;
        $stmt=$db->prepare($statement);
        try {
            $stmt->execute();
            $res=$stmt->get_result();
            if($res instanceof mysqli_result)$res->free();
        } finally {
            $stmt->close();
        }
    }
}
try {
    $ddl=[
        'attributes'=>'CREATE TABLE zz73_attributes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL DEFAULT "",
            input_type VARCHAR(16) NOT NULL DEFAULT "list",
            input_mode VARCHAR(24) NOT NULL DEFAULT "auto",
            value_source VARCHAR(12) NOT NULL DEFAULT "legacy",
            color_display VARCHAR(12) NOT NULL DEFAULT "both",
            number_options VARCHAR(1024) NOT NULL DEFAULT "",
            text_options TEXT NULL,
            number_unit VARCHAR(32) NOT NULL DEFAULT ""
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'attribute_values'=>'CREATE TABLE zz73_attribute_values (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attribute_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            code VARCHAR(100) NOT NULL,
            value_payload MEDIUMTEXT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            state TINYINT NOT NULL DEFAULT 1,
            UNIQUE KEY value_code (attribute_id,code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'product_attribute_scalars'=>'CREATE TABLE zz73_product_attribute_scalars (
            product_id INT UNSIGNED NOT NULL,
            attribute_id INT UNSIGNED NOT NULL,
            value_text TEXT NULL,
            value_number DECIMAL(18,4) NULL,
            value_boolean TINYINT UNSIGNED NULL,
            PRIMARY KEY(product_id,attribute_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'product_attribute_options'=>'CREATE TABLE zz73_product_attribute_options (
            product_id INT UNSIGNED NOT NULL,
            attribute_id INT UNSIGNED NOT NULL,
            value_id INT UNSIGNED NOT NULL,
            PRIMARY KEY(product_id,attribute_id,value_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'product_attribute_multi_values'=>'CREATE TABLE zz73_product_attribute_multi_values (
            product_id INT UNSIGNED NOT NULL,
            attribute_id INT UNSIGNED NOT NULL,
            value_text VARCHAR(255) NOT NULL,
            PRIMARY KEY(product_id,attribute_id,value_text)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];
    foreach($ddl as $sql)$db->query($sql);
    $db->query("INSERT INTO zz73_attributes
        (id,title,input_type,input_mode,value_source,text_options) VALUES
        (1,'Material','text','presets','legacy','[\"Cotton\",\"Wool\",\"Silk\"]'),
        (2,'Color','color','checkboxes','legacy',NULL),
        (3,'Power','number','auto','legacy',NULL),
        (4,'Safety','boolean','auto','legacy',NULL),
        (5,'Region','list','dropdown','legacy',NULL),
        (6,'Comment','text','auto','legacy',NULL)");
    $db->query("INSERT INTO zz73_attribute_values (id,attribute_id,title,code) VALUES
        (301,2,'Blue','blue'),(302,5,'West','west')");
    $db->query("INSERT INTO zz73_product_attribute_scalars
        (product_id,attribute_id,value_text,value_number,value_boolean) VALUES
        (10,1,'Cotton',NULL,NULL),
        (11,1,'Wool',NULL,NULL),
        (12,3,NULL,15.0000,NULL),
        (12,4,NULL,NULL,1),
        (12,6,'Preserved free-form comment',NULL,NULL)");
    $db->query("INSERT INTO zz73_product_attribute_multi_values
        (product_id,attribute_id,value_text) VALUES
        (13,1,'Cotton'),(13,1,'Silk')");
    $db->query("INSERT INTO zz73_product_attribute_options
        (product_id,attribute_id,value_id) VALUES
        (10,2,301),(11,5,302)");
    yes73(count73u($db,'attributes')===6,'fixture attributes');
    yes73(count73u($db,'attribute_values')===2,'fixture original dictionary IDs');
    $sql=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/updates/mysql/0.5.73.sql');
    yes73(is_string($sql) && str_contains($sql,'DROP TABLE #__minimarket_product_attribute_multi_values'),
        'actual updater contains legacy cleanup');
    $sql=str_replace('#__minimarket_','zz73_',$sql);
    $db->begin_transaction(); // Equivalent to the installer preflight hook.
    run73u($db,$sql);
    yes73(count73u($db,'attributes')===6,'all attribute IDs retained');
    yes73(count73u($db,'attribute_values')===5,'three text preset IDs materialized');
    yes73(count73u($db,'product_attribute_options')===6,
        'old color/list IDs + scalar and multi text IDs survive');
    yes73(count73u($db,'product_attribute_scalars')===3,
        'only two text preset scalar rows converted');
    yes73((int)$db->query('SELECT COUNT(*) FROM zz73_attributes
        WHERE value_source="legacy" OR input_type="list"')->fetch_row()[0]===0,
        'all attribute definitions canonical');
    yes73((int)$db->query('SELECT COUNT(*) FROM zz73_product_attribute_options o
        JOIN zz73_attribute_values v ON v.id=o.value_id AND v.attribute_id=o.attribute_id
        WHERE o.product_id=13 AND v.title IN ("Cotton","Silk")')
        ->fetch_row()[0]===2,'multi text converted with exact meaning');
    yes73((int)$db->query('SELECT COUNT(*) FROM zz73_product_attribute_options
        WHERE value_id IN (301,302)')->fetch_row()[0]===2,
        'original dictionary identity unaffected');
    yes73((int)$db->query('SELECT COUNT(*) FROM zz73_product_attribute_scalars
        WHERE product_id=12 AND (value_number=15 OR value_boolean=1)')
        ->fetch_row()[0]===2,'number and boolean remain typed scalars');
    yes73((string)$db->query('SELECT value_text FROM zz73_product_attribute_scalars
        WHERE product_id=12 AND attribute_id=6')->fetch_row()[0]==='Preserved free-form comment',
        'free-form manual text retained');
    foreach(['input_mode','text_options','number_options'] as $col){
        yes73($db->query("SHOW COLUMNS FROM zz73_attributes LIKE '$col'")->num_rows===0,
            'obsolete metadata dropped '.$col);
    }
    yes73($db->query("SHOW TABLES LIKE 'zz73_product_attribute_multi_values'")->num_rows===0,
        'obsolete multi table removed');
    echo "MM73_UPGRADE_MYSQL84_OK: $n checks\n";
} finally {
    foreach(array_reverse($names) as $name)$db->query('DROP TABLE IF EXISTS '.$prefix.$name);
    $db->close();
}
