<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Recommendation;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView {
 public ?object $product=null;
 public array $modes=['similar'=>'auto','together'=>'auto'];
 public array $similar=[];
 public array $together=[];
 public function display($tpl=null) {
  $app=Factory::getApplication();$id=$app->input->getInt('product_id',0);
  if(!$app->getIdentity()->authorise('core.edit','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  if($id<1)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
  $db=Factory::getContainer()->get(DatabaseInterface::class);
  $db->setQuery($db->getQuery(true)->select(['id','title','sku'])
   ->from($db->quoteName('#__minimarket_products'))->where('id='.$id));
  $this->product=$db->loadObject()?:null;
  if(!$this->product)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
  $this->modes=\MiniMarket\Component\Minimarket\Site\Service\ProductRecommendations::modes($db,$id);
  $q=$db->getQuery(true)->select(['l.kind','p.id','p.title'])
   ->from($db->quoteName('#__minimarket_recommendation_links','l'))
   ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=l.target_product_id')
   ->where('l.source_product_id='.$id)->order('l.kind ASC,l.sort_order ASC,l.id ASC');
  $db->setQuery($q);
  foreach((array)$db->loadAssocList() as $row) {
   $kind=(string)$row['kind'];
   if(in_array($kind,['similar','together'],true))
    $this->{$kind}[(int)$row['id']]=(string)$row['title'];
  }
  $this->setLayout('edit');
  ToolbarHelper::title(Text::_('COM_MINIMARKET_RECOMMENDATIONS_TITLE'));
  parent::display($tpl);
 }
}
