<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGallery;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVideos;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductLocalDimensions;
use MiniMarket\Component\Minimarket\Administrator\Service\VariantMatrix;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductCatalogAxes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductSkuMatrix;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;
use MiniMarket\Component\Minimarket\Administrator\Service\GroupEditor;

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
            $item->variants = ProductVariants::formRows($this->getDatabase(), (int) $item->id);
            $item->variant_dimensions = ProductLocalDimensions::formRows($this->getDatabase(), (int) $item->id);
            $item->group_ids = ProductGroups::currentIds($this->getDatabase(), (int) $item->id);
        } elseif ($item) {
            $item->gallery = [];
            $item->videos = [];
            $item->attribute_values = [];
            $item->variants = [];
            $item->variant_dimensions = [];
            $item->group_ids = [];
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
        $hasMatrix = array_key_exists('sku_matrix_payload', $data);
        $hasGallery = array_key_exists('gallery', $data) || !empty($data['gallery_present']);
        $hasVideos = array_key_exists('videos', $data) || !empty($data['videos_present']);
        $hasAttributes = array_key_exists('attribute_values', $data)
            || !empty($data['attributes_present']);
        $hasVariants = array_key_exists('variants', $data) || !empty($data['variants_present']);
        $hasLocal = array_key_exists('variant_dimensions', $data)
            || !empty($data['variant_dimensions_present']);
        $localDimensions = [];
        $hasGroupEditor = array_key_exists('group_editor', $data);
        $groupEditorRows = [];
        if ($hasGroupEditor) {
            try {
                $groupEditorRows = GroupEditor::normalize($data['group_editor'], 'product');
            } catch (\InvalidArgumentException $error) {
                $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
                return false;
            }
        }
        $hasGroup = !$hasGroupEditor &&
            (array_key_exists('group_ids', $data) || !empty($data['product_group_present']));
        $groupIds = [];
        $newGroupTitle = '';
        if ($hasGroup) {
            try {
                $groupIds = ProductGroups::normalizeIds($data['group_ids'] ?? []);
                $newGroupTitle = ProductGroups::normalizeNewTitle($data['new_group_title'] ?? '');
            } catch (\InvalidArgumentException $error) {
                $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
                return false;
            }
        }
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
                $requiredIds = CategoryAttributes::requiredForCategory(
                    (int) ($data['category_id'] ?? 0), $hierarchy['parents'], $hierarchy['rules']
                );
                $normalized = ProductAttributes::normalize(
                    $data['attribute_values'] ?? [],
                    (int) ($data['category_id'] ?? 0),
                    $definitions,
                    $categoryMap,
                    $requiredIds
                );
            } catch (\InvalidArgumentException $error) {
                $message = $error->getMessage();
                if (str_starts_with($message, 'COM_MINIMARKET_PRODUCT_ATTRIBUTES_REQUIRED:')) {
                    $attributeId = (int) substr($message, strlen('COM_MINIMARKET_PRODUCT_ATTRIBUTES_REQUIRED:'));
                    $this->setError(Text::sprintf(
                        'COM_MINIMARKET_PRODUCT_ATTRIBUTES_REQUIRED',
                        (string) ($definitions[$attributeId]['title'] ?? '#' . $attributeId)
                    ));
                } else {
                    $this->setError(Text::_(str_starts_with($message, 'COM_MINIMARKET_')
                        ? $message : 'COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID'));
                }
                return false;
            }
        }

        $variantRows = [];
        if ($hasLocal || $hasVariants || $hasMatrix) {
            try {
                if ($hasMatrix) {
                    $matrix = ProductSkuMatrix::normalize(
                        $db, (int) ($data['id'] ?? 0),
                        (int) ($data['category_id'] ?? 0),
                        $data['sku_matrix_payload'], (string) ($data['price'] ?? '0')
                    );
                    $localDimensions = $matrix['dimensions'];
                    $variantRows = $matrix['variants'];
                    $hasLocal = true;
                    $hasVariants = true;
                } else {
                    // Compatibility with installed versions that still post
                    // the previous native subform payload.
                    $localDimensions = VariantMatrix::normalize(
                        array_values((array) ($data['variant_dimensions'] ?? []))
                    );
                    ProductCatalogAxes::validate($db,
                        (int) ($data['category_id'] ?? 0),
                        (int) ($data['id'] ?? 0), $localDimensions);
                    $axes = ProductLocalDimensions::axesForVariants($localDimensions);
                    $variantRows = ProductVariants::normalize($data['variants'] ?? [], $axes);
                }
            } catch (\InvalidArgumentException | \JsonException $error) {
                $key = $error->getMessage();
                $this->setError(Text::_(str_starts_with($key, 'COM_MINIMARKET_')
                    ? $key : 'COM_MINIMARKET_VARIANTS_INVALID'));
                return false;
            }
        }

        if ($hasGallery && $rows && trim((string) ($data['image'] ?? '')) === '') {
            $this->setError(Text::_('COM_MINIMARKET_GALLERY_COVER_REQUIRED'));
            return false;
        }

        unset($data['group_ids'], $data['new_group_title'], $data['product_group_present'],
            $data['group_contents'], $data['group_editor']);
        unset($data['category_option_preview'], $data['sku_matrix_payload']);
        unset($data['variant_dimensions'], $data['variant_dimensions_present'],
            $data['variant_matrix'], $data['variants'], $data['variants_present'],
            $data['videos'], $data['videos_present'],
            $data['gallery'], $data['gallery_present'],
            $data['attribute_values'], $data['attributes_present']);
        $db->transactionStart();

        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }

            if ($hasVideos) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductVideos::replace($db, $productId, $videoRows);
            }
            if ($hasAttributes) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductAttributes::replace($db, $productId, $normalized);
            }
            if ($hasVariants) {
                $productId = (int) $this->getState($this->getName() . '.id');
                $variantIds=ProductVariants::replace($db, $productId, $variantRows);
                if ($hasLocal) {
                    ProductLocalDimensions::replace($db, $productId, $localDimensions,
                        $variantRows, $variantIds);
                }
            } elseif ($hasLocal) {
                // No SKU yet; allow saving local option definitions without
                // inserting a dummy SKU or touching the existing product trade fields.
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductLocalDimensions::replace($db, $productId, $localDimensions, [], []);
            }
            if ($hasGallery) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductGallery::replaceRows($db, $productId, $rows,
                    $hasVariants ? $variantRows : [],
                    $hasVariants ? ($variantIds ?? []) : []);
            }
            if ($hasGroupEditor) {
                $productId = (int) $this->getState($this->getName() . '.id');
                GroupEditor::saveProduct($db, $productId, $groupEditorRows);
            } elseif ($hasGroup) {
                $productId = (int) $this->getState($this->getName() . '.id');
                ProductGroups::replaceForProduct($db, $productId, $groupIds, $newGroupTitle);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_VARIANTS_SAVE_FAILED'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.product.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
