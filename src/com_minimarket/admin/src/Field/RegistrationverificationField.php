<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;

/**
 * Registration providers in the native Joomla Options selector.
 *
 * Only the built-in email provider currently has a working checkout OTP
 * implementation. Installed but unimplemented provider plugins are visible
 * as disabled rows, never as a false promise of working verification.
 */
final class RegistrationverificationField extends ListField
{
    protected $type = 'Registrationverification';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $email = HTMLHelper::_('select.option','email',
            Text::_('COM_MINIMARKET_REGISTRATION_EMAIL_METHOD'));
        // Email OTP works for signed-in members even when new Joomla user
        // registration is disabled. Keep it selectable for that workflow.
        $options[] = $email;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        foreach (InstalledMethodPlugins::list($db,'registration') as $plugin) {
            if ((int)$plugin->enabled !== 1) continue;
            $label = Text::_((string)$plugin->name).' — '
                .Text::_('COM_MINIMARKET_VERIFICATION_PLUGIN_UNAVAILABLE');
            $option = HTMLHelper::_('select.option',
                'plugin_'.(int)$plugin->extension_id,$label);
            $option->disable = true;
            $options[] = $option;
        }
        return $options;
    }
}
