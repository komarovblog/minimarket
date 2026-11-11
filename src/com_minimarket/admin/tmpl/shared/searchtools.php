<?php
\defined('_JEXEC') or die;

// The same Joomla SearchTools layout used in Materials and Categories.
// All entity lists render the native form fields inside their adminForm.
echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::render(
    $mmSearch,
    (int) ($this->total ?? 0)
);
