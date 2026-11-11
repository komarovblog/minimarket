<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
?>
<div class="com-minimarket-import">
    <section class="card card-body mb-4 mm-import-block">
        <h2 class="h4 mb-2"><?php echo Text::_('COM_MINIMARKET_IMPORT_TITLE_ONLY'); ?></h2>
        <p class="small text-muted mb-3"><?php echo Text::_('COM_MINIMARKET_IMPORT_COMPACT_HELP'); ?></p>
        <h3 class="h6 mb-2"><?php echo Text::_('COM_MINIMARKET_IMPORT_EXAMPLES_TITLE'); ?></h3>
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mm-import-templates">
                <thead><tr><th><?php echo Text::_('COM_MINIMARKET_IMPORT_SAMPLE_KIND'); ?></th>
                    <th>XLSX</th><th>CSV</th><th>JSON</th></tr></thead>
                <tbody>
                    <?php foreach (['rich' => 'COM_MINIMARKET_IMPORT_DOWNLOAD_RICH',
                                    'catalog' => 'COM_MINIMARKET_IMPORT_DOWNLOAD_SAMPLE',
                                    'empty' => 'COM_MINIMARKET_IMPORT_DOWNLOAD_EMPTY'] as $template => $label) : ?>
                    <tr><th scope="row"><?php echo Text::_($label); ?></th>
                        <?php foreach (['xlsx','csv','json'] as $format) : ?>
                        <td><a class="btn btn-secondary btn-sm mm-import-format"
                               href="<?php echo Route::_('index.php?option=com_minimarket&task=import.example&template='
                                   . $template . '&filetype=' . $format . '&' . Session::getFormToken() . '=1'); ?>">
                            <?php echo strtoupper($format); ?></a></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.preview'); ?>"
              method="post" enctype="multipart/form-data" class="mm-import-upload">
            <label for="mm-import-file" class="form-label"><?php echo Text::_('COM_MINIMARKET_IMPORT_FILE'); ?></label>
            <input type="file" class="form-control mb-2" name="package" id="mm-import-file"
                   accept=".xlsx,.csv,.json,application/json,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            <button type="submit" class="btn btn-primary">
                <?php echo Text::_('COM_MINIMARKET_IMPORT_PREVIEW'); ?>
            </button>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
        <p class="small text-muted mb-0 mt-3"><?php echo Text::_('COM_MINIMARKET_IMPORT_SCOPE_NOTE'); ?></p>
    </section>
    <section class="card card-body mb-4 mt-4 mm-export-block">
        <h2 class="h4 mb-2"><?php echo Text::_('COM_MINIMARKET_EXPORT_TITLE'); ?></h2>
        <p class="text-muted mb-2"><?php echo Text::_('COM_MINIMARKET_EXPORT_HELP'); ?></p>
        <p class="small text-warning"><?php echo Text::_('COM_MINIMARKET_EXPORT_LIMIT_NOTE'); ?></p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach (['xlsx','csv','json'] as $format) : ?>
                <a class="btn btn-primary" href="<?php echo Route::_(
                    'index.php?option=com_minimarket&task=import.export&filetype=' . $format
                    . '&' . Session::getFormToken() . '=1'); ?>">
                    <?php echo Text::_('COM_MINIMARKET_EXPORT_DOWNLOAD') . ' ' . strtoupper($format); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($this->canFull) : ?>
        <section class="card card-body mb-4 mm-export-block">
            <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_EXCHANGE_FULL_TITLE'); ?></h2>
            <p><?php echo Text::_('COM_MINIMARKET_EXCHANGE_FULL_HELP'); ?></p>
            <p class="small text-warning"><?php echo Text::_('COM_MINIMARKET_EXCHANGE_FULL_WARNING'); ?></p>
            <p>
                <a class="btn btn-primary" href="<?php echo Route::_(
                    'index.php?option=com_minimarket&task=import.exportFull&'
                    . Session::getFormToken() . '=1'); ?>">
                    <?php echo Text::_('COM_MINIMARKET_EXCHANGE_FULL_EXPORT'); ?>
                </a>
            </p>
            <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.previewFull'); ?>"
                  method="post" enctype="multipart/form-data">
                <label class="form-label" for="mm-full-json"><?php echo Text::_('COM_MINIMARKET_EXCHANGE_FULL_UPLOAD'); ?></label>
                <input id="mm-full-json" name="full_package" type="file" class="form-control mb-2"
                       accept=".json,application/json" required>
                <button type="submit" class="btn btn-primary">
                    <?php echo Text::_('COM_MINIMARKET_IMPORT_PREVIEW'); ?>
                </button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
            <?php
              $fullReport = is_array($this->fullPending['report'] ?? null)
                  ? $this->fullPending['report'] : null;
              $fullLast = $fullReport===null && is_array($this->fullLast) ? $this->fullLast : null;
              $fullRows = $fullReport ?? $fullLast;
            ?>
            <?php if ($fullRows !== null) : ?>
                <div class="table-responsive mm-editor-table-viewport">
        <table class="table table-sm table-striped mt-3 align-middle">
                    <thead><tr>
                        <th><?php echo Text::_('COM_MINIMARKET_IMPORT_SECTION'); ?></th>
                        <th><?php echo Text::_('COM_MINIMARKET_IMPORT_CREATED'); ?></th>
                        <th><?php echo Text::_('COM_MINIMARKET_EXCHANGE_UNCHANGED'); ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($fullRows as $section => $amount) : ?>
                            <tr>
                                <th scope="row"><?php echo $this->escape($section); ?></th>
                                <td><?php echo (int)($amount['created'] ?? 0); ?></td>
                                <td><?php echo (int)($amount['unchanged'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        </div>
            <?php endif; ?>
            <?php if ($fullReport !== null) : ?>
                <div class="d-flex gap-2 mt-3">
                    <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.applyFull'); ?>" method="post">
                        <button type="submit" class="btn btn-success">
                            <?php echo Text::_('COM_MINIMARKET_IMPORT_APPLY'); ?>
                        </button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>
                    <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.cancelFull'); ?>" method="post">
                        <button type="submit" class="btn btn-outline-secondary">
                            <?php echo Text::_('JCANCEL'); ?>
                        </button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php
    $report = is_array($this->pending['report'] ?? null) ? $this->pending['report'] : null;
    $last = $report === null && is_array($this->last) ? $this->last : null;
    $active = $report ?? $last;
    ?>
    <?php if ($active !== null) : ?>
        <h2 class="h4"><?php echo Text::_($report !== null
            ? 'COM_MINIMARKET_IMPORT_PREVIEW_RESULT' : 'COM_MINIMARKET_IMPORT_LAST_RESULT'); ?></h2>
        <div class="table-responsive mm-editor-table-viewport">
        <table class="table table-striped align-middle">
            <thead><tr>
                <th><?php echo Text::_('COM_MINIMARKET_IMPORT_SECTION'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_IMPORT_CREATED'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_IMPORT_UPDATED'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_IMPORT_REFERENCES'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($active as $section => $counts) : ?>
                <tr>
                    <th scope="row"><?php echo Text::_('COM_MINIMARKET_IMPORT_SECTION_' . strtoupper($section)); ?></th>
                    <td><?php echo (int) ($counts['created'] ?? 0); ?></td>
                    <td><?php echo (int) ($counts['updated'] ?? 0); ?></td>
                    <td><?php echo (int) ($counts['linked'] ?? 0); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    <?php if ($report !== null) : ?>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_IMPORT_PREVIEW_NOTE'); ?></p>
        <div class="d-flex gap-2">
            <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.apply'); ?>" method="post">
                <button type="submit" class="btn btn-success">
                    <?php echo Text::_('COM_MINIMARKET_IMPORT_APPLY'); ?>
                </button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
            <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.cancel'); ?>" method="post">
                <button type="submit" class="btn btn-outline-secondary">
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    <?php endif; ?>
</div>
