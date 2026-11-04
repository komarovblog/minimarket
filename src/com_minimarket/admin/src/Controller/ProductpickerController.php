<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Admin-only, paged search for adding members to product relationships.
 * This is never a public product-discovery endpoint.
 */
final class ProductpickerController extends BaseController
{
    private function ensureAllowed(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_minimarket')
            || !Session::checkToken('get')) {
            throw new \RuntimeException('Access denied', 403);
        }
    }

    private function respond(array $data): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->setHeader('Cache-Control', 'no-store', true);
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $app->close();
    }

    public function search(): void
    {
        $this->ensureAllowed();
        $app = Factory::getApplication();
        $input = $app->input;
        $queryText = trim((string) $input->getString('q', ''));
        $categoryId = $input->getInt('category_id', 0);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select(['p.id','p.title','p.category_id','p.state',
                'c.title AS category_title'])
            ->from($db->quoteName('#__minimarket_products', 'p'))
            ->join('INNER', $db->quoteName('#__minimarket_categories', 'c')
                . ' ON c.id = p.category_id')
            ->order('p.title ASC, p.id ASC');
        if ($categoryId > 0) $q->where('p.category_id = ' . $categoryId);
        if ($queryText !== '') {
            $queryText = mb_substr($queryText, 0, 100);
            $q->where('(' . 'p.title LIKE ' . $db->quote('%'.$db->escape($queryText,true).'%')
                . ' OR p.sku LIKE ' . $db->quote('%'.$db->escape($queryText,true).'%') . ')');
        }
        $db->setQuery($q, 0, 25);
        $items = [];
        foreach ((array)$db->loadAssocList() as $row) {
            $items[] = ['id'=>(int)$row['id'],'title'=>(string)$row['title'],
                'category_id'=>(int)$row['category_id'],
                'category'=>(string)$row['category_title'],
                'state'=>(int)$row['state']];
        }
        $this->respond(['items'=>$items]);
    }

    public function group(): void
    {
        $this->ensureAllowed();
        $id = Factory::getApplication()->input->getInt('id', 0);
        if ($id < 1) throw new \RuntimeException('Invalid group', 400);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)->select(['id','title','state'])
            ->from($db->quoteName('#__minimarket_product_groups'))
            ->where('id = '.$id);
        $db->setQuery($q);
        $group = $db->loadAssoc();
        if (!$group) throw new \RuntimeException('Group not found', 404);
        $q = $db->getQuery(true)->select(['p.id','p.title','p.state'])
            ->from($db->quoteName('#__minimarket_product_group_members', 'gm'))
            ->join('INNER', $db->quoteName('#__minimarket_products', 'p')
                . ' ON p.id = gm.product_id')
            ->where('gm.group_id = '.$id)->order('gm.sort_order ASC,p.id ASC');
        // Never return a truncated group: saving a partial member list would
        // wrongly unlink the omitted members.
        $db->setQuery($q,0,1001);
        $found = (array) $db->loadAssocList();
        if (count($found) > 1000) {
            throw new \RuntimeException('Group too large for inline editing', 409);
        }
        $members = [];
        foreach ($found as $p) {
            $members[] = ['id'=>(int)$p['id'],'title'=>(string)$p['title'],
                'state'=>(int)$p['state']];
        }
        $this->respond(['group'=>['id'=>(int)$group['id'],
            'title'=>(string)$group['title'],'state'=>(int)$group['state'],
            'members'=>$members]]);
    }
}
