<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $stats=[];
    public function display($tpl = null)
    {
        $db=Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        // Use the same meaning as each list screen: all records, including
        // drafts, while simple/bundle groups are filtered by their type.
        // The mapping is static: table names/SQL conditions are never input.
        foreach ([
            'categories'   => ['categories', '1=1'],
            'products'     => ['products', "product_type='simple'"],
            'bundles'      => ['products', "product_type='bundle'"],
            'brands'       => ['brands', '1=1'],
            'collections'  => ['collections', '1=1'],
            'groups'       => ['product_groups', "type<>'bundle'"],
            'bundlegroups' => ['product_groups', "type='bundle'"],
            'attributes'   => ['attributes', '1=1'],
            'orderoptions' => ['order_options', '1=1'],
            'feeds'        => ['feeds', '1=1'],
        ] as $key=>[$table,$predicate]) {
            $db->setQuery('SELECT COUNT(*) FROM '
                .$db->quoteName('#__minimarket_'.$table).' WHERE '.$predicate);
            $this->stats[$key]=(int)$db->loadResult();
        }
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.dashboard',
                'media/com_minimarket/css/dashboard.css');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_TITLE'));

        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
