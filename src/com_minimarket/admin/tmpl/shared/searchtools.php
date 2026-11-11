<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

// Required $mmSearch: view, query, filters[], sort, sortOptions[], limit.
// The GET search form is deliberately outside the POST adminForm to avoid
// nested forms and preserve Joomla's bulk-action/CSRF contract.
$esc = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$view = (string) $mmSearch['view'];
$fields = (array) ($mmSearch['filters'] ?? []);
$expanded = false;
foreach ($fields as $field) {
    if ((string) ($field['value'] ?? '') !== (string) ($field['default'] ?? '')) {
        $expanded = true;
        break;
    }
}
$searchId = 'mm-search-' . $view;
?>
<form class="mm-native-searchtools" method="get" action="index.php" role="search">
    <input type="hidden" name="option" value="com_minimarket">
    <input type="hidden" name="view" value="<?php echo $esc($view); ?>">
    <div class="mm-native-searchtools__line">
        <div class="input-group mm-native-searchtools__query">
            <input id="<?php echo $esc($searchId); ?>" class="form-control" type="search"
                   name="filter_search" maxlength="100"
                   placeholder="<?php echo $esc(Text::_('JSEARCH_FILTER')); ?>"
                   aria-label="<?php echo $esc($mmSearch['searchLabel'] ?? Text::_('JSEARCH_FILTER')); ?>"
                   value="<?php echo $esc($mmSearch['query'] ?? ''); ?>">
            <button class="btn btn-primary" type="submit" title="<?php echo $esc(Text::_('JSEARCH_FILTER_SUBMIT')); ?>">
                <span class="icon-search" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></span>
            </button>
        </div>
        <?php if ($fields): ?>
        <details class="mm-native-searchtools__details" <?php echo $expanded ? 'open' : ''; ?>>
            <summary class="btn btn-primary">
                <?php echo Text::_('JSEARCH_FILTER_OPTIONS'); ?>
                <span class="icon-chevron-down" aria-hidden="true"></span>
            </summary>
            <div class="mm-native-searchtools__filters">
                <?php foreach ($fields as $i => $field): ?>
                <label class="mm-native-searchtools__filter" for="<?php echo $esc('mm-filter-' . $view . '-' . $i); ?>">
                    <span class="visually-hidden"><?php echo $esc($field['label']); ?></span>
                    <?php if (($field['type'] ?? 'select') === 'text'): ?>
                    <input class="form-control" type="search" id="<?php echo $esc('mm-filter-' . $view . '-' . $i); ?>"
                           name="<?php echo $esc($field['name']); ?>" maxlength="100"
                           placeholder="<?php echo $esc($field['label']); ?>"
                           value="<?php echo $esc($field['value'] ?? ''); ?>">
                    <?php else: ?>
                    <select class="form-select" id="<?php echo $esc('mm-filter-' . $view . '-' . $i); ?>"
                            name="<?php echo $esc($field['name']); ?>"
                            aria-label="<?php echo $esc($field['label']); ?>">
                        <?php foreach ($field['options'] as $key => $label): ?>
                            <option value="<?php echo $esc($key); ?>" <?php echo (string)($field['value']??'') === (string)$key ? 'selected' : ''; ?>>
                                <?php echo $esc($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
                <button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            </div>
        </details>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="index.php?option=com_minimarket&amp;view=<?php echo $esc($view); ?>">
            <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
        </a>
        <div class="mm-native-searchtools__spacer"></div>
        <label class="visually-hidden" for="<?php echo $esc('mm-sort-' . $view); ?>"><?php echo Text::_('JGLOBAL_SORT_BY'); ?></label>
        <select id="<?php echo $esc('mm-sort-' . $view); ?>" class="form-select mm-native-searchtools__sort" name="filter_sort" onchange="this.form.requestSubmit()">
            <?php foreach (($mmSearch['sortOptions']??[]) as $key=>$label): ?>
                <option value="<?php echo $esc($key); ?>" <?php echo (string)($mmSearch['sort']??'newest') === (string)$key ? 'selected' : ''; ?>>
                    <?php echo $esc($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label class="visually-hidden" for="<?php echo $esc('mm-limit-' . $view); ?>"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
        <select id="<?php echo $esc('mm-limit-' . $view); ?>" class="form-select mm-native-searchtools__limit" name="limit" onchange="this.form.requestSubmit()">
            <?php foreach ([20,50,100] as $size): ?>
                <option value="<?php echo $size; ?>" <?php echo (int)($mmSearch['limit']??20) === $size ? 'selected' : ''; ?>><?php echo $size; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
