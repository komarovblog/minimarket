<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;
\defined('_JEXEC') or die;
/** Bundle-only relationship groups. Ordinary product links never appear here. */
final class BundlegroupsModel extends GroupsModel
{
    protected string $groupType='bundle';
}
