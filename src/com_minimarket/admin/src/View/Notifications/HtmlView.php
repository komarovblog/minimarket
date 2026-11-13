<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Notifications;
\defined('_JEXEC') or die;

/**
 * Manager inbox: the exact SAME unresolved sources/receipts as Workqueue.
 * Only unread rows are displayed, no second source of business statuses.
 */
final class HtmlView extends \MiniMarket\Component\Minimarket\Administrator\View\Workqueue\HtmlView
{
}
