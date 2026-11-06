<?php
/**
 * Standalone administrator module installer.
 * MiniMarket component itself no longer touches this module.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

class mod_minimarket_toolbarInstallerScript extends InstallerScript
{
    public function postflight($type, $parent)
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return true;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . '=' . $db->quote('mod_minimarket_toolbar'))
            ->where($db->quoteName('client_id') . '=1')
            ->order('id ASC'));
        $id = (int) $db->loadResult();
        if ($id < 1) {
            Factory::getApplication()->enqueueMessage(
                'MiniMarket shortcut: Joomla administrator module instance not found',
                'warning'
            );
            return true;
        }

        if ($type === 'install') {
            $db->setQuery($db->getQuery(true)
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('title') . '=' . $db->quote('MiniMarket'))
                ->set($db->quoteName('position') . '=' . $db->quote('status'))
                ->set($db->quoteName('published') . '=1')
                ->set($db->quoteName('showtitle') . '=0')
                ->set($db->quoteName('access') . '=3')
                ->set($db->quoteName('params') . '=' . $db->quote(json_encode([
                    'enabled' => 1, 'label' => 'MiniMarket',
                    'show_icon' => 1, 'show_label' => 1
                ], JSON_THROW_ON_ERROR)))
                ->where($db->quoteName('id') . '=' . $id));
            $db->execute();

            $db->setQuery($db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__modules_menu'))
                ->where($db->quoteName('moduleid') . '=' . $id));
            if ((int) $db->loadResult() === 0) {
                $db->setQuery($db->getQuery(true)
                    ->insert($db->quoteName('#__modules_menu'))
                    ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
                    ->values($id . ',0'));
                $db->execute();
            }
        }

        // Only adjust ordering for modules in the normal Joomla "status"
        // position. If the user moved the shortcut elsewhere, leave it there.
        $db->setQuery($db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('module'), $db->quoteName('ordering')])
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('client_id') . '=1')
            ->where($db->quoteName('position') . '=' . $db->quote('status'))
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('id') . ' ASC'));
        $rows = (array) $db->loadAssocList();
        $selfIndex = null;
        $versionIndex = null;
        foreach ($rows as $index => $row) {
            if ((int) $row['id'] === $id) $selfIndex = $index;
            if ($row['module'] === 'mod_version' && $versionIndex === null) {
                $versionIndex = $index;
            }
        }
        if ($selfIndex === null || $versionIndex === null) {
            return true;
        }
        if ($selfIndex === $versionIndex - 1) {
            return true;
        }

        // Reassign ordering only for the status position, retaining all
        // other modules' relative order. This deterministically places the
        // shortcut immediately to the left of the Joomla version control.
        $shortcut = $rows[$selfIndex];
        array_splice($rows, $selfIndex, 1);
        $versionIndex = null;
        foreach ($rows as $index => $row) {
            if ($row['module'] === 'mod_version') {
                $versionIndex = $index;
                break;
            }
        }
        if ($versionIndex === null) {
            return true;
        }
        array_splice($rows, $versionIndex, 0, [$shortcut]);
        foreach ($rows as $index => $row) {
            $ordering = $index + 1;
            if ((int) $row['ordering'] === $ordering) continue;
            $db->setQuery($db->getQuery(true)
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('ordering') . '=' . $ordering)
                ->where($db->quoteName('id') . '=' . (int)$row['id'])
                ->where($db->quoteName('client_id') . '=1'));
            $db->execute();
        }
        return true;
    }
}
