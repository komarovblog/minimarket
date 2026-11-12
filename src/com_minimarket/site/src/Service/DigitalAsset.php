<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Private file identifiers never become public Media URLs. */
final class DigitalAsset
{
    public static function path(string $key): ?string
    {
        if(!preg_match('/^[a-f0-9]{48}\.(?:pdf|zip|epub|mp3|mp4|txt)$/D',$key))return null;
        $root=realpath(JPATH_ROOT);
        $dir=realpath(dirname(dirname(JPATH_ROOT)).'/minimarket-private-files');
        if(!$root||!$dir||str_starts_with($dir.DIRECTORY_SEPARATOR,$root.DIRECTORY_SEPARATOR))return null;
        $file=$dir.DIRECTORY_SEPARATOR.$key;
        if(!is_file($file)||!is_readable($file)||is_link($file))return null;
        return $file;
    }
    public static function isReady(object $product):bool
    {
        return (string)($product->delivery_type??'physical')==='digital'
            &&(string)($product->digital_filename??'')!==''
            &&self::path((string)($product->digital_asset_key??''))!==null;
    }
}
