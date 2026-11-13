<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Document;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderDocuments;

final class HtmlView extends BaseHtmlView
{
    public ?object $document=null;
    public array $snapshot=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_order_documents')
            .' WHERE id='.$id);
        $this->document=$db->loadObject()?:null;
        if(!$this->document)
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $this->snapshot=OrderDocuments::decode(
            (string)$this->document->snapshot_json,(string)$this->document->snapshot_sha256);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DOCUMENTS_TITLE')
            .' — '.(string)$this->document->document_number);
        parent::display($tpl);
    }
}
