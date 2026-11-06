<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

/**
 * Joomla-native dashboard and optional global header shortcut.
 * Never edits Joomla core or Atum files. The shortcut is a real administrator
 * module, so the site owner can unpublish, relocate or customize it.
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
        if ((int) $db->loadResult() === 0) {
            $this->addDashboardMenu('minimarket', 'minimarket');
        }

        // An optional nested extension should never make the catalog update
        // unusable. The independently installable module ZIP is also shipped.
        try {
            $this->installQuicklink($db);
        } catch (\Throwable $error) {
            Factory::getApplication()->enqueueMessage(
                'MiniMarket: failed to install the top-bar shortcut. '
                . 'Install the supplied mod_minimarket_quicklink ZIP manually. '
                . $error->getMessage(),
                'warning'
            );
        }

        return true;
    }

    private function installQuicklink(DatabaseInterface $db): void
    {
        $source = JPATH_ADMINISTRATOR
            . '/components/com_minimarket/modules/mod_minimarket_quicklink';
        if (!is_file($source . '/mod_minimarket_quicklink.xml')) {
            throw new \RuntimeException('Bundled quicklink module manifest not found');
        }

        // Check before installation: Joomla creates an unpublished instance
        // only when the module is first installed. Do not reset an existing
        // administrator's published state, access, position, label, or menu
        // assignment during a component upgrade.
        $query = $db->getQuery(true)
            ->select('extension_id')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . '=' . $db->quote('module'))
            ->where($db->quoteName('element') . '=' . $db->quote('mod_minimarket_quicklink'))
            ->where($db->quoteName('client_id') . '=1');
        $db->setQuery($query);
        $alreadyInstalled = (int) $db->loadResult() > 0;

        // Use a new installer object: the component install is still active.
        $installer = new Installer();
        $installer->setDatabase($db);
        if (!$installer->install($source)) {
            throw new \RuntimeException('Joomla module installer returned false');
        }
        if ($alreadyInstalled) {
            return;
        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . '=' . $db->quote('mod_minimarket_quicklink'))
            ->where($db->quoteName('client_id') . '=1')
            ->order('id DESC');
        $db->setQuery($query);
        $moduleId = (int) $db->loadResult();
        if ($moduleId < 1) {
            throw new \RuntimeException('Joomla did not create the quicklink module instance');
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('title') . '=' . $db->quote('MiniMarket'))
            ->set($db->quoteName('position') . '=' . $db->quote('status'))
            ->set($db->quoteName('published') . '=1')
            ->set($db->quoteName('showtitle') . '=0')
            ->set($db->quoteName('access') . '=3')
            ->set($db->quoteName('params') . '=' . $db->quote(
                json_encode([
                    'enabled' => 1,
                    'label' => 'MiniMarket',
                    'show_icon' => 1,
                    'show_label' => 1,
                ], JSON_THROW_ON_ERROR)
            ))
            ->where($db->quoteName('id') . '=' . $moduleId);
        $db->setQuery($query);
        $db->execute();

        // One assignment to all administrator pages (not to the login
        // screen, because the module is installed in the admin client and
        // further checks core.manage on every render).
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__modules_menu'))
            ->where($db->quoteName('moduleid') . '=' . $moduleId);
        $db->setQuery($query);
        if ((int) $db->loadResult() === 0) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__modules_menu'))
                ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
                ->values($moduleId . ',0');
            $db->setQuery($query);
            $db->execute();
        }
    }
}
