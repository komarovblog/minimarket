<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Promocode;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $promo=null;
    public bool $canPublish=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $id=$app->input->getInt('id',0);
        $acl=$id>0?'core.edit':'core.create';
        $this->canPublish=$app->getIdentity()->authorise('core.edit.state','com_minimarket');
        if (!$app->getIdentity()->authorise($acl,'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        if ($id>0) {
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('*')
                ->from($db->quoteName('#__minimarket_promo_codes'))->where('id='.(int)$id));
            $this->promo=$db->loadObject()?:null;
            if (!$this->promo) throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        }
        $this->setLayout('create');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PROMOCODES_TITLE'));
        parent::display($tpl);
    }
}
