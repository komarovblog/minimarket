<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGallery;

final class ProductModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_minimarket.product',
            'product',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item && (int) ($item->id ?? 0) > 0) {
            $item->gallery = ProductGallery::loadFormRows($this->getDatabase(), (int) $item->id);
        } elseif ($item) {
            $item->gallery = [];
        }

        return $item;
    }

    /**
     * One DB transaction for product + gallery. A failed product validation
     * must not delete photos; a failed gallery insert must roll back product.
     */
    public function save($data)
    {
        $hasGallery = array_key_exists('gallery', $data);
        try {
            $rows = $hasGallery
                ? ProductGallery::normalizeRows($data['gallery'], (string) ($data['image'] ?? ''))
                : [];
        } catch (\InvalidArgumentException | \JsonException $error) {
            $this->setError(Text::_('COM_MINIMARKET_GALLERY_INVALID'));
            return false;
        }

        unset($data['gallery']);
        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }

            if ($hasGallery) {
                $productId = (int) $this->getState($this->getName() . '.id');
                if ($productId < 1) {
                    throw new \RuntimeException('COM_MINIMARKET_GALLERY_INVALID');
                }
                ProductGallery::replaceRows($db, $productId, $rows);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_GALLERY_SAVE_FAILED'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.product.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
