<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/** One list data contract for built-in and plugin delivery/payment methods. */
final class MethodListRows
{
    public static function collect(
        string $kind,
        Registry $params,
        array $plugins,
        bool $canEdit,
        bool $canEditPlugins,
        string $search,
        string $typeFilter,
        string $statusFilter,
        string $sort,
        int $limit,
        int $offset
    ): array {
        $methods = match ($kind) {
            'delivery' => [
                'pickup' => ['COM_MINIMARKET_ORDER_DELIVERY_PICKUP', 'delivery_pickup_enabled', 1],
                'courier' => ['COM_MINIMARKET_ORDER_DELIVERY_COURIER', 'delivery_courier_enabled', 0],
            ],
            'registration' => [
                'email' => ['COM_MINIMARKET_REGISTRATION_EMAIL_METHOD', 'allowUserRegistration', 0],
            ],
            default => [
                'on_receipt' => ['COM_MINIMARKET_PAYMENT_ON_RECEIPT', 'payment_on_receipt_enabled', 1],
                'bank_transfer' => ['COM_MINIMARKET_PAYMENT_BANK_TRANSFER', 'payment_bank_transfer_enabled', 0],
            ],
        };

        $view = match ($kind) {
            'delivery' => 'deliverymethods',
            'registration' => 'registrationmethods',
            default => 'paymentmethods',
        };
        $rows = [];

        foreach ($methods as $method => [$label, $param, $default]) {
            $rows[] = [
                'name' => Text::_($label),
                'code' => $method,
                'quick_id' => $method,
                'type' => 'builtin',
                'enabled' => (int) $params->get($param, $default) === 1,
                'url' => $canEdit
                    ? Route::_('index.php?option=com_minimarket&view=' . $view . '&method=' . $method, false)
                    : '',
            ];
        }

        foreach ($plugins as $plugin) {
            $rows[] = [
                'name' => Text::_((string) $plugin->name),
                'code' => (string) $plugin->element,
                'quick_id' => 'plugin_'.(int)$plugin->extension_id,
                'type' => 'plugin',
                'enabled' => (int) $plugin->enabled === 1,
                'url' => Route::_('index.php?option=com_minimarket&view='.$view
                    .'&method=plugin_'.(int)$plugin->extension_id,false),
                'plugin_info_url' => $canEditPlugins
                    ? Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id='
                        . (int)$plugin->extension_id,false) : '',
            ];
        }

        $search = mb_strtolower(trim($search));
        $rows = array_values(array_filter($rows, static function (array $row) use (
            $search, $typeFilter, $statusFilter
        ): bool {
            if ($typeFilter !== '' && $typeFilter !== $row['type']) {
                return false;
            }
            if ($statusFilter !== 'all'
                && $row['enabled'] !== ($statusFilter === 'enabled')) {
                return false;
            }
            return $search === '' || str_contains(
                mb_strtolower($row['name'] . ' ' . $row['code']), $search
            );
        }));

        usort($rows, static fn(array $a, array $b): int =>
            ($sort === 'name_desc' ? -1 : 1) *
            (strnatcasecmp($a['name'], $b['name']) ?: strcmp($a['code'], $b['code'])));

        $total = count($rows);
        $offset = $total === 0 ? 0 : min(
            max(0, $offset),
            (int) (floor(($total - 1) / $limit) * $limit)
        );

        return [
            'rows' => array_slice($rows, $offset, $limit),
            'total' => $total,
            'offset' => $offset,
        ];
    }
}
