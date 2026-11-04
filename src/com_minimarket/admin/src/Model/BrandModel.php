<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
final class BrandModel extends AdminModel
{
    public function getForm($data=[],$loadData=true)
    { return $this->loadForm('com_minimarket.brand','brand',['control'=>'jform','load_data'=>$loadData]); }
    public function delete(&$pks)
    {
        $ids=BulkDeleteGuard::normalizeIds($pks);
        if (!$ids) { $this->setError(Text::_('COM_MINIMARKET_BULK_EMPTY')); return false; }
        $db=$this->getDatabase();
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_products'))
            ->where($db->quoteName('brand_id').' IN ('.implode(',',$ids).')');
        $db->setQuery($q);
        if ((int)$db->loadResult()>0) {
            $this->setError(Text::_('COM_MINIMARKET_BRAND_IN_USE')); return false;
        }
        return parent::delete($pks);
    }
    protected function loadFormData()
    {
        $data=Factory::getApplication()->getUserState('com_minimarket.edit.brand.data',[]);
        return empty($data)?$this->getItem():$data;
    }
}
