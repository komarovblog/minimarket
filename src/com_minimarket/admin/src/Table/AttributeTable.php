<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;

/** Only the canonical Manual/Preset storage contract is accepted. */
final class AttributeTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_attributes','id',$db);
        $this->setColumnAlias('published','state');
    }

    public function check()
    {
        $this->title=trim((string)$this->title);
        $this->code=strtolower(trim((string)$this->code));
        $this->input_type=(string)$this->input_type;
        if ($this->title==='' || mb_strlen($this->title)>255) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TITLE_INVALID'));
            return false;
        }
        if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D',$this->code)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_INVALID'));
            return false;
        }
        if (!in_array($this->input_type,AttributeSettings::TYPES,true)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID'));
            return false;
        }
        foreach (['is_multiple','is_filterable','state'] as $flag) {
            if (!in_array((int)($this->$flag??-1),[0,1],true)) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_FLAGS_INVALID'));
                return false;
            }
            $this->$flag=(int)$this->$flag;
        }
        if (($error=AttributeSettings::normalize($this))!==null) {
            $this->setError(Text::_($error));
            return false;
        }
        $order=trim((string)($this->sort_order??'0'));
        if (!preg_match('/^(0|[1-9][0-9]*)$/D',$order)
            || strlen($order)>10
            || (strlen($order)===10 && strcmp($order,'4294967295')>0)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_SORT_INVALID'));
            return false;
        }
        $this->sort_order=(int)$order;
        $db=$this->getDbo();
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('code').' = '.$db->quote($this->code))
            ->where($db->quoteName('id').' <> '.(int)($this->id??0));
        $db->setQuery($q);
        if ((int)$db->loadResult()>0) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_DUPLICATE'));
            return false;
        }
        $id=(int)($this->id??0);
        if ($id>0) {
            $q=$db->getQuery(true)->select(['input_type','value_source'])
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id').' = '.$id);
            $db->setQuery($q);
            $saved=$db->loadAssoc();
            $change=$saved && (
                (string)$saved['input_type']!==$this->input_type
                || (string)$saved['value_source']!==$this->value_source
            );
            foreach ([
                '#__minimarket_product_attribute_scalars',
                '#__minimarket_product_attribute_options',
                '#__minimarket_product_attribute_free_values'
            ] as $relation) {
                if ($change) {
                    $q=$db->getQuery(true)->select('COUNT(*)')
                        ->from($db->quoteName($relation))
                        ->where($db->quoteName('attribute_id').' = '.$id);
                    $db->setQuery($q);
                    if ((int)$db->loadResult()>0) {
                        $this->setError(Text::_('COM_MINIMARKET_UNIFIED_SOURCE_IN_USE'));
                        return false;
                    }
                }
            }
            if ($this->is_multiple===0) {
                foreach (['#__minimarket_product_attribute_options',
                    '#__minimarket_product_attribute_free_values'] as $relation) {
                    $q=$db->getQuery(true)->select('COUNT(*)')
                        ->from($db->quoteName($relation))
                        ->where($db->quoteName('attribute_id').' = '.$id)
                        ->group($db->quoteName('product_id'))
                        ->having('COUNT(*) > 1');
                    $db->setQuery($q);
                    if ((int)$db->loadResult()>0) {
                        $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_MULTIPLE_IN_USE'));
                        return false;
                    }
                }
            }
        }
        return parent::check();
    }
}
