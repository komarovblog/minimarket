<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;

$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<div class="com-minimarket-feeds mm-admin-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h4 mb-1"><?php echo Text::_('COM_MINIMARKET_FEEDS_TITLE'); ?></h2>
            <p class="text-muted mb-0"><?php echo Text::_('COM_MINIMARKET_FEEDS_HELP'); ?></p>
        </div>
        <a class="btn btn-success" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=feed',false)); ?>">
            <span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('JNEW'); ?>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_FEED_PROVIDER'); ?></th>
                <th><?php echo Text::_('JSTATUS'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_FEED_URL'); ?></th>
                <th><?php echo Text::_('COM_MINIMARKET_FEED_ACTIONS'); ?></th>
            </tr></thead><tbody>
            <?php foreach($this->feeds as $feed):
                $url=\Joomla\CMS\Uri\Uri::root().'index.php?option=com_minimarket&task=feed.xml&id='
                    .(int)$feed['id'].'&key='.rawurlencode((string)$feed['access_key']);
                $edit=Route::_('index.php?option=com_minimarket&view=feed&id='.(int)$feed['id'],false);
            ?>
                <tr>
                    <td><a href="<?php echo $e($edit); ?>"><?php echo $e($feed['title']); ?></a></td>
                    <td><?php echo Text::_(FeedConfig::PROVIDERS[$feed['provider']]??'COM_MINIMARKET_FEED_INVALID'); ?></td>
                    <td><?php echo (int)$feed['state']===1?Text::_('JPUBLISHED'):Text::_('JUNPUBLISHED'); ?></td>
                    <td>
                      <?php if((int)$feed['state']===1): ?>
                        <input class="form-control form-control-sm" type="text" readonly
                            aria-label="<?php echo $e(Text::_('COM_MINIMARKET_FEED_URL')); ?>"
                            value="<?php echo $e($url); ?>" onclick="this.select()">
                      <?php else: ?>
                        <span class="text-muted"><?php echo Text::_('COM_MINIMARKET_FEED_DRAFT'); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($edit); ?>"><?php echo Text::_('JEDIT'); ?></a>
                            <?php if((int)$feed['state']===1): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($url); ?>"
                                target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_MINIMARKET_FEED_VIEW_XML'); ?></a>
                            <?php endif; ?>
                            <form action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=feed.delete',false)); ?>"
                                  method="post" onsubmit="return confirm(<?php echo $e(json_encode(Text::_('COM_MINIMARKET_FEED_CONFIRM_DELETE'),JSON_HEX_APOS|JSON_HEX_QUOT)); ?>)">
                                <input type="hidden" name="id" value="<?php echo (int)$feed['id']; ?>">
                                <?php echo HTMLHelper::_('form.token'); ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?php echo Text::_('JACTION_DELETE'); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$this->feeds): ?>
                <tr><td colspan="5" class="text-muted"><?php echo Text::_('COM_MINIMARKET_FEED_EMPTY'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small"><?php echo Text::_('COM_MINIMARKET_FEED_LIMITATIONS'); ?></p>
</div>
