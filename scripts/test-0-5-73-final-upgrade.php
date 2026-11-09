<?php
/** One source of truth: standalone manual SQL must equal the tested Joomla updater. */
declare(strict_types=1);
$root=dirname(__DIR__);
$installer=file_get_contents($root.'/src/com_minimarket/admin/sql/updates/mysql/0.5.73.sql');
$manual=file_get_contents($root.'/releases/minimarket_mysql84_nnop3_upgrade_0_5_73.sql');
if(!is_string($installer) || !is_string($manual))throw new RuntimeException('MM73_FINAL_SQL_MISSING');
$begin="-- BEGIN SHARED MIGRATION\n";
$end="\n-- END SHARED MIGRATION";
$start=strpos($manual,$begin);
if($start===false)throw new RuntimeException('MM73_FINAL_START_MARKER_MISSING');
$start+=strlen($begin);$last=strpos($manual,$end,$start);
if($last===false)throw new RuntimeException('MM73_FINAL_END_MARKER_MISSING');
$reimport=trim(str_replace('nnop3_','#__',substr($manual,$start,$last-$start)));
if($reimport!==trim($installer))throw new RuntimeException('MM73_FINAL_SQL_MISMATCH');
if(!str_contains($manual,"UPDATE nnop3_schemas SET version_id='0.5.73'")
 || !str_contains($manual,'element=\'com_minimarket\''))
  throw new RuntimeException('MM73_FINAL_SCHEMA_MARKER_MISSING');
if(str_contains($manual,'nnop3_users') || str_contains($manual,'nnop3_user_profiles'))
  throw new RuntimeException('MM73_FINAL_USER_TABLES_MUST_NOT_BE_MODIFIED');
echo "MM73_FINAL_SQL_OK: matches tested installer migration and marks Joomla schema\n";
