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
        return true;
    }
}
