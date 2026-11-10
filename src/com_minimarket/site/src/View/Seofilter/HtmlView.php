<?php
namespace MiniMarket\Component\Minimarket\Site\View\Seofilter;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\FacetedCatalog;
use MiniMarket\Component\Minimarket\Site\View\Category\HtmlView as CategoryView;

/** Reuses the SAME real category filter engine, but owns URL/SEO content. */
final class HtmlView extends CategoryView
{
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $seoId=$app->input->getInt('id',0);
        if($seoId<1)throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['s.id','s.category_id','s.title',
            's.heading','s.introtext','s.description','s.meta_description',
            's.meta_robots','s.filters_json'])
            ->from($db->quoteName('#__minimarket_seo_filters','s'))
            ->join('INNER',$db->quoteName('#__minimarket_categories','c').' ON c.id=s.category_id')
            ->where('s.id='.(int)$seoId)->where('s.state=1')->where('c.state=1');
        $db->setQuery($q);$seo=$db->loadObject();
        if(!$seo)throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'),404);
        $filters=json_decode((string)$seo->filters_json,true);
        if(!is_array($filters)||!$filters) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'),404);
        }
        $this->seoHeading=(string)$seo->heading;
        $this->seoIntro=(string)$seo->introtext;
        $this->seoDescription=(string)$seo->description;
        $app->input->set('id',(int)$seo->category_id);
        $app->input->set('mmf',$filters);
        parent::display($tpl);
        $document=$this->getDocument();
        $document->setTitle((string)$seo->title);
        if(trim((string)$seo->meta_description)!=='')
            $document->setDescription((string)$seo->meta_description);
        // A landing with no currently valid facets must not be indexed.
        $robots=FacetedCatalog::active($this->facetSelection)
            ?(string)$seo->meta_robots:'noindex,follow';
        $document->setMetaData('robots',$robots);
        $document->setMetaData('og:title',(string)$seo->title,'property');
        if(trim((string)$seo->meta_description)!=='') {
            $document->setMetaData('og:description',(string)$seo->meta_description,'property');
        }
        SeoHelper::setCanonical($document,'seofilter',$seoId);
    }
}
