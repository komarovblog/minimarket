<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\DigitalAsset;
use MiniMarket\Component\Minimarket\Site\Service\DigitalLicensePool;

/**
 * Paid digital fulfillment: one file grant / N reserved license codes per order
 * item. Manual/cloud items remain pending for a human operator. No fake API.
 */
final class DigitalFulfillment
{
    public static function prepare(DatabaseInterface $db,int $orderId):array
    {
        $q=$db->getQuery(true)->select([
                'id','product_id','quantity','title','digital_asset_key',
                'digital_filename','digital_delivery_mode','digital_delivery_note'
            ])->from($db->quoteName('#__minimarket_order_items'))
            ->where('order_id='.$orderId)->where("delivery_type='digital'");
        $db->setQuery($q);
        $items=(array)$db->loadAssocList();
        foreach($items as $item){
            $mode=(string)$item['digital_delivery_mode'];
            if($mode==='license'){
                DigitalLicensePool::assign($db,(int)$item['product_id'],
                    (int)$item['id'],(int)$item['quantity']);
                continue;
            }
            if(in_array($mode,['manual','cloud'],true))continue;
            if($mode!=='file'||DigitalAsset::path((string)$item['digital_asset_key'])===null)
                throw new \DomainException('COM_MINIMARKET_DIGITAL_FILE_MISSING');
            $q=$db->getQuery(true)->select('order_item_id')
                ->from($db->quoteName('#__minimarket_digital_grants'))
                ->where('order_item_id='.(int)$item['id']);
            $db->setQuery($q);
            if($db->loadResult()!==null)continue;
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_digital_grants'))
                ->columns($db->quoteName(['order_item_id','token_nonce','expires_at','max_downloads','created_at']))
                ->values(implode(',',[(string)(int)$item['id'],$db->quote(bin2hex(random_bytes(16))),
                    'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)','10','UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
        }
        return $items;
    }

    public static function allAutomatic(array $items):bool
    {
        foreach($items as $item)
            if(!in_array((string)($item['digital_delivery_mode']??'file'),['file','license'],true))
                return false;
        return true;
    }

    public static function send(DatabaseInterface $db,int $orderId,array $items,
        string $email,int $userId=0):bool
    {
        if(!$items)return true;
        if(filter_var($email,FILTER_VALIDATE_EMAIL)===false)return false;
        $secret=(string)Factory::getConfig()->get('secret','');
        if(strlen($secret)<16)return false;
        $lang=Factory::getApplication()->getLanguage();
        $lang->load('com_minimarket',JPATH_SITE,$lang->getTag(),true);
        $lines=[Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_TITLE',$orderId),'',
            Text::_($userId>0?'COM_MINIMARKET_DIGITAL_MAIL_HELP':
                'COM_MINIMARKET_DIGITAL_MAIL_GUEST_HELP'),''];
        $grantIds=[];
        foreach($items as $item){
            $mode=(string)($item['digital_delivery_mode']??'file');
            if($mode==='license'){
                $keys=DigitalLicensePool::forOrderItem($db,(int)$item['id']);
                if(count($keys)!==(int)$item['quantity'])return false;
                $lines[]=(string)$item['title'].':';
                foreach($keys as $key)$lines[]=$key;
                $lines[]='';
                continue;
            }
            if(in_array($mode,['manual','cloud'],true)){
                $lines[]=(string)$item['title'].': '
                    .Text::_('COM_MINIMARKET_DIGITAL_MANUAL_PENDING');
                $lines[]='';
                continue;
            }
            if($mode!=='file')return false;
            $q=$db->getQuery(true)->select(['token_nonce','expires_at'])
                ->from($db->quoteName('#__minimarket_digital_grants'))
                ->where('order_item_id='.(int)$item['id']);
            $db->setQuery($q);$grant=$db->loadAssoc();
            if(!$grant)return false;
            $token=hash_hmac('sha256','mm-download:'.(int)$item['id'].':'.$grant['token_nonce'],$secret);
            $url=Uri::root().'index.php?'.http_build_query([
                'option'=>'com_minimarket','task'=>'download.fetch',
                'item_id'=>(int)$item['id'],'token'=>$token
            ],'','&',PHP_QUERY_RFC3986);
            $lines[]=(string)$item['title'].": ".$url;
            $lines[]=Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_EXPIRES',$grant['expires_at']);
            $lines[]='';
            $grantIds[]=(int)$item['id'];
        }
        if(!self::mail($email,Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_TITLE',$orderId),
            implode("\n",$lines)))return false;
        if($grantIds){
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_digital_grants'))
                ->set('last_sent_at=UTC_TIMESTAMP()')
                ->where('order_item_id IN ('.implode(',',$grantIds).')');
            $db->setQuery($q);$db->execute();
        }
        return true;
    }

    public static function mail(string $to,string $subject,string $body):bool
    {
        if(filter_var($to,FILTER_VALIDATE_EMAIL)===false)return false;
        try{
            $cfg=Factory::getConfig();
            $mailer=Factory::getMailer();
            $mailer->setSender([(string)$cfg->get('mailfrom'),(string)$cfg->get('fromname')]);
            $mailer->addRecipient($to);
            $mailer->setSubject($subject);
            $mailer->isHtml(false);
            $mailer->setBody($body);
            $sent=$mailer->send();
            return $sent!==false && !($sent instanceof \Throwable);
        }catch(\Throwable){return false;}
    }
}
