<?php
/** Manual SQL covers 0.5.69->0.5.71 grid + 0.5.73 attr cleanup, MySQL 8.4. */
declare(strict_types=1);
if(getenv('MINIMARKET_CANONICAL_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73'){
    fwrite(STDERR,"MM73_MANUAL_GRID_REFUSED\n");exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',(int)(getenv('MM_CI_DB_PORT')?:3306));
$db->set_charset('utf8mb4');
$manual=file_get_contents(__DIR__.'/../releases/minimarket_mysql84_nnop3_upgrade_0_5_73.sql');
$start='-- BEGIN GRID PRELUDE';$end='-- END GRID PRELUDE';
$offset=strpos($manual,$start);$endAt=strpos($manual,$end,$offset);
if($offset===false||$endAt===false)throw new RuntimeException('MISSING_GRID_PRELUDE');
$grid=substr($manual,$offset,$endAt-$offset);
$grid=str_replace('nnop3_minimarket_categories','mm73_old_grid_ci',$grid);
$n=0;
function checkGrid73(bool $ok,string $message):void {
 global $n;$n++;if(!$ok)throw new RuntimeException('MM73_GRID_FAIL: '.$message);
}
function runGrid73(mysqli $db,string $sql):void {
 $db->multi_query($sql);
 do{
   $result=$db->store_result();
   if($result instanceof mysqli_result)$result->free();
   if(!$db->more_results())break;
 }while($db->next_result());
}
$db->query('DROP TABLE IF EXISTS mm73_old_grid_ci');
try {
 $db->query('CREATE TABLE mm73_old_grid_ci (
   id INT UNSIGNED PRIMARY KEY, cards_mobile TINYINT NOT NULL DEFAULT -1,
   cards_tablet TINYINT NOT NULL DEFAULT -1,
   cards_desktop TINYINT NOT NULL DEFAULT -1,
   cards_wide TINYINT NOT NULL DEFAULT -1
 ) ENGINE=InnoDB');
 $db->query('INSERT INTO mm73_old_grid_ci VALUES (4,1,2,3,4),(9,-1,-1,-1,-1)');
 runGrid73($db,$grid);
 $cols=array_column($db->query('SHOW COLUMNS FROM mm73_old_grid_ci')
   ->fetch_all(MYSQLI_ASSOC),'Field');
 foreach(['cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl'] as $col)
   checkGrid73(in_array($col,$cols,true),'new grid '.$col);
 foreach(['cards_mobile','cards_tablet','cards_desktop','cards_wide'] as $col)
   checkGrid73(!in_array($col,$cols,true),'obsolete grid absent '.$col);
 $saved=$db->query('SELECT id,cards_xs,cards_sm,cards_md,cards_lg,cards_xl,cards_xxl
   FROM mm73_old_grid_ci ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 checkGrid73(array_values(array_map('intval',$saved[0]))===[4,1,-1,2,3,4,-1],
   'old values preserve exact mapped count');
 runGrid73($db,$grid);
 $again=$db->query('SELECT id,cards_xs,cards_sm,cards_md,cards_lg,cards_xl,cards_xxl
   FROM mm73_old_grid_ci ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 checkGrid73($saved===$again,'second manual prelude does not repeat DDL');
 echo "MM73_MANUAL_GRID_OK: $n checks\n";
} finally {
 $db->query('DROP TABLE IF EXISTS mm73_old_grid_ci');
 $db->close();
}
