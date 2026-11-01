<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * MiniMarket stores a reusable relative media path, not Joomla's transient
 * #joomlaImage:// adapter metadata nor source image width/height.
 *
 * Every category/product/OG image and future gallery relation must use this
 * normalization at its server-side write boundary.
 */
final class MediaPath
{
    /**
     * @return string|null Clean site-relative path, '' for no selection,
     *                     null for an invalid/untrusted value.
     */
    public static function normalize(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $fragment = strpos($value, '#');
        if ($fragment !== false) {
            // Recognize only Joomla Media's own metadata suffix. Any other
            // fragment is invalid, never silently truncate arbitrary input.
            if (!str_starts_with(substr($value, $fragment), '#joomlaImage://')) {
                return null;
            }
            $value = substr($value, 0, $fragment);
        }

        if (strlen($value) > 1024
            || !str_starts_with($value, 'images/')
            || str_contains($value, '..')
            || preg_match('~[\x00-\x1f<>?#\\\\]~', $value)
        ) {
            return null;
        }

        return $value;
    }
}
