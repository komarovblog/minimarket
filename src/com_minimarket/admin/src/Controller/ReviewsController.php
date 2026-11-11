<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;

final class ReviewsController extends BaseController
{

    public function add(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->setRedirect('index.php?option=com_minimarket&view=review&layout=create');
    }

    public function publish(): void
    {
        $this->change('#__minimarket_reviews', 'state', '1', ['0','1','2'], false, false);
    }

    public function reject(): void
    {
        $this->change('#__minimarket_reviews', 'state', '2', ['0','1','2'], false, false);
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
        $this->setRedirect('index.php?option=com_minimarket&view=reviews');
    }
}
