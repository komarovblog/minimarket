<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** A confirmed delete from the standalone group editor (never deletes products). */
final class GroupController extends FormController
{
    protected $context = 'group';

    public function deleteRelationship(): void
    {
        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $id = $app->input->getInt('id', 0);
        $posted = $app->input->post->get('jform', [], 'array');
        if ($id < 1 || (int) ($posted['id'] ?? 0) !== $id) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'), 400);
        }
        $model = $this->getModel('Group', 'Administrator');
        if (!$model) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
        }
        $ids = [$id];
        if (!$model->delete($ids)) {
            $this->setRedirect(
                Route::_('index.php?option=com_minimarket&task=group.edit&id='.$id, false),
                (string) ($model->getError() ?: Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID')),
                'error'
            );
            return;
        }
        $this->setRedirect(
            Route::_('index.php?option=com_minimarket&view=groups', false),
            Text::_('COM_MINIMARKET_GROUP_UI_DELETED')
        );
    }
}
