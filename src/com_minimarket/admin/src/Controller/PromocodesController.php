<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;

final class PromocodesController extends BaseController
{

    public function add(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->setRedirect('index.php?option=com_minimarket&view=promocode&layout=create');
    }

    public function publish(): void
    {
        $this->change('#__minimarket_promo_codes', 'state', '1', ['0','1'], false, false);
    }

    public function unpublish(): void
    {
        $this->change('#__minimarket_promo_codes', 'state', '0', ['0','1'], false, false);
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
        $this->setRedirect('index.php?option=com_minimarket&view=promocodes');
    }
}
