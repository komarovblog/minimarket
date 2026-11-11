<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<section class="mm-order-complete">
    <h1><?php echo Text::_('COM_MINIMARKET_ORDER_COMPLETE_TITLE'); ?></h1>
    <?php if ($this->orderId > 0) : ?>
        <p><?php echo Text::sprintf('COM_MINIMARKET_ORDER_COMPLETE_MESSAGE', (int)$this->orderId); ?></p>
        <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_ORDER_COMPLETE_NEXT'); ?></p>
        <?php if($this->bankInstructions!==''): ?>
         <section class="mt-3">
          <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_BANK_TRANSFER_INSTRUCTIONS_TITLE'); ?></h2>
          <p><?php echo nl2br($this->escape($this->bankInstructions),false); ?></p>
         </section>
        <?php endif; ?>
    <?php else : ?>
        <p><?php echo Text::_('COM_MINIMARKET_ORDER_COMPLETE_NO_SESSION'); ?></p>
    <?php endif; ?>
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_CONTINUE'); ?></a>
</section>
