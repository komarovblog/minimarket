<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice;

/** Searchable bundle component/SKU rows with quantity controls. */
final class BundlepickerField extends FormField
{
    protected $type='Bundlepicker';

    protected function getInput():string
    {
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $rows=is_array($this->value)?array_values($this->value):[];
        $productIds=[];$variantIds=[];
        foreach($rows as $row){
            if(!is_array($row))continue;
            $choice=(string)($row['component']??'');
            if(preg_match('/^product:([1-9]\d*)$/D',$choice,$m))$productIds[(int)$m[1]]=true;
            if(preg_match('/^variant:([1-9]\d*)$/D',$choice,$m))$variantIds[(int)$m[1]]=true;
        }
        $meta=[];
        $prices=BundlePrice::snapshot($db);
        if($variantIds) {
            $q=$db->getQuery(true)->select(['v.id','v.product_id','v.sku','p.title','p.state','p.price'])
                ->from($db->quoteName('#__minimarket_product_variants','v'))
                ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=v.product_id')
                ->where('v.id IN ('.implode(',',array_keys($variantIds)).')');
            $db->setQuery($q);
            foreach((array)$db->loadAssocList() as $r) $meta['variant:'.$r['id']]=[
                'title'=>$r['title'],'sku'=>$r['sku'],'product'=>(int)$r['product_id'],
                'price'=>(string)$r['price'],'state'=>(int)$r['state']];
        }
        if($productIds) {
            $q=$db->getQuery(true)->select(['id','title','sku','state','price','product_type'])
                ->from($db->quoteName('#__minimarket_products'))
                ->where('id IN ('.implode(',',array_keys($productIds)).')');
            $db->setQuery($q);
            foreach((array)$db->loadAssocList() as $r) $meta['product:'.$r['id']]=[
                'title'=>$r['title'],'sku'=>$r['sku'],'product'=>(int)$r['id'],
                'price'=>$r['product_type']==='bundle'
                    ? BundlePrice::calculate((int)$r['id'],$prices)['price']
                    : (string)$r['price'],'state'=>(int)$r['state']];
        }
        $q=$db->getQuery(true)->select(['id','title'])
            ->from($db->quoteName('#__minimarket_categories'))->order('title ASC,id ASC');
        $db->setQuery($q);
        $categories=(array)$db->loadAssocList();
        $esc=static fn($s):string=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $url='index.php?option=com_minimarket&task=productpicker.bundleCandidates&'
            .Session::getFormToken().'=1';
        $name=$esc($this->name); $id=(int)($this->form->getValue('id')??0);
$labels=[
            'loading'=>Text::_('COM_MINIMARKET_PICKER_LOADING'),
            'empty'=>Text::_('COM_MINIMARKET_PICKER_EMPTY'),
            'all'=>Text::_('COM_MINIMARKET_PICKER_ALL_ADDED'),
            'limit'=>Text::_('COM_MINIMARKET_PICKER_LIMIT'),
            'error'=>Text::_('COM_MINIMARKET_PICKER_ERROR'),
            'remove'=>Text::_('COM_MINIMARKET_PICKER_REMOVE'),
            'quantity'=>Text::_('COM_MINIMARKET_BUNDLE_QUANTITY'),
            'unit'=>Text::_('COM_MINIMARKET_BUNDLE_UNIT_PRICE'),
        ];
        $labelsJson=htmlspecialchars(json_encode($labels,JSON_THROW_ON_ERROR|
            JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),
            ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $out='<div class="mm-entity-picker" data-mm-picker="bundle"'
            .' data-mm-url="'.$esc($url).'" data-mm-input="'.$name.'" data-mm-labels="'.$labelsJson.'"'
            .' data-mm-current="'.$id.'"><div data-mm-selected>'
            .'<div class="mm-bundle-grid-head" aria-hidden="true">'
            .'<span>'.$esc(Text::_('COM_MINIMARKET_BUNDLE_COMPONENT')).'</span>'
            .'<span>'.$esc(Text::_('COM_MINIMARKET_BUNDLE_UNIT_PRICE')).'</span>'
            .'<span>'.$esc(Text::_('COM_MINIMARKET_BUNDLE_QUANTITY')).'</span>'
            .'<span>'.$esc(Text::_('COM_MINIMARKET_BUNDLE_LINE_TOTAL')).'</span>'
            .'<span></span></div>';
        $seq=0;
        foreach($rows as $row){
            if(!is_array($row))continue;
            $key=(string)($row['component']??'');
            if(!preg_match('/^(product|variant):[1-9]\d*$/D',$key) || !isset($meta[$key]))continue;
            $data=$meta[$key];$index='row'.$seq++;
            $out.='<div class="mm-entity-picker__row" data-mm-key="'.$esc($key).'"'
                .' data-mm-unit-price="'.$esc($data['price']).'">'
                .'<div class="mm-bundle-product"><a href="index.php?option=com_minimarket&amp;task=product.edit&amp;id='.(int)$data['product'].'">'
                .$esc($data['title']).'</a>'
                .'<small>'.$esc($data['sku']).'</small></div>'
                .'<span class="mm-bundle-unit-price">'.$esc(Text::_('COM_MINIMARKET_BUNDLE_UNIT_PRICE'))
                .': '.$esc($data['price']).'</span>'
                .'<input type="hidden" name="'.$name.'['.$index.'][component]" value="'.$esc($key).'">'
                .'<label class="mm-bundle-quantity"><span class="visually-hidden">'.$esc(Text::_('COM_MINIMARKET_BUNDLE_QUANTITY')).'</span>'
                .'<input type="number" class="form-control form-control-sm" min="1" max="1000" step="1" required'
                .' name="'.$name.'['.$index.'][quantity]" value="'.max(1,(int)($row['quantity']??1)).'"></label>'
                .'<strong class="mm-bundle-line-total" data-mm-line-total></strong>'
                .'<button class="btn btn-danger btn-sm" type="button" data-mm-remove aria-label="'
                .$esc(Text::_('JREMOVE')).'">−</button></div>';
        }
        $out.='</div><button class="btn btn-success btn-sm" type="button" data-mm-add>+ '
            .$esc(Text::_('COM_MINIMARKET_BUNDLE_ADD_PRODUCT')).'</button>'
            .'<div data-mm-finder hidden><div class="mm-entity-picker__search">'
            .'<input type="search" class="form-control form-control-sm" data-mm-query'
            .' placeholder="'.$esc(Text::_('COM_MINIMARKET_BUNDLE_SEARCH')).'">'
            .'<select class="form-select form-select-sm" data-mm-category>'
            .'<option value="0">'.$esc(Text::_('JALL')).'</option>';
        foreach($categories as $cat)$out.='<option value="'.(int)$cat['id'].'">'.$esc($cat['title']).'</option>';
        $out.='</select><button type="button" class="btn btn-primary btn-sm" data-mm-search>'
            .$esc(Text::_('JSEARCH_FILTER_SUBMIT')).'</button></div>'
            .'<div data-mm-results></div></div><div data-mm-message role="status"></div></div>';
        return $out;
    }
}
