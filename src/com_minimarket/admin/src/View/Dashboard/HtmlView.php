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
    public string $version='';
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
            'orders'       => ['orders', 'deleted_at IS NULL'],
             'documents'    => ['order_documents', '1=1'],
            'tickets'      => ['support_tickets', "status IN ('new','in_progress')"],
            'deliveries'   => ['shipments', "status IN ('new','in_transit')"],
            'returns'      => ['returns', "status IN ('new','approved','received')"],
            'deliveryzones'=> ['delivery_zones','1=1'],
            'reviews'      => ['reviews', 'state=0'],
            'seofilters'   => ['seo_filters','1=1'],
            'promocodes'   => ['promo_codes','1=1'],
            'autodiscounts'=> ['auto_discounts','1=1'],
            'recommendations'=> ['recommendation_settings','1=1'],
        ] as $key=>[$table,$predicate]) {
            $db->setQuery('SELECT COUNT(*) FROM '
                .$db->quoteName('#__minimarket_'.$table).' WHERE '.$predicate);
            $this->stats[$key]=(int)$db->loadResult();
        }
        // Payment register contains an obligation row for every active order.
        // Reuse the same count; avoid duplicate SQL and invented receipts.
        $this->stats['payments']=$this->stats['orders'];
        // Only explicit verified Joomla user links; guest orders stay unmerged.
        $db->setQuery('SELECT COUNT(DISTINCT user_id) FROM '
            .$db->quoteName('#__minimarket_orders')
            .' WHERE user_id>0 AND deleted_at IS NULL');
        $this->stats['customers']=(int)$db->loadResult();
        $work=$this->stats['tickets'];
        foreach([
            ['orders',"payment_status IN ('unpaid','processing') AND status NOT IN ('cancelled','completed') AND deleted_at IS NULL"],
            ['shipments',"problem_status='open'"],
            ['returns',"status IN ('new','approved','received')"]
        ] as [$table,$condition]){
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_'.$table)
                .' WHERE '.$condition);
            $work+=(int)$db->loadResult();
        }
        $this->stats['workqueue']=$work;
        // Alert badges describe what needs action, never all stored records.
        foreach([
            'orders_new'=>['orders',"status='new' AND deleted_at IS NULL"],
            'deliveries_attention'=>['shipments',"status='new' OR problem_status='open'"],
            'returns_new'=>['returns',"status='new'"],
            'payments_attention'=>['orders',
                "payment_status IN ('unpaid','processing') AND status<>'cancelled' AND deleted_at IS NULL"],
            'tickets_new'=>['support_tickets',"status='new'"],
            'fiscal_attention'=>['fiscal_operations',"status IN ('failed','pending')"],
            'abandonedcarts'=>['cart_snapshots',
                \MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore::abandonedPredicate('c')],
        ] as $key=>[$table,$predicate]){
            $source=$db->quoteName('#__minimarket_'.$table,
                $key==='abandonedcarts'?'c':null);
            $db->setQuery('SELECT COUNT(*) FROM '.$source.' WHERE '.$predicate);
            $this->stats[$key]=(int)$db->loadResult();
        }
        try{
            $unread=\MiniMarket\Component\Minimarket\Administrator\Service\WorkqueueReads::unreadCount(
                $db,(int)Factory::getApplication()->getIdentity()->id);
            $this->stats['notifications_unread']=(int)$unread['count'];
            $this->stats['notifications_capped']=(bool)$unread['capped'];
        }catch(\Throwable $e){
            $this->stats['notifications_unread']=null;
            $this->stats['notifications_capped']=false;
        }

        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.dashboard',
                'media/com_minimarket/css/dashboard.css');
        // Installed component version, not the separately installed quick-link module.
        if(method_exists($db,'getQuery')){
            $db->setQuery($db->getQuery(true)->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type').'='.$db->quote('component'))
                ->where($db->quoteName('element').'='.$db->quote('com_minimarket')));
            $manifest=json_decode((string)$db->loadResult(),true);
            $version=is_array($manifest)?(string)($manifest['version']??''):'';
            $this->version=preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D',$version)?$version:'';
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_TITLE'));

        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
