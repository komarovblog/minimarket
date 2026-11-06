<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

/**
 * Joomla-owned dashboard/menu registration. No Atum/core file patching.
 * A second component upgrade must not create a second submenu module.
 */
class com_minimarketInstallerScript extends InstallerScript
{
    public function postflight($type, $parent)
    {
        if (!in_array($type, ['install','update'], true)) return true;
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module').'='.$db->quote('mod_submenu'))
            ->where($db->quoteName('position').'='.$db->quote('cpanel-minimarket'));
        $db->setQuery($q);
        if ((int)$db->loadResult()===0) {
            $this->addDashboardMenu('minimarket','minimarket');
        }
        return true;
    }
}
