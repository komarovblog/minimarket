<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

/**
 * Select an ordinary, published MiniMarket storefront menu item.
 *
 * Component options contain one ID, so the choice is intrinsically exclusive.
 * Joomla's other menu components and menu-item aliases are not offered here.
 */
final class ShopmenuField extends ListField
{
    protected $type = 'Shopmenu';

    protected function getOptions()
    {
        // Includes the static "Automatic" option from admin/config.xml.
        $options = parent::getOptions();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('m.id'),
                $db->quoteName('m.title'),
                $db->quoteName('m.path'),
                $db->quoteName('m.home'),
                $db->quoteName('m.link'),
                $db->quoteName('m.language'),
            ])
            ->from($db->quoteName('#__menu', 'm'))
            ->join('INNER',
                $db->quoteName('#__extensions', 'e') . ' ON '
                . $db->quoteName('e.extension_id') . ' = ' . $db->quoteName('m.component_id')
            )
            ->where($db->quoteName('e.element') . ' = ' . $db->quote('com_minimarket'))
            ->where($db->quoteName('e.type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('m.client_id') . ' = 0')
            ->where($db->quoteName('m.published') . ' = 1')
            ->where($db->quoteName('m.type') . ' = ' . $db->quote('component'))
            ->order($db->quoteName('m.id') . ' ASC');

        $db->setQuery($query);

        foreach ((array) $db->loadObjectList() as $item) {
            if (!self::isStorefrontLink((string) $item->link)) {
                continue;
            }

            $url = (int) $item->home === 1 ? '/' : '/' . ltrim((string) $item->path, '/');
            $language = (string) $item->language === '*' ? '' : ' [' . $item->language . ']';
            $text = (string) $item->title . ' (' . $url . ', ID ' . (int) $item->id . ')' . $language;

            $options[] = HTMLHelper::_('select.option', (int) $item->id, $text);
        }

        return $options;
    }

    public static function isStorefrontLink(string $link): bool
    {
        $parts = parse_url($link);

        if ($parts === false || ($parts['path'] ?? null) !== 'index.php') {
            return false;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        return ($query['option'] ?? null) === 'com_minimarket'
            && ($query['view'] ?? null) === 'catalog';
    }
}
