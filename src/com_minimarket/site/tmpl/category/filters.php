<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;

$escape=static fn(mixed $text):string=>htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$facets=$this->facetData['facets'];
$selected=$this->facetSelection;
$json=json_encode(['facets'=>$facets,'facts'=>$this->facetData['facts'],
    'skus'=>$this->facetData['skus']],
    JSON_THROW_ON_ERROR|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>
<form id="mm-facet-form" class="mm-facet-form" data-mm-facet-form method="get"
      action="<?php echo $escape(RouteHelper::category((int)$this->category->id)); ?>">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_FILTER_TITLE'); ?></h2>
    <p class="small text-muted" role="status" data-mm-facet-count
       data-mm-facet-label="<?php echo $escape(Text::sprintf('COM_MINIMARKET_FILTER_RESULTS','{count}')); ?>">
        <?php echo Text::sprintf('COM_MINIMARKET_FILTER_RESULTS',
            count(array_filter($this->facetMatches))); ?>
    </p>
    <?php foreach ($facets as $id=>$facet):
        $mode=$facet['mode'];$selection=$selected[$id]??[];
        $chosen=$selection['values']??[];$name='mmf['.$id.']';
        $caption=($id==='price'?Text::_('COM_MINIMARKET_FILTER_PRICE'):$facet['title'])
            .($facet['unit']!==''?' ('.$facet['unit'].')':'');
    ?>
        <fieldset class="mm-facet-group" data-mm-facet="<?php echo $escape($id); ?>"
                  data-mm-facet-mode="<?php echo $escape($mode); ?>"
                  data-mm-facet-type="<?php echo $escape($facet['type']); ?>">
            <legend><?php echo $escape($caption); ?>
                <small class="mm-facet-count" data-mm-facet-group-count></small>
            </legend>
            <?php if ($mode==='text'): ?>
                <input type="search" class="form-control form-control-sm"
                       data-mm-facet-text name="<?php echo $escape($name); ?>"
                       aria-label="<?php echo $escape($caption); ?>"
                       placeholder="<?php echo $escape(Text::_('COM_MINIMARKET_FILTER_SEARCH')); ?>"
                       value="<?php echo $escape($selection['text']??''); ?>">
            <?php elseif (in_array($mode,['range','slider'],true)): ?>
                <div class="mm-facet-range-fields">
                    <?php foreach (['min','max'] as $boundary): ?>
                        <label class="form-label small">
                            <?php echo Text::_($boundary==='min'?'COM_MINIMARKET_FILTER_MIN':'COM_MINIMARKET_FILTER_MAX'); ?>
                            <input type="number" class="form-control form-control-sm"
                                   name="<?php echo $escape($name.'['.$boundary.']'); ?>"
                                   data-mm-facet-bound="<?php echo $boundary; ?>"
                                   min="<?php echo $escape($facet['min']); ?>"
                                   max="<?php echo $escape($facet['max']); ?>" step="any"
                                   value="<?php echo $escape($selection[$boundary]??''); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if ($mode==='slider' && $facet['min']<$facet['max']): ?>
                    <?php foreach (['min','max'] as $boundary): ?>
                        <input type="range" class="mm-facet-slider"
                               data-mm-facet-slider="<?php echo $boundary; ?>"
                               min="<?php echo $escape($facet['min']); ?>"
                               max="<?php echo $escape($facet['max']); ?>" step="any"
                               value="<?php echo $escape($selection[$boundary]??$facet[$boundary]); ?>"
                               aria-label="<?php echo $escape($caption.' '.Text::_($boundary==='min'?'COM_MINIMARKET_FILTER_MIN':'COM_MINIMARKET_FILTER_MAX')); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($mode==='dropdown' || $mode==='values'): ?>
                    <details class="mm-facet-dropdown" <?php echo $chosen?'open':''; ?>>
                        <summary><?php echo $escape($chosen ? implode(', ',array_map(
                            static function($v) use ($facet):string {
                                foreach ($facet['choices'] as $item) {
                                    if ($item['value']===$v) return $item['label'];
                                }
                                return (string)$v;
                            }, $chosen)) : Text::_('COM_MINIMARKET_FILTER_ANY')); ?></summary>
                <?php endif; ?>
                <input type="search" class="form-control form-control-sm mm-facet-search"
                       data-mm-choice-search autocomplete="off"
                       placeholder="<?php echo $escape(Text::_('COM_MINIMARKET_FILTER_VALUE_SEARCH')); ?>"
                       aria-label="<?php echo $escape($caption.' '.Text::_('COM_MINIMARKET_FILTER_SEARCH')); ?>">
                <?php foreach ($facet['choices'] as $i=>$choice):
                    $checked=in_array($choice['value'],$chosen,true);
                    $multiple=$facet['multiple'];$kind=$multiple?'checkbox':'radio';
                    $fieldId='mm-facet-'.$id.'-'.$i;
                ?>
                    <div class="mm-facet-choice" data-mm-facet-choice-row>
                        <input class="form-check-input" type="<?php echo $kind; ?>"
                               data-mm-facet-value="<?php echo $escape($choice['value']); ?>"
                               id="<?php echo $escape($fieldId); ?>"
                               name="<?php echo $escape($name.($multiple?'[]':'')); ?>"
                               value="<?php echo $escape($choice['value']); ?>"
                               <?php echo $checked?'checked':''; ?>>
                        <label for="<?php echo $escape($fieldId); ?>">
                            <?php if ($choice['hex']): ?>
                                <span class="mm-facet-swatch" aria-hidden="true"
                                      style="background-color:<?php echo $escape($choice['hex']); ?>"></span>
                            <?php endif; ?>
                            <?php echo $escape($facet['type']==='boolean'
                                ? Text::_($choice['value']==='b:1'?'JYES':'JNO')
                                : $choice['label']); ?>
                        </label>
                        <small class="mm-facet-count" data-mm-choice-count><?php echo (int)$choice['count']; ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if ($mode==='dropdown' || $mode==='values'): ?></details><?php endif; ?>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-sm">
            <?php echo Text::_('COM_MINIMARKET_FILTER_APPLY'); ?>
        </button>
        <a href="<?php echo $escape(RouteHelper::category((int)$this->category->id)); ?>"
           class="btn btn-outline-secondary btn-sm" data-mm-facet-reset>
            <?php echo Text::_('COM_MINIMARKET_FILTER_RESET'); ?>
        </a>
    </div>
</form>
<script type="application/json" data-mm-facet-data><?php echo $json; ?></script>
