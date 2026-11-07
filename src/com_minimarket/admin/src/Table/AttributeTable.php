<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;

final class AttributeTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_attributes','id',$db);
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title=trim((string)$this->title);
        $this->code=strtolower(trim((string)$this->code));
        $this->input_type=(string)$this->input_type;
        if ($this->title==='' || mb_strlen($this->title)>255) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TITLE_INVALID'));return false;
        }
        if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D',$this->code)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_INVALID'));return false;
        }
        if (!in_array($this->input_type,AttributeSettings::TYPES,true)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID'));return false;
        }
        foreach(['is_multiple','is_filterable','state'] as $field) {
            if (!in_array((int)$this->$field,[0,1],true)) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_FLAGS_INVALID'));return false;
            }
            $this->$field=(int)$this->$field;
        }
        $error=AttributeSettings::normalize($this);
        if ($error!==null) {$this->setError(Text::_($error));return false;}
        $sort=trim((string)$this->sort_order);
        if (!preg_match('/^(0|[1-9][0-9]*)$/D',$sort) ||
            strlen($sort)>10 ||
            (strlen($sort)===10 && strcmp($sort,'4294967295')>0)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_SORT_INVALID'));return false;
        }
        $this->sort_order=(int)$sort;
        $db=$this->getDbo();
        $query=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('code').' = '.$db->quote($this->code))
            ->where($db->quoteName('id').' <> '.(int)$this->id);
        $db->setQuery($query);
        if ((int)$db->loadResult()>0) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_DUPLICATE'));return false;
        }
        if ((int)$this->id>0) {
            $q=$db->getQuery(true)->select(['input_type','value_source'])
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id').' = '.(int)$this->id);
            $db->setQuery($q);
            $old=(array)$db->loadAssoc();
            $change=($old['input_type']??$this->input_type)!==$this->input_type
                || ($old['value_source']??$this->value_source)!==$this->value_source;
            if ($change) {
                foreach (['#__minimarket_product_attribute_scalars',
                    '#__minimarket_product_attribute_options',
                    '#__minimarket_product_attribute_free_values'] as $table) {
                    $q=$db->getQuery(true)->select('COUNT(*)')
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('attribute_id').' = '.(int)$this->id);
                    $db->setQuery($q);
                    if ((int)$db->loadResult()>0) {
                        $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_HAS_VALUES'));
                        return false;
                    }
                }
            }
            if ($this->is_multiple===0) {
                foreach(['#__minimarket_product_attribute_options',
                    '#__minimarket_product_attribute_free_values'] as $table) {
                    $q=$db->getQuery(true)->select('COUNT(*)')
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('attribute_id').' = '.(int)$this->id)
                        ->group($db->quoteName('product_id'))->having('COUNT(*) > 1');
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
