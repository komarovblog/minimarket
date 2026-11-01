<?php
namespace Joomla\Plugin\Content\Minimarketmenu\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Keep the per-menu primary flag exclusive without touching other menu data.
 *
 * This does not create any Joomla menu items or alter the core save routine.
 */
final class PrimaryMenuSync
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    /**
     * Only ordinary component links of the exact MiniMarket storefront view.
     */
    public static function isShopLink(string $link): bool
    {
        $parts = parse_url($link);

        if (!is_array($parts) || ($parts['path'] ?? '') !== 'index.php') {
            return false;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        return ($query['option'] ?? null) === 'com_minimarket'
            && ($query['view'] ?? null) === 'catalog';
    }

    public function disableOthers(int $currentId, int $componentId): int
    {
        if ($currentId < 1 || $componentId < 1) {
            return 0;
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('link'),
                $db->quoteName('params'),
            ])
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = 0')
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('component_id') . ' = ' . $componentId)
            ->where($db->quoteName('id') . ' <> ' . $currentId);

        $db->setQuery($query);
        $items = (array) $db->loadObjectList();
        $changed = 0;

        foreach ($items as $item) {
            // Do not touch aliases, other component views, or unrelated menu items.
            if (!self::isShopLink((string) $item->link)) {
                continue;
            }

            $params = json_decode((string) $item->params, true);

            if (!is_array($params) || (int) ($params['shop_primary_route'] ?? 0) !== 1) {
                continue;
            }

            // Preserve every unknown parameter, including show_categories,
            // show_products, sorting, pagination and Joomla settings.
            $params['shop_primary_route'] = 0;
            $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                continue;
            }

            $update = $db->getQuery(true)
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('params') . ' = ' . $db->quote($json))
                ->where($db->quoteName('id') . ' = ' . (int) $item->id);

            $db->setQuery($update);
            $db->execute();
            ++$changed;
        }

        return $changed;
    }
}
