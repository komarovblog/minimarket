<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Block edits that would orphan a saved variant's attribute/option references.
 * Called BEFORE AttributeModel writes its modified dictionary or metadata.
 */
final class VariantDependencies
{
    public static function assertAttributeChange(
        DatabaseInterface $db, int $attributeId, string $type,
        string $inputMode, int $isVariant, string $textOptions, array $dictionaryRows
    ): void {
        if ($attributeId < 1) return;
        $q = $db->getQuery(true)->select('selection_json')
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('selection_json') . ' LIKE ' .
                $db->quote('%"' . $attributeId . '":%'));
        $db->setQuery($q);
        $selections = [];
        foreach ((array) $db->loadColumn() as $json) {
            $decoded = json_decode((string) $json, true);
            $value = is_array($decoded) ? ($decoded[$attributeId] ?? null) : null;
            if (is_string($value)) $selections[] = $value;
        }
        if (!$selections) return;
        if ($isVariant !== 1 || !in_array($type, ['color','list','text'], true)
            || ($type === 'text' && $inputMode !== 'presets')) {
            throw new \RuntimeException('COM_MINIMARKET_VARIANT_REFERENCES_IN_USE');
        }
        $allowed = [];
        if ($type === 'text') {
            foreach (TextPresetRows::values($textOptions) as $value) $allowed['t:' . $value] = true;
        } else {
            foreach ($dictionaryRows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) $allowed['d:' . $id] = true;
            }
        }
        foreach ($selections as $choice) {
            if (!isset($allowed[$choice])) {
                throw new \RuntimeException('COM_MINIMARKET_VARIANT_REFERENCES_IN_USE');
            }
        }
    }
}
