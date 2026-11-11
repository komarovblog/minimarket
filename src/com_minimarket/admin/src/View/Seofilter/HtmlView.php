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
    public bool $canPublish=false;
    public $mediaForm=null;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $id=$app->input->getInt('id',0);
        $this->canPublish=$app->getIdentity()->authorise('core.edit.state','com_minimarket');
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
        // AJAX returns only category-bound attribute/option dictionaries.
        // Joomla's native media manager (never construct a private file picker).
        \Joomla\CMS\Form\Form::addFormPath(JPATH_ADMINISTRATOR.'/components/com_minimarket/forms');
        $this->mediaForm=\Joomla\CMS\Form\Form::getInstance(
            'com_minimarket.seofilter_media','seofilter_media',['control'=>'']);
        $this->mediaForm->bind([
            'image'=>(string)($this->page->image??''),
            'og_image'=>(string)($this->page->og_image??'')
        ]);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SEOFILTER_CREATE'));
        parent::display($tpl);
    }
}
