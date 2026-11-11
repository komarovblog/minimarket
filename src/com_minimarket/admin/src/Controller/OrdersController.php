<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;

final class OrdersController extends BaseController
{

    public function confirm(): void
    {
        $this->change('#__minimarket_orders', 'status', 'confirmed', ['new','confirmed'], true, false);
    }

    public function cancel(): void
    {
        $this->change('#__minimarket_orders', 'status', 'cancelled', ['new','confirmed','cancelled'], true, false);
    }

    public function trash(): void
    {
        $this->change('#__minimarket_orders', 'status', '', ['cancelled'], true, true);
    }

    public function restore(): void
    {
        $app=Factory::getApplication();
        try {
            $count=ListBulkActions::restoreOrders();
            $app->enqueueMessage(Text::sprintf('COM_MINIMARKET_LIST_BULK_DONE',$count));
        } catch(\DomainException $e) {
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=orders&filter_trash=1');
    }

    private function change(
        string $table, string $field, string $value, array $eligible,
        bool $onlyLiveOrders, bool $softDelete
    ): void {
        $app = Factory::getApplication();
        try {
            $count = ListBulkActions::apply($table, $field, $value, $eligible, $onlyLiveOrders, $softDelete);
            $app->enqueueMessage(Text::sprintf('COM_MINIMARKET_LIST_BULK_DONE', $count));
        } catch (\DomainException $e) {
            $app->enqueueMessage(Text::_($e->getMessage()), 'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=orders');
    }
}
