<?php
/** 0.6.2 additive SEO landing migration on disposable canonical MySQL 8.4. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
    ||getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73')
    throw new RuntimeException('MM062_MYSQL_REFUSED_OUTSIDE_DISPOSABLE_CI');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$table='zz62_seo_filters';
$db->query("DROP TABLE IF EXISTS $table");
$count=0;
$check=static function(bool $result,string $message)use(&$count):void{
    ++$count;if(!$result)throw new RuntimeException('MM062_MYSQL_FAIL: '.$message);
};
try{
    $db->query("CREATE TABLE $table (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        category_id INT UNSIGNED NOT NULL,
        filters_json TEXT NOT NULL,
        state TINYINT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT INTO $table VALUES
        (1,7,'{\"a10\":[\"v:101\",\"v:102\"]}',1),
        (2,7,'{\"a10\":[\"v:101\"]}',0)");
    $sql=(string)file_get_contents(dirname(__DIR__)
        .'/src/com_minimarket/admin/sql/updates/mysql/0.6.2.sql');
    $sql=str_replace('#__minimarket_seo_filters',$table,$sql);
    $sql=(string)preg_replace('/^[ \\t]*--[^\\r\\n]*$/m','',$sql);
    foreach(explode(';',$sql) as $statement){
        if(trim($statement)!=='')$db->query(trim($statement));
    }
    $old=$db->query("SELECT state,filters_json,conditions_json FROM $table WHERE id=1")
        ->fetch_assoc();
    $check($old!==null&&$old['state']==='1'
        &&$old['conditions_json']===null
        &&str_contains($old['filters_json'],'v:102'),
        'published legacy multi-value SEO landing preserved unchanged');
    $oldDraft=$db->query("SELECT state,conditions_json FROM $table WHERE id=2")
        ->fetch_assoc();
    $check($oldDraft['state']==='0'&&$oldDraft['conditions_json']===null,
        'draft legacy row preserved without invented rule');
    $rules=json_encode([
        'a10'=>['mode'=>'exact','value'=>'v:101'],
        's20'=>['mode'=>'any']
    ],JSON_THROW_ON_ERROR);
    $stmt=$db->prepare("INSERT INTO $table(id,category_id,filters_json,conditions_json,state)
        VALUES(3,7,?,?,1)");
    $filters='{"a10":["v:101"]}';
    $stmt->bind_param('ss',$filters,$rules);$stmt->execute();$stmt->close();
    $row=$db->query("SELECT conditions_json FROM $table WHERE id=3")->fetch_assoc();
    $decoded=json_decode((string)$row['conditions_json'],true);
    $check(($decoded['s20']['mode']??null)==='any'
        &&($decoded['a10']['value']??null)==='v:101',
        'new three-state rules stored and read safely');
    $check((int)$db->query("SELECT COUNT(*) FROM $table")->fetch_row()[0]===3,
        'no old SEO records deleted');
    echo "MM062_SCHEMA_MYSQL84_PASS: $count checks\n";
}finally{
    $db->query("DROP TABLE IF EXISTS $table");
    $db->close();
}
