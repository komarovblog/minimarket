<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/** Ajax collection picker: selected items only, never a wall of checkboxes. */
final class CollectionpickerField extends FormField
{
    protected $type='Collectionpicker';

    protected function getInput():string
    {
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $raw=is_array($this->value)?$this->value:[];
        $ids=[];
        foreach($raw as $id) {
            if(is_scalar($id) && ctype_digit((string)$id) && (int)$id>0) $ids[(int)$id]=true;
        }
        $found=[];
        if($ids) {
            $q=$db->getQuery(true)->select(['id','title','state'])
                ->from($db->quoteName('#__minimarket_collections'))
                ->where('id IN ('.implode(',',array_keys($ids)).')');
            $db->setQuery($q);
            foreach((array)$db->loadAssocList() as $row) $found[(int)$row['id']]=$row;
        }
        $name=htmlspecialchars($this->name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $esc=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $url='index.php?option=com_minimarket&task=productpicker.collections&'
            .Session::getFormToken().'=1';
$labels=[
            'loading'=>Text::_('COM_MINIMARKET_PICKER_LOADING'),
            'empty'=>Text::_('COM_MINIMARKET_PICKER_EMPTY'),
            'all'=>Text::_('COM_MINIMARKET_PICKER_ALL_ADDED'),
            'limit'=>Text::_('COM_MINIMARKET_PICKER_LIMIT'),
            'error'=>Text::_('COM_MINIMARKET_PICKER_ERROR'),
            'remove'=>Text::_('COM_MINIMARKET_PICKER_REMOVE'),
            'quantity'=>Text::_('COM_MINIMARKET_BUNDLE_QUANTITY'),
        ];
        $labelsJson=htmlspecialchars(json_encode($labels,JSON_THROW_ON_ERROR|
            JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),
            ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $out='<div class="mm-entity-picker" data-mm-picker="collections"'
            .' data-mm-url="'.$esc($url).'" data-mm-input="'.$name.'" data-mm-labels="'.$labelsJson.'">'
            .'<div data-mm-selected>';
        foreach(array_keys($ids) as $id){
            if(!isset($found[$id]))continue;
            $row=$found[$id];
            $out.='<div class="mm-entity-picker__row" data-mm-key="'.$id.'">'
                .'<a href="index.php?option=com_minimarket&amp;task=collection.edit&amp;id='.$id.'">'
                .$esc($row['title']).'</a>'
                .((int)$row['state']===0?' <small>('.$esc(Text::_('JUNPUBLISHED')).')</small>':'')
                .'<input type="hidden" name="'.$name.'[]" value="'.$id.'">'
                .'<button class="btn btn-danger btn-sm" type="button" data-mm-remove aria-label="'
                .$esc(Text::_('JREMOVE')).'">×</button></div>';
        }
        $out.='</div><button class="btn btn-success btn-sm" type="button" data-mm-add>+ '
            .$esc(Text::_('COM_MINIMARKET_COLLECTION_ADD')).'</button>'
            .'<div data-mm-finder hidden><div class="mm-entity-picker__search">'
            .'<input type="search" class="form-control form-control-sm" data-mm-query'
            .' placeholder="'.$esc(Text::_('COM_MINIMARKET_COLLECTION_SEARCH')).'">'
            .'<button type="button" class="btn btn-primary btn-sm" data-mm-search>'
            .$esc(Text::_('JSEARCH_FILTER_SUBMIT')).'</button></div>'
            .'<div data-mm-results></div></div><div data-mm-message role="status"></div></div>';
        return $out;
    }
}
