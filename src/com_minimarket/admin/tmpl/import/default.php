<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
?>
<div class="com-minimarket-import">
    <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_IMPORT_HELP'); ?></p>
    <div class="card card-body mb-3">
        <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_IMPORT_STEPS_TITLE'); ?></h2>
        <ol class="mb-3">
            <li><?php echo Text::_('COM_MINIMARKET_IMPORT_STEP_DOWNLOAD'); ?></li>
            <li><?php echo Text::_('COM_MINIMARKET_IMPORT_STEP_EDIT'); ?></li>
            <li><?php echo Text::_('COM_MINIMARKET_IMPORT_STEP_PREVIEW'); ?></li>
            <li><?php echo Text::_('COM_MINIMARKET_IMPORT_STEP_APPLY'); ?></li>
        </ol>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-secondary"
               href="<?php echo Route::_('index.php?option=com_minimarket&task=import.example&template=catalog&' . Session::getFormToken() . '=1'); ?>">
                <?php echo Text::_('COM_MINIMARKET_IMPORT_DOWNLOAD_SAMPLE'); ?>
            </a>
            <a class="btn btn-secondary"
               href="<?php echo Route::_('index.php?option=com_minimarket&task=import.example&template=empty&' . Session::getFormToken() . '=1'); ?>">
                <?php echo Text::_('COM_MINIMARKET_IMPORT_DOWNLOAD_EMPTY'); ?>
            </a>
        </div>
        <p class="small text-muted mt-3 mb-0"><?php echo Text::_('COM_MINIMARKET_IMPORT_SCOPE_NOTE'); ?></p>
    </div>
    <form action="<?php echo Route::_('index.php?option=com_minimarket&task=import.preview'); ?>"
          method="post" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="mm-import-file" class="form-label"><?php echo Text::_('COM_MINIMARKET_IMPORT_FILE'); ?></label>
            <input type="file" class="form-control" name="package" id="mm-import-file"
                   accept=".json,application/json" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <?php echo Text::_('COM_MINIMARKET_IMPORT_PREVIEW'); ?>
        </button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <?php
    $report = is_array($this->pending['report'] ?? null) ? $this->pending['report'] : null;
    $last = $report === null && is_array($this->last) ? $this->last : null;
    $active = $report ?? $last;
    ?>
    <?php if ($active !== null) : ?>
        <h2 class="h4"><?php echo Text::_($report !== null
            ? 'COM_MINIMARKET_IMPORT_PREVIEW_RESULT' : 'COM_MINIMARKET_IMPORT_LAST_RESULT'); ?></h2>
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
