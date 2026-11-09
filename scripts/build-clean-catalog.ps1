param(
    [Parameter(Mandatory = $true)][string]$DbPrefix,
    [Parameter(Mandatory = $true)][string]$DatabaseName,
    [Parameter(Mandatory = $true)][string]$OutputPath
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Generates a FILE ONLY. It never connects to MySQL and never deletes data.
if ($DbPrefix -notmatch '^[A-Za-z0-9_]{2,64}$' -or
    $DatabaseName -notmatch '^[A-Za-z0-9_]{1,64}$') {
    throw 'CLEAN_SQL_UNSAFE_DB_NAME_OR_PREFIX'
}
$root = Split-Path -Parent $PSScriptRoot
$schemaFile = Join-Path $root 'src\com_minimarket\admin\sql\install.mysql.sql'
$seedFile = Join-Path $root 'sample-data\minimarket_mysql84_nnop3_update_0_5_73.sql'
if (-not (Test-Path -LiteralPath $schemaFile -PathType Leaf) -or
    -not (Test-Path -LiteralPath $seedFile -PathType Leaf)) {
    throw 'CLEAN_SQL_MISSING_SOURCE'
}
$schema = [System.IO.File]::ReadAllText($schemaFile, [System.Text.Encoding]::UTF8)
$seed = [System.IO.File]::ReadAllText($seedFile, [System.Text.Encoding]::UTF8)
if (-not $schema.Contains('CREATE TABLE IF NOT EXISTS `#__minimarket_attributes`') -or
    -not $schema.Contains('CREATE TABLE IF NOT EXISTS `#__minimarket_feeds`') -or
    $schema.Contains('product_attribute_multi_values') -or
    $schema.Contains('input_mode') -or
    -not $seed.Contains('INSERT INTO `nnop3_minimarket_products`') -or
    -not $seed.Contains('INSERT INTO `nnop3_minimarket_attribute_values`') -or
    -not $seed.Contains('COMMIT;')) {
    throw 'CLEAN_SQL_SOURCE_VERSION_MISMATCH'
}

# Use the database selected at the top of the file; do not allow the
# cumulative seed to silently switch to another database.
$usePattern = '(?mi)^[ \t]*USE[ \t]+`minimarket`[ \t]*;[ \t]*\r?$'
if ([regex]::Matches($seed, $usePattern).Count -lt 1) {
    throw 'CLEAN_SQL_UNEXPECTED_SEED_DATABASE_SWITCH'
}
$seed = [regex]::Replace($seed, $usePattern, '-- Database selected by clean SQL header.')
$schema = $schema.Replace('#__', $DbPrefix)
$seed = $seed.Replace('nnop3_', $DbPrefix)

$header = @'
-- MiniMarket 0.5.73 CLEAN REBUILD (MySQL 8.4, Joomla 6).
-- DESTRUCTIVE for the MiniMarket catalog: backup the COMPLETE database first.
-- This resets ONLY __PREFIX__minimarket_* tables, never Joomla core tables.
-- All products/categories/SKUs/attributes/feeds are replaced with DEMO data.
-- Import into an EXISTING Joomla with com_minimarket registered, BEFORE ZIP.
-- Do NOT remove Joomla users/extensions/schemas or uninstall the component.
-- Database and Joomla prefix are both taken from configuration.php.
USE `__DATABASE__`;
SET NAMES utf8mb4;

-- Validate Joomla component registration BEFORE any destructive SQL.
CREATE TEMPORARY TABLE `_mm73_clean_guard` (
    `ok` TINYINT NOT NULL CHECK (`ok` = 1)
) ENGINE=InnoDB;
INSERT INTO `_mm73_clean_guard` (`ok`)
SELECT IF(
    EXISTS (SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '__PREFIX__extensions')
    AND EXISTS (SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '__PREFIX__schemas')
    AND EXISTS (SELECT 1 FROM `__PREFIX__extensions`
        WHERE `type` = 'component' AND `element` = 'com_minimarket'),
    1, 0
);
DROP TEMPORARY TABLE `_mm73_clean_guard`;

-- Delete only the existing MiniMarket tables, including old legacy tables.
-- Other Joomla tables and unrelated components are never included.
-- Use a literal: MySQL 8.4 rejects GREATEST() as a SET-variable argument (#1232).
-- Set before suspending FK checks, so failures cannot leave FK checks off.
SET SESSION group_concat_max_len = 65535;
SET @mm73_prior_fk_checks = @@SESSION.FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
SELECT GROUP_CONCAT(
    CONCAT('`', REPLACE(TABLE_NAME, '`', '``'), '`')
    ORDER BY TABLE_NAME SEPARATOR ', '
) INTO @mm73_old_catalog_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND LEFT(TABLE_NAME, CHAR_LENGTH('__PREFIX__minimarket_')) = '__PREFIX__minimarket_';
SET @mm73_drop_catalog = IF(@mm73_old_catalog_tables IS NULL,
    'DO 0', CONCAT('DROP TABLE IF EXISTS ', @mm73_old_catalog_tables));
PREPARE mm73_clean_drop FROM @mm73_drop_catalog;
EXECUTE mm73_clean_drop;
DEALLOCATE PREPARE mm73_clean_drop;
SET FOREIGN_KEY_CHECKS = @mm73_prior_fk_checks;

-- Canonical 0.5.73 fresh install schema:
'@
$footer = @'
-- Version marker ONLY for the already installed MiniMarket component.
-- Prevent Joomla from replaying obsolete SQL migrations against fresh tables.
UPDATE `__PREFIX__schemas` s
JOIN `__PREFIX__extensions` e ON e.extension_id = s.extension_id
SET s.version_id = '0.5.73'
WHERE e.type = 'component' AND e.element = 'com_minimarket';
INSERT INTO `__PREFIX__schemas` (`extension_id`, `version_id`)
SELECT e.extension_id, '0.5.73'
FROM `__PREFIX__extensions` e
WHERE e.type = 'component' AND e.element = 'com_minimarket'
AND NOT EXISTS (SELECT 1 FROM `__PREFIX__schemas` s WHERE s.extension_id = e.extension_id);

-- Sanity-check the demo after import: expected 12, 52, 496, 462.
SELECT 'attributes' entity, COUNT(*) rows_count FROM `__PREFIX__minimarket_attributes`
UNION ALL SELECT 'attribute_values', COUNT(*) FROM `__PREFIX__minimarket_attribute_values`
UNION ALL SELECT 'product_attribute_options', COUNT(*) FROM `__PREFIX__minimarket_product_attribute_options`
UNION ALL SELECT 'product_attribute_scalars', COUNT(*) FROM `__PREFIX__minimarket_product_attribute_scalars`
UNION ALL SELECT 'products', COUNT(*) FROM `__PREFIX__minimarket_products`
UNION ALL SELECT 'categories', COUNT(*) FROM `__PREFIX__minimarket_categories`;
'@
$header = $header.Replace('__PREFIX__', $DbPrefix).Replace('__DATABASE__', $DatabaseName)
$footer = $footer.Replace('__PREFIX__', $DbPrefix)
$sql = $header + [Environment]::NewLine + $schema +
    [Environment]::NewLine + '-- CANONICAL DEMONSTRATION CATALOG 0.5.73' +
    [Environment]::NewLine + $seed + [Environment]::NewLine + $footer
if ($sql.Contains('#__minimarket_') -or $sql.Contains('__PREFIX__') -or
    $sql.Contains('__DATABASE__')) {
    throw 'CLEAN_SQL_UNREPLACED_PLACEHOLDER'
}
$dest = [System.IO.Path]::GetFullPath($OutputPath)
$parent = Split-Path -Parent $dest
if (-not (Test-Path -LiteralPath $parent -PathType Container)) {
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
}
$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($dest, $sql, $utf8)
Write-Output "MINIMARKET_CLEAN_SQL_READY: $dest"
Write-Output "MINIMARKET_CLEAN_SQL_TARGET: $DatabaseName / $DbPrefix"

