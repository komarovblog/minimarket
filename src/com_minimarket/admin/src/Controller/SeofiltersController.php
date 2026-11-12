<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoLandingRules;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoCategoryChoices;
use Joomla\Database\DatabaseInterface;

final class SeofiltersController extends BaseController
{

    public function add(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->setRedirect('index.php?option=com_minimarket&view=seofilter&layout=create');
    }


    /**
     * Validate the entire target set before publishing anything.
     * The generic bulk state updater would bypass landing-rule conflicts.
     * Lock category rows in a stable order, matching the single-item editor.
     */
    public function publish(): void
    {
        $app=Factory::getApplication();
        try {
            $ids=ListBulkActions::selected('core.edit.state');
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->transactionStart();
            try {
                $in=implode(',',array_map('intval',$ids));
                $query=$db->getQuery(true)
                    ->select(['id','category_id','state','conditions_json'])
                    ->from($db->quoteName('#__minimarket_seo_filters'))
                    ->where('id IN ('.$in.')');
                $db->setQuery($query);
                $target=(array)$db->loadAssocList();
                if(count($target)!==count($ids))
                    throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
                $categories=array_values(array_unique(array_map(
                    static fn($x)=>(int)$x['category_id'],$target)));
                sort($categories,SORT_NUMERIC);
                if(!$categories)
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_categories')
                    .' WHERE id IN ('.implode(',',$categories).') AND state=1'
                    .' ORDER BY id ASC FOR UPDATE');
                $locked=(array)$db->loadColumn();
                if(count($locked)!==count($categories))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $query=$db->getQuery(true)->select([
                    'id','category_id','state','conditions_json'
                ])->from($db->quoteName('#__minimarket_seo_filters'))
                  ->where('(state=1 OR id IN ('.$in.'))')
                  ->where('category_id IN ('.implode(',',$categories).')')
                  ->order('id ASC');
                $db->setQuery($query);
                $all=(array)$db->loadAssocList();
                $perCategory=[];
                $requested=array_fill_keys($ids,true);
                foreach($all as $row){
                    $category=(int)$row['category_id'];
                    $raw=(string)($row['conditions_json']??'');
                    if($raw==='' || $raw==='{}')continue; // legacy URLs preserved
                    $json=json_decode($raw,true,8);
                    if(!is_array($json))
                        throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                    $rules=SeoLandingRules::normalize($json);
                    if(isset($requested[(int)$row['id']]))
                        SeoCategoryChoices::validateConditions($db,$category,$rules);
                    foreach($perCategory[$category]??[] as $existing){
                        if(SeoLandingRules::specificity($existing)===SeoLandingRules::specificity($rules)
                            &&SeoLandingRules::overlap($existing,$rules))
                            throw new \DomainException('COM_MINIMARKET_SEOFILTER_CONFLICT');
                    }
                    $perCategory[$category][]=$rules;
                }
                $query=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'))
                    ->set('state=1')->where('id IN ('.$in.')');
                $db->setQuery($query);$db->execute();
                $db->transactionCommit();
                $app->enqueueMessage(Text::sprintf('COM_MINIMARKET_LIST_BULK_DONE',count($ids)));
            }catch(\Throwable $e){
                $db->transactionRollback();
                throw $e;
            }
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
    }

    public function unpublish(): void
    {
        $this->change('#__minimarket_seo_filters', 'state', '0', ['0','1'], false, false);
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
        $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
    }
}
