<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

/**
 * Register the MiniMarket dashboard. Starting in 0.5.64 also retire ONLY the
 * obsolete administrator module introduced by component release 0.5.62.
 * The replacement mod_minimarket_toolbar is a separate optional extension,
 * never installed or modified by the MiniMarket component.
 */
class com_minimarketInstallerScript extends InstallerScript
{
    /**
     * START TRANSACTION is not accepted by MySQL 8.4 as a prepared statement.
     * Joomla executes SQL upgrade scripts using prepared statements, so begin
     * the 0.5.73 data migration via Joomla's database driver.
     */
    public function preflight($type, $parent)
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }
        if ($type !== 'update') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery(
            $db->getQuery(true)
                ->select('s.version_id')
                ->from($db->quoteName('#__schemas', 's'))
                ->join('INNER', $db->quoteName('#__extensions', 'e')
                    . ' ON s.extension_id = e.extension_id')
                ->where('e.type = ' . $db->quote('component'))
                ->where('e.element = ' . $db->quote('com_minimarket'))
        );
        $schemaVersion = (string) ($db->loadResult() ?? '');
        if ($schemaVersion !== '' && version_compare($schemaVersion, '0.5.73', '>=')) {
            return true;
        }

        // An interrupted destructive upgrade must be inspected, not replayed.
        $db->setQuery('SHOW COLUMNS FROM '
            . $db->quoteName('#__minimarket_attributes') . ' LIKE '
            . $db->quote('input_mode'));
        if (!$db->loadResult()) {
            throw new \RuntimeException(
                'MiniMarket 0.5.73: legacy attribute columns are already absent '
                . 'while Joomla schema version is below 0.5.73. '
                . 'Stop and verify the database backup/schema before retrying.'
            );
        }

        // Joomla's 0.5.73.sql COMMIT completes the data-only transaction.
        // Upgrades with earlier outstanding DDL may implicitly commit first.
        $db->transactionStart();
        return true;
    }

    public function postflight($type, $parent)
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return true;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . '=' . $db->quote('mod_submenu'))
            ->where($db->quoteName('position') . '=' . $db->quote('cpanel-minimarket'));
        $db->setQuery($q);
        if ((int)$db->loadResult()===0) {
            $this->addDashboardMenu('minimarket','minimarket');
        }

        // The old shortcut was installed automatically in 0.5.62, and its
        // module instances persist after component upgrades. Remove only
        // its exact Joomla extension ID via Joomla's extension uninstaller.
        // Never delete arbitrary mod_custom modules or other status modules.
        try {
            $db->setQuery($db->getQuery(true)
                ->select('extension_id')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . '=' . $db->quote('module'))
                ->where($db->quoteName('element') . '=' . $db->quote('mod_minimarket_quicklink'))
                ->where($db->quoteName('client_id') . '=1'));
            $legacyId=(int)$db->loadResult();
            if ($legacyId>0) {
                $installer=new Installer();
                $installer->setDatabase($db);
                if (!$installer->uninstall('module',$legacyId)) {
                    throw new \RuntimeException('Joomla extension uninstaller returned false');
                }
            }
        } catch (\Throwable $error) {
            Factory::getApplication()->enqueueMessage(
                'MiniMarket: remove the retired "MiniMarket — quick link" '
                . 'administrator module in Joomla Extensions if still visible. '
                . $error->getMessage(),
                'warning'
            );
        }
        // Component Options are stored as JSON in #__extensions. Migrate
        // previous custom grid counts without changing any unrelated settings.
        // This runs after Joomla applies 0.5.71.sql and is idempotent.
        $this->migrateGridOptions($db);
        // Fresh canonical databases supersede obsolete pre-0.5.73 DDL checks.
        // This affects installed SQL history files only, never the catalog DB.
        try {
            $this->retireObsoleteSchemaChecks($db);
        } catch (\Throwable $error) {
            Factory::getApplication()->enqueueMessage(
                'MiniMarket: cannot archive obsolete Joomla SQL checks: ' . $error->getMessage(),
                'warning'
            );
        }
        return true;
    }


    /**
     * Joomla Database Maintenance scans *all* historical ALTER files without
     * considering later DROP statements. On a canonical 0.5.73 database it
     * therefore offers to re-add legacy attribute columns that were removed
     * intentionally. Keep this PHP install-time clean-up independent of SQL
     * migrations, and run only after a verified 0.5.73 install/upgrade.
     *
     * Files from the installed extension are SHA-256-backed up first; this
     * never modifies #__schemas, attributes, products or any other DB row.
     * The repository/source upgrade SQL files remain untouched.
     */
    private function retireObsoleteSchemaChecks(DatabaseInterface $db): void
    {
        // Joomla defines this constant. Standalone options tests do not,
        // and do not have an installed SQL history to inspect.
        if (!defined('JPATH_ADMINISTRATOR')) return;
        $dir = JPATH_ADMINISTRATOR.'/components/com_minimarket/sql/updates/mysql';
        if (!is_dir($dir)) return;

        $db->setQuery(
            $db->getQuery(true)->select('s.version_id')
                ->from($db->quoteName('#__schemas', 's'))
                ->join('INNER', $db->quoteName('#__extensions', 'e')
                    . ' ON e.extension_id=s.extension_id')
                ->where('e.type=' . $db->quote('component'))
                ->where('e.element=' . $db->quote('com_minimarket'))
        );
        if ((string)($db->loadResult() ?? '') !== '0.5.73') {
            return;
        }

        $attrTable = $db->quoteName('#__minimarket_attributes');
        foreach (['input_type', 'value_source'] as $column) {
            $db->setQuery('SHOW COLUMNS FROM '.$attrTable.' LIKE '.$db->quote($column));
            if (!$db->loadResult()) return;
        }
        foreach (['input_mode', 'text_options', 'number_options'] as $column) {
            $db->setQuery('SHOW COLUMNS FROM '.$attrTable.' LIKE '.$db->quote($column));
            if ($db->loadResult()) return;
        }
        $db->setQuery('SHOW TABLES LIKE '.$db->quote(
            $db->getPrefix().'minimarket_product_attribute_multi_values'
        ));
        if ($db->loadResult()) return;

        $files = glob($dir.'/*.sql');
        if (!$files) return;
        $latest = $dir.'/0.5.73.sql';
        if (!is_file($latest)) return;
        foreach ($files as $file) {
            $filename = basename($file, '.sql');
            if (!preg_match('/^\d+(?:\.\d+){2}$/D', $filename)
                || version_compare($filename, '0.5.73', '>')) {
                // Future or foreign schema files must never be retired.
                return;
            }
        }
        $marker = "-- MiniMarket 0.5.73 canonical baseline for Joomla Database Maintenance.\n"
            . "-- Prior migration files are archived outside sql/updates/mysql.\n"
            . "-- Upgrade SQL remains available in the versioned project repository.\n"
            . "SELECT 1;\n";
        if (count($files) === 1 && file_get_contents($latest) === $marker) {
            return;
        }

        $archive = JPATH_ADMINISTRATOR
            . '/components/com_minimarket/sql/archived-updates/mysql/'
            . gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        if (!mkdir($archive, 0755, true) && !is_dir($archive)) {
            throw new \RuntimeException('Cannot create migration history backup directory');
        }
        foreach ($files as $file) {
            $copy = $archive.'/'.basename($file);
            if (!copy($file, $copy)
                || hash_file('sha256', $file) !== hash_file('sha256', $copy)) {
                throw new \RuntimeException('Cannot back up '.basename($file));
            }
        }
        if (file_put_contents($latest, $marker, LOCK_EX) === false) {
            throw new \RuntimeException('Cannot write canonical Joomla schema marker');
        }
        foreach ($files as $file) {
            if (basename($file) !== '0.5.73.sql' && !unlink($file)) {
                throw new \RuntimeException('Cannot retire old SQL file '.basename($file));
            }
        }
    }

    private function migrateGridOptions(DatabaseInterface $db): void
    {
        $q=$db->getQuery(true)
            ->select(['extension_id','params'])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type').'='.$db->quote('component'))
            ->where($db->quoteName('element').'='.$db->quote('com_minimarket'));
        $db->setQuery($q);
        $row=$db->loadAssoc();
        if (!$row || (int)$row['extension_id']<1) return;
        $params=json_decode((string)($row['params']??''),true);
        if (!is_array($params)) return;
        $legacy=[
            'category_cards_mobile'=>'category_cards_xs',
            'category_cards_tablet'=>'category_cards_md',
            'category_cards_desktop'=>'category_cards_lg',
            'category_cards_wide'=>'category_cards_xl'
        ];
        $hasLegacy=false;
        foreach ($legacy as $previous=>$next) {
            if (!array_key_exists($previous,$params)) continue;
            $hasLegacy=true;
            if (!array_key_exists($next,$params)) {
                $params[$next]=max(1,min(6,(int)$params[$previous]));
            }
            unset($params[$previous]);
        }
        if (!$hasLegacy) return;
        // These two new tiers should preserve the old nearest neighbor.
        if (!array_key_exists('category_cards_sm',$params)) {
            $params['category_cards_sm']=$params['category_cards_xs']??1;
        }
        if (!array_key_exists('category_cards_xxl',$params)) {
            $params['category_cards_xxl']=$params['category_cards_xl']??4;
        }
        $encoded=json_encode($params,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        $q=$db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params').'='.$db->quote($encoded))
            ->where($db->quoteName('extension_id').'='.(int)$row['extension_id']);
        $db->setQuery($q);
        $db->execute();
    }
}
