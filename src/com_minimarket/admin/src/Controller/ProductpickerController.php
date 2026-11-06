<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice;

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

    /** ACL/CSRF-protected paginated single-brand selection. */
    public function brands(): void
    {
        $this->ensureAllowed();
        $term=mb_substr(trim((string)Factory::getApplication()->input->getString('q','')),0,100);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title','state'])
            ->from($db->quoteName('#__minimarket_brands'))
            ->order('title ASC,id ASC');
        if($term!=='') {
            $like=$db->quote('%'.$db->escape($term,true).'%',false);
            $q->where('(title LIKE '.$like.' OR alias LIKE '.$like.')');
        }
        $db->setQuery($q,0,25);
        $items=[];
        foreach((array)$db->loadAssocList() as $row) {
            $id=(int)$row['id'];
            $items[]=['id'=>$id,'key'=>(string)$id,'title'=>(string)$row['title'],
                'state'=>(int)$row['state']];
        }
        $this->respond(['items'=>$items]);
    }

    /** Small, paged collection search. Never load every collection in product HTML. */
    public function collections(): void
    {
        $this->ensureAllowed();
        $input=Factory::getApplication()->input;
        $text=mb_substr(trim((string)$input->getString('q','')),0,100);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title','state'])
            ->from($db->quoteName('#__minimarket_collections'))
            ->order('title ASC,id ASC');
        if ($text!=='') {
            $like=$db->quote('%'.$db->escape($text,true).'%',false);
            $q->where('(title LIKE '.$like.' OR alias LIKE '.$like.')');
        }
        $db->setQuery($q,0,25);
        $items=[];
        foreach ((array)$db->loadAssocList() as $item) {
            $id=(int)$item['id'];
            $items[]=['id'=>$id,'key'=>(string)$id,'title'=>(string)$item['title'],
                'state'=>(int)$item['state']];
        }
        $this->respond(['items'=>$items]);
    }

    /** Search by product name, product SKU or specific variant SKU. */
    public function bundleCandidates(): void
    {
        $this->ensureAllowed();
        $input=Factory::getApplication()->input;
        $text=mb_substr(trim((string)$input->getString('q','')),0,100);
        $category=(int)$input->getInt('category_id',0);
        $self=(int)$input->getInt('current_id',0);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select([
            'p.id','p.title','p.sku','p.product_type','p.category_id','p.state',
            'v.id AS variant_id','v.sku AS variant_sku','v.state AS variant_state', 'p.price'
        ])->from($db->quoteName('#__minimarket_products','p'))
        ->join('LEFT',$db->quoteName('#__minimarket_product_variants','v').' ON v.product_id=p.id')
        ->order('p.title ASC,p.id ASC,v.id ASC');
        if ($self>0)$q->where('p.id <> '.$self);
        if ($category>0)$q->where('p.category_id='.$category);
        if ($text!=='') {
            $like=$db->quote('%'.$db->escape($text,true).'%',false);
            $q->where('(p.title LIKE '.$like.' OR p.sku LIKE '.$like
                .' OR v.sku LIKE '.$like.')');
        }
        $db->setQuery($q,0,25);
        $items=[];
        $priceSnapshot=BundlePrice::snapshot($db);
        foreach ((array)$db->loadAssocList() as $row) {
            $id=(int)$row['id'];
            $variant=$row['variant_id']!==null;
            $key=$variant?'variant:'.(int)$row['variant_id']:'product:'.$id;
            $items[]=[
                'id'=>$id,'product_id'=>$id,'key'=>$key,
                'title'=>(string)$row['title'],
                'sku'=>(string)($variant?$row['variant_sku']:$row['sku']),
                'state'=>(int)$row['state'],
                'variant_state'=>$variant?(int)$row['variant_state']:null,
                'product_type'=>(string)$row['product_type'],
                'price'=>$row['product_type']==='bundle'
                    ? BundlePrice::calculate($id,$priceSnapshot)['price']
                    : (string)$row['price']
            ];
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
