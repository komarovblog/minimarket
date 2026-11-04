<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

/**
 * One expandable relationship editor in BOTH product and group edit forms.
 * No 1000-product CheckboxField and no unrelated product options sent to HTML.
 * Product searching is a 25-row-per-request protected admin endpoint.
 */
final class GroupeditorField extends FormField
{
    protected $type = 'Groupeditor';

    protected function getInput(): string
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $mode = (string) ($this->element['mode'] ?? 'product');
        if (!in_array($mode, ['product','group'], true)) {
            throw new \InvalidArgumentException('Invalid group editor mode');
        }
        $id = (int) ($this->form->getValue('id') ?: $app->input->getInt('id'));
        $groups = [];
        if ($mode === 'product' && $id > 0) {
            foreach (ProductGroups::forProduct($db, $id) as $g) {
                $members = [];
                foreach ($g['members'] as $member) {
                    $mid = (int)$member['id'];
                    $members[] = ['id'=>$mid === $id ? 'current' : $mid,
                        'title'=>(string)$member['title'], 'enabled'=>true,
                        'state'=>(int)$member['state']];
                }
                $groups[] = [
                    'id'=>(int)$g['id'],'title'=>(string)$g['title'],
                    'state'=>(int)$g['state'], 'members'=>$members,
                    'delete'=>false,'leave'=>false,
                ];
            }
        } elseif ($mode === 'group') {
            $members = [];
            foreach (ProductGroups::members($db, $id) as $member) {
                $members[] = ['id'=>(int)$member['id'],
                    'title'=>(string)$member['title'],'enabled'=>true,
                    'state'=>(int)$member['state']];
            }
            $groups[] = ['id'=>$id,
                'title'=>(string) ($this->form->getValue('title') ?? ''),
                'state'=>(int)($this->form->getValue('state') ?? 1),
                'members'=>$members,'delete'=>false,'leave'=>false];
        }
        $q = $db->getQuery(true)->select(['id','title','parent_id'])
            ->from($db->quoteName('#__minimarket_categories'))
            ->order('title ASC,id ASC');
        $db->setQuery($q);
        $categories = [];
        foreach ((array)$db->loadAssocList() as $cat) {
            $categories[] = ['id'=>(int)$cat['id'],
                'title'=>(string)$cat['title'],'parent_id'=>(int)$cat['parent_id']];
        }
        $token = Session::getFormToken();
        $endpoint = 'index.php?option=com_minimarket&task=productpicker.';
        $config = [
            'mode'=>$mode,'currentId'=>$mode==='product' ? $id : 0,
            'currentTitle'=>$mode==='product'
                ? (string)($this->form->getValue('title') ?: ('#'.$id)) : '',
            'groups'=>$groups, 'categories'=>$categories,
            'otherGroups'=>$mode==='product' ? ProductGroups::options($db) : [],
            'searchUrl'=>$endpoint.'search&'.$token.'=1',
            'groupUrl'=>$endpoint.'group&'.$token.'=1'
        ];
        $escape = static fn(string $v): string =>
            htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
        $json = json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (strlen($json) > 2000000) {
            throw new \RuntimeException('Group editor data too large');
        }
        $labels = [
            'addGroup'=>Text::_('COM_MINIMARKET_GROUP_UI_ADD'),
            'name'=>Text::_('JGLOBAL_TITLE'),
            'members'=>Text::_('COM_MINIMARKET_GROUP_MEMBERS'),
            'addProduct'=>Text::_('COM_MINIMARKET_GROUP_UI_ADD_PRODUCT'),
            'search'=>Text::_('JSEARCH_FILTER_SUBMIT'),
            'searchPlaceholder'=>Text::_('COM_MINIMARKET_GROUP_UI_SEARCH'),
            'category'=>Text::_('COM_MINIMARKET_PRODUCT_CATEGORY'),
            'all'=>Text::_('JALL'),
            'current'=>Text::_('COM_MINIMARKET_GROUP_UI_CURRENT'),
            'active'=>Text::_('COM_MINIMARKET_GROUP_UI_MEMBER_ACTIVE'),
            'removeMember'=>Text::_('COM_MINIMARKET_GROUP_UI_REMOVE_MEMBER'),
            'leave'=>Text::_('COM_MINIMARKET_GROUP_UI_LEAVE'),
            'deleteGroup'=>Text::_('COM_MINIMARKET_GROUP_UI_DELETE'),
            'deleteConfirm'=>Text::_('COM_MINIMARKET_GROUP_UI_DELETE_CONFIRM'),
            'newGroup'=>Text::_('COM_MINIMARKET_GROUP_UI_NEW'),
            'existing'=>Text::_('COM_MINIMARKET_GROUP_UI_EXISTING'),
            'none'=>Text::_('COM_MINIMARKET_GROUP_UI_NONE'),
            'noMatches'=>Text::_('COM_MINIMARKET_GROUP_UI_NO_MATCHES'),
            'saveFirst'=>Text::_('COM_MINIMARKET_GROUP_UI_SAVE_FIRST'),
            'error'=>Text::_('COM_MINIMARKET_GROUP_UI_SEARCH_ERROR'),
            'unpublished'=>Text::_('JUNPUBLISHED'),
            'groupSearch'=>Text::_('COM_MINIMARKET_GROUP_UI_SEARCH_GROUP')
        ];
        $labelJson = json_encode($labels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        // Preserve a valid user-submitted draft after server-side validation.
        $posted = is_string($this->value) && strlen($this->value) < 250000
            && json_validate($this->value) ? $this->value : json_encode($groups,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        return '<div class="mm-group-editor" data-mm-group-editor data-mm-config="'
            .$escape($json).'" data-mm-labels="'.$escape($labelJson).'">'
            .'<div data-mm-group-content></div>'
            .'<input type="hidden" data-mm-group-payload name="'.$escape($this->name)
            .'" id="'.$escape($this->id).'" value="'.$escape($posted).'">'
            .'<p class="small text-muted" data-mm-group-status role="status"></p>'
            .'</div>';
    }
}
