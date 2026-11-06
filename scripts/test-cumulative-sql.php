<?php
/** Contract for the cumulative SQL delivered to an already-populated demo catalog. */
declare(strict_types=1);
$sql=file_get_contents(__DIR__.'/../sample-data/minimarket_mysql84_nnop3_update_0_5_57.sql');
if ($sql===false) throw new RuntimeException('CUMULATIVE_SQL_MISSING');
function mm_assert(bool $yes,string $why):void { if (!$yes) throw new RuntimeException('CUMULATIVE_SQL_FAIL: '.$why); }
mm_assert(str_contains($sql,'ck_mm_catalog_identity'),'identity collision guard missing');
mm_assert(!str_contains($sql,'ck_mm_demo_empty'),'obsolete empty-catalog constraint still present');
mm_assert(substr_count($sql,'ON DUPLICATE KEY UPDATE')>=30,'idempotent insert clauses missing');
mm_assert(str_contains($sql,'BUNDLE-1092') && str_contains($sql,'SKU-1012-SET60-BLUE'),'nested and SKU bundle demos missing');
mm_assert(!preg_match('/^\\s*(?:DELETE\\s+FROM|TRUNCATE\\s+TABLE|DROP\\s+TABLE|ALTER\\s+TABLE)\\b/im',$sql),'destructive SQL not permitted');
mm_assert(str_contains($sql,'USE `minimarket`;'),'wrong demo database');
echo "CUMULATIVE_SQL_CONTRACT_OK\\n";
