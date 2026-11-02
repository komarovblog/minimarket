<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGallery;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVideos;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

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
            $item->videos = ProductVideos::loadFormRows($this->getDatabase(), (int) $item->id);
            $item->attribute_values = ProductAttributes::load($this->getDatabase(), (int) $item->id);
        } elseif ($item) {
            $item->gallery = [];
            $item->videos = [];
            $item->attribute_values = [];
        }

        return $item;
    }

    /**
     * One DB transaction for product + gallery. A failed product validation
     * must not delete photos; a failed gallery insert must roll back product.
     */
    public function save($data)
    {
        // Joomla repeatable subform posts no rows when the last row is removed.
        // Hidden sentinel makes an explicit empty gallery distinct from a legacy
        // form that never offered gallery editing.
        $hasGallery = array_key_exists('gallery', $data) || !empty($data['gallery_present']);
        $hasVideos = array_key_exists('videos', $data) || !empty($data['videos_present']);
        $hasAttributes = array_key_exists('attribute_values', $data)
            || !empty($data['attributes_present']);
        try {
            $rows = $hasGallery
                ? ProductGallery::normalizeRows($data['gallery'] ?? [], (string) ($data['image'] ?? ''))
                : [];
        } catch (\InvalidArgumentException | \JsonException $error) {
            $this->setError(Text::_('COM_MINIMARKET_GALLERY_INVALID'));
            return false;
        }

        try {
            $videoRows = $hasVideos ? ProductVideos::normalize($data['videos'] ?? []) : [];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_VIDEO_INVALID'));
            return false;
        }

        $db = $this->getDatabase();
        $normalized = ['scalars' => [], 'options' => []];
        if ($hasAttributes) {
            try {
                $definitions = ProductAttributes::definitions($db);
                $hierarchy = CategoryAttributes::loadHierarchy($db);
                $categoryMap = ProductAttributes::categoryMap($hierarchy, $definitions);
                $normalized = ProductAttributes::normalize(
                    $data['attribute_values'] ?? [],
                    (int) ($data['category_id'] ?? 0),
                    $definitions,
                    $categoryMap
                );
            } catch (\InvalidArgumentException $error) {
                $message = $error->getMessage();
                $this->setError(Text::_(str_starts_with($message, 'COM_MINIMARKET_')
                    ? $message : 'COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID'));
                return false;
            }
        }

        if ($hasGallery && $rows && trim((string) ($data['image'] ?? '')) === '') {
            $this->setError(Text::_('COM_MINIMARKET_GALLERY_COVER_REQUIRED'));
            return false;
        }

        unset($data['videos'], $data['videos_present'],
            $data['gallery'], $data['gallery_present'],
            $data['attribute_values'], $data['attributes_present']);
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
            if ($hasVideos) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductVideos::replace($db, $productId, $videoRows);
            }
            if ($hasAttributes) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductAttributes::replace($db, $productId, $normalized);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_SAVE_FAILED'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.product.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
