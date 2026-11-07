<?php
/** MySQL 8.4 regression, disposable shadow table, not real catalog. */
declare(strict_types=1);
if (getenv('MINIMARKET_DEMO_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_demo84') {
    fwrite(STDERR,"MM71_GRID_DB_TEST_REFUSED\n");
    exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',
    getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_demo84',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$table='nnop3_mm71_grid_ci';
$n=0;
function checkDb71(bool $pass,string $message):void {
    global $n;$n++;
    if(!$pass)throw new RuntimeException('MM71_GRID_MYSQL84_FAIL: '.$message);
}
$db->query('DROP TABLE IF EXISTS '.$table);
try {
    $db->query('CREATE TABLE '.$table.' (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        cards_mobile TINYINT NOT NULL DEFAULT -1,
        cards_tablet TINYINT NOT NULL DEFAULT -1,
        cards_desktop TINYINT NOT NULL DEFAULT -1,
        cards_wide TINYINT NOT NULL DEFAULT -1
    ) ENGINE=InnoDB');
    $db->query('INSERT INTO '.$table.'
        (title,cards_mobile,cards_tablet,cards_desktop,cards_wide)
        VALUES ("Customized",2,3,4,6),("Default",-1,-1,-1,-1)');
    $source=file_get_contents(__DIR__.
        '/../src/com_minimarket/admin/sql/updates/mysql/0.5.71.sql');
    if(!is_string($source))throw new RuntimeException('MIGRATION_MISSING');
    $sql=str_replace(chr(96).'#__minimarket_categories'.chr(96),$table,$source);
    if(!str_contains($sql,'ALTER TABLE '.$table)){
        throw new RuntimeException('WRONG_MIGRATION_TABLE');
    }
    $db->multi_query($sql);
    do {
        $res=$db->store_result();
        if($res instanceof mysqli_result)$res->free();
        if(!$db->more_results())break;
    } while($db->next_result());
    $fields=array_column(
        $db->query('SHOW COLUMNS FROM '.$table)->fetch_all(MYSQLI_ASSOC),'Field'
    );
    foreach(['cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl'] as $field){
        checkDb71(in_array($field,$fields,true),'new field '.$field);
    }
    foreach(['cards_mobile','cards_tablet','cards_desktop','cards_wide'] as $old){
        checkDb71(!in_array($old,$fields,true),'old field removed '.$old);
    }
    $rows=$db->query('SELECT * FROM '.$table.' ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    checkDb71(count($rows)===2,'both old category IDs retained');
    $cols=['cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl'];
    $actual=static function(array $row)use($cols):array{
        return array_map(static fn(string $field):int=>(int)$row[$field],$cols);
    };
    checkDb71($actual($rows[0])===[2,-1,3,4,6,-1],
      'all saved counts moved without loss');
    checkDb71($actual($rows[1])===[-1,-1,-1,-1,-1,-1],
      'new category inherits all six counts');
    checkDb71((int)$rows[0]['id']===1 &&
      $rows[0]['title']==='Customized','original category identity preserved');
    echo 'MM71_GRID_MYSQL84_OK: '.$n." checks\n";
} finally {
    $db->query('DROP TABLE IF EXISTS '.$table);
    $db->close();
}
