<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

/** Read-only group composition, always resolved from saved group memberships. */
final class GroupcontentsField extends FormField
{
    protected $type = 'Groupcontents';

    protected function getInput(): string
    {
        $productId = (int) ($this->form->getValue('id') ?? 0);
        if ($productId < 1) return '<p class="text-muted">' .
            htmlspecialchars(Text::_('COM_MINIMARKET_GROUP_CONTENTS_SAVE_FIRST'), ENT_QUOTES, 'UTF-8') . '</p>';
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $groups = ProductGroups::forProduct($db, $productId);
        if (!$groups) return '<p class="text-muted">' .
            htmlspecialchars(Text::_('COM_MINIMARKET_GROUP_CONTENTS_EMPTY'), ENT_QUOTES, 'UTF-8') . '</p>';
        $escape = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<table class="table table-striped align-middle"><thead><tr><th>' .
            $escape(Text::_('COM_MINIMARKET_PRODUCT_GROUP')) . '</th><th>' .
            $escape(Text::_('COM_MINIMARKET_GROUP_MEMBERS')) . '</th></tr></thead><tbody>';
        foreach ($groups as $group) {
            $url = AdminEntityLinks::edit('group', (int) $group['id']);
            $html .= '<tr><th scope="row"><a href="' . $url . '">' .
                $escape((string) $group['title']) . '</a></th><td>';
            $links = [];
            foreach ($group['members'] as $member) {
                $memberUrl = AdminEntityLinks::edit('product', (int) $member['id']);
                $links[] = '<a href="' . $memberUrl . '">' .
                    $escape((string) $member['title']) . '</a>';
            }
            $html .= implode(', ', $links) . '</td></tr>';
        }
        return $html . '</tbody></table>';
    }
}
