<?php
namespace Joomla\Plugin\Content\Minimarketmenu\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Content\Minimarketmenu\Service\PrimaryMenuSync;

/**
 * Only handles saving a public MiniMarket Shop menu item.
 *
 * The ordinary Joomla com_menus save still owns the item; this plugin
 * adjusts just shop_primary_route on other ordinary MiniMarket menu items.
 */
final class Minimarketmenu extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentAfterSave' => 'onContentAfterSave',
        ];
    }

    public function onContentAfterSave(AfterSaveEvent $event): void
    {
        if ($event->getContext() !== 'com_menus.item') {
            return;
        }

        $item = $event->getItem();

        // Do not activate a draft, save-as-copy, menulink alias, or foreign
        // component. An unpublished copy must not claim primary URL.
        if ((int) ($item->id ?? 0) < 1
            || (int) ($item->client_id ?? -1) !== 0
            || (int) ($item->published ?? 0) !== 1
            || (string) ($item->type ?? '') !== 'component'
            || !PrimaryMenuSync::isShopLink((string) ($item->link ?? ''))) {
            return;
        }

        $rawParams = (string) ($item->params ?? '');
        $params = json_decode($rawParams, true);

        if (!is_array($params) || (int) ($params['shop_primary_route'] ?? 0) !== 1) {
            return;
        }

        (new PrimaryMenuSync($this->getDatabase()))->disableOthers(
            (int) $item->id,
            (int) ($item->component_id ?? 0)
        );
    }
}
