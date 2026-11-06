<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/** Single-brand search with direct editor link, shared MiniMarket picker UI. */
final class BrandpickerField extends FormField
{
    protected $type='Brandpicker';

    protected function getInput(): string
    {
        $id=(int)($this->value??0);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $selected=null;
        if($id>0) {
            $q=$db->getQuery(true)->select(['id','title','state'])
                ->from($db->quoteName('#__minimarket_brands'))->where('id='.$id);
            $db->setQuery($q);
            $selected=$db->loadAssoc();
        }
        $e=static fn($s):string=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $labels=[
            'loading'=>Text::_('COM_MINIMARKET_PICKER_LOADING'),
            'empty'=>Text::_('COM_MINIMARKET_PICKER_EMPTY'),
            'all'=>Text::_('COM_MINIMARKET_PICKER_ALL_ADDED'),
            'limit'=>Text::_('COM_MINIMARKET_PICKER_LIMIT'),
            'error'=>Text::_('COM_MINIMARKET_PICKER_ERROR'),
            'remove'=>Text::_('COM_MINIMARKET_PICKER_REMOVE'),
            'quantity'=>Text::_('COM_MINIMARKET_BUNDLE_QUANTITY'),
            'add'=>Text::_('COM_MINIMARKET_PICKER_ADD_ITEM')
        ];
        $json=$e(json_encode($labels,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE
            |JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT));
        $url='index.php?option=com_minimarket&task=productpicker.brands&'
            .Session::getFormToken().'=1';
        $name=$e($this->name);
        $html='<div class="mm-entity-picker" data-mm-picker="brand" data-mm-input="'.$name
            .'" data-mm-labels="'.$json.'" data-mm-url="'.$e($url).'">'
            // Always submit brand_id=0 if user explicitly removes selected brand.
            .'<input type="hidden" name="'.$name.'" value="0"><div data-mm-selected>';
        if($selected){
            $html.='<div class="mm-entity-picker__row" data-mm-key="'.$id.'">'
                .'<a href="index.php?option=com_minimarket&amp;task=brand.edit&amp;id='.$id.'">'
                .$e($selected['title']).'</a>'
                .'<input type="hidden" name="'.$name.'" value="'.$id.'">'
                .'<button type="button" class="btn btn-danger btn-sm" data-mm-remove'
                .' aria-label="'.$e(Text::_('COM_MINIMARKET_PICKER_REMOVE')).'">−</button></div>';
        }
        return $html.'</div>'
            .'<button type="button" class="btn btn-success btn-sm" data-mm-add>'
            .$e(Text::_('COM_MINIMARKET_BRAND_ADD')).'</button>'
            .'<div data-mm-finder hidden><div class="mm-entity-picker__search">'
            .'<input type="search" class="form-control form-control-sm" data-mm-query'
            .' placeholder="'.$e(Text::_('COM_MINIMARKET_BRAND_SEARCH')).'">'
            .'<button class="btn btn-primary btn-sm" type="button" data-mm-search>'
            .$e(Text::_('JSEARCH_FILTER_SUBMIT')).'</button></div>'
            .'<div data-mm-results></div></div><div data-mm-message role="status"></div></div>';
    }
}
