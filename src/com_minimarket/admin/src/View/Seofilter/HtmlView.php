<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Seofilter;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $categories=[];
    public ?object $page=null;
    public array $facets=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $id=$app->input->getInt('id',0);
        $this->setLayout('create');
        if(!$app->getIdentity()->authorise($id>0?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title'])->from($db->quoteName('#__minimarket_categories'))
            ->where('state=1')->order('title ASC,id ASC');
        $db->setQuery($q,0,5000);$this->categories=(array)$db->loadObjectList();
        if($id>0){
            $db->setQuery($db->getQuery(true)->select('*')
                ->from($db->quoteName('#__minimarket_seo_filters'))->where('id='.(int)$id));
            $this->page=$db->loadObject()?:null;
            if(!$this->page)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        }
        // Published dictionary choices, not raw URL strings, are displayed in the editor.
        $q=$db->getQuery(true)->select(['a.id AS attribute_id','a.title AS attribute_title',
            'v.id AS value_id','v.title AS value_title'])
            ->from($db->quoteName('#__minimarket_attributes','a'))
            ->join('INNER',$db->quoteName('#__minimarket_attribute_values','v').' ON v.attribute_id=a.id')
            ->where('a.state=1')->where('a.is_filterable=1')->where('v.state=1')
            ->order('a.title ASC,v.sort_order ASC,v.id ASC');
        $db->setQuery($q,0,5000);$this->facets=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SEOFILTER_CREATE'));
        parent::display($tpl);
    }
}
