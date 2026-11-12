<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Private file ingestion: uploads must never enter Joomla Media/public paths. */
final class ProductDigitalFile
{
    private const MAX_BYTES=52428800; // 50 MiB, additionally limited by PHP.
    private const EXTENSIONS=['pdf','zip','epub','mp3','mp4','txt'];

    public static function upload(DatabaseInterface $db,int $productId):void
    {
        $file=$_FILES['digital_upload']??null;
        if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return;
        if($productId<1 ||(int)($file['error']??-1)!==UPLOAD_ERR_OK
            ||!is_string($file['name']??null)
            ||!is_string($file['tmp_name']??null)
            ||(int)($file['size']??0)<1||(int)($file['size']??0)>self::MAX_BYTES
            ||!is_uploaded_file($file['tmp_name']))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        $db->setQuery('SELECT digital_delivery_mode,delivery_type FROM '.$db->quoteName('#__minimarket_products')
            .' WHERE id='.(int)$productId);
        $row=$db->loadAssoc();
        if(($row['delivery_type']??'')!=='digital'||($row['digital_delivery_mode']??'')!=='file')
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        $filename=basename(str_replace('\\','/',(string)$file['name']));
        $ext=strtolower((string)pathinfo($filename,PATHINFO_EXTENSION));
        if(!in_array($ext,self::EXTENSIONS,true)||mb_strlen($filename)>240
            ||preg_match('/[\x00-\x1f<>:"\\\\\/\|?*]/',$filename))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        $finfo=new \finfo(FILEINFO_MIME_TYPE);
        $mime=$finfo->file($file['tmp_name']);
        $mimes=['application/pdf','application/zip','application/x-zip-compressed',
            'application/epub+zip','audio/mpeg','video/mp4','text/plain','application/octet-stream'];
        if(!in_array($mime,$mimes,true))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        $dir=dirname(dirname(JPATH_ROOT)).'/minimarket-private-files';
        if(!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        if(!is_writable($dir)||realpath($dir)===false
            ||str_starts_with(realpath($dir).DIRECTORY_SEPARATOR,realpath(JPATH_ROOT).DIRECTORY_SEPARATOR))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        $key=bin2hex(random_bytes(24)).'.'.$ext;
        $target=$dir.'/'.$key;
        if(!move_uploaded_file($file['tmp_name'],$target))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_UPLOAD_ERROR');
        chmod($target,0600);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_products'))
            ->set($db->quoteName('digital_asset_key').'='.$db->quote($key))
            ->set($db->quoteName('digital_filename').'='.$db->quote($filename))
            ->where('id='.(int)$productId);
        $db->setQuery($q);$db->execute();
    }
}
