<?php
namespace MiniMarket\Component\Minimarket\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

/**
 * Joomla MVC component + its own SEF router.
 * Does not depend on Joomla articles (com_content).
 */
final class MinimarketComponent extends MVCComponent implements RouterServiceInterface
{
    use RouterServiceTrait;
}
