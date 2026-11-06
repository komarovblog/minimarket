<?php
/**
 * MiniMarket quick link for the Joomla administrator top status position.
 * Installed as a native administrator module; does not change Atum or Joomla.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

$identity = Factory::getApplication()->getIdentity();

// Access is checked even when an administrator publishes this module at a
// broadly visible access level. Public/site contexts must never render it.
if (!$identity->authorise('core.manage', 'com_minimarket')) {
    return;
}
if ((int) $params->get('enabled', 1) !== 1) {
    return;
}
$showIcon = (int) $params->get('show_icon', 1) === 1;
$showLabel = (int) $params->get('show_label', 1) === 1;
if (!$showIcon && !$showLabel) {
    return;
}
require ModuleHelper::getLayoutPath('mod_minimarket_toolbar', $params->get('layout', 'default'));
