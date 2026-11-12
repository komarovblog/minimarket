<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\DigitalAsset;

/**
 * Idempotent paid-order digital entitlements, not a payment gateway.
 * A logged-in buyer must present the HMAC link and own the paid order.
 */
final class DigitalFulfillment
{
    public static function prepare(DatabaseInterface $db,int $orderId): array
    {
        $q=$db->getQuery(true)->select(['id','title','digital_asset_key','digital_filename'])
            ->from($db->quoteName('#__minimarket_order_items'))
            ->where('order_id='.$orderId)->where("delivery_type='digital'");
        $db->setQuery($q);
        $items=(array)$db->loadAssocList();
        foreach($items as $item){
            if(DigitalAsset::path((string)$item['digital_asset_key'])===null)
                throw new \DomainException('COM_MINIMARKET_DIGITAL_FILE_MISSING');
            // The order row is locked by the administrator controller, so
            // concurrent manual confirmations serialize without invalidating tokens.
            $check=$db->getQuery(true)->select('order_item_id')
                ->from($db->quoteName('#__minimarket_digital_grants'))
                ->where('order_item_id='.(int)$item['id']);
            $db->setQuery($check);
            if($db->loadResult()!==null)continue;
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_digital_grants'))
                ->columns($db->quoteName(['order_item_id','token_nonce','expires_at','max_downloads','created_at']))
                ->values(implode(',',[(string)(int)$item['id'],$db->quote(bin2hex(random_bytes(16))),
                    'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)','10','UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
        }
        return $items;
    }

    public static function send(DatabaseInterface $db,int $orderId,array $items,string $email):bool
    {
        if(!$items)return true;
        if(filter_var($email,FILTER_VALIDATE_EMAIL)===false)return false;
        $secret=(string)Factory::getConfig()->get('secret','');
        if(strlen($secret)<16)return false;
        $lang=Factory::getApplication()->getLanguage();
        $lang->load('com_minimarket',JPATH_SITE,$lang->getTag(),true);
        $lines=[Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_TITLE',$orderId),'',
            Text::_('COM_MINIMARKET_DIGITAL_MAIL_HELP'),''];
        foreach($items as $item){
            $q=$db->getQuery(true)->select(['token_nonce','expires_at'])
                ->from($db->quoteName('#__minimarket_digital_grants'))
                ->where('order_item_id='.(int)$item['id']);
            $db->setQuery($q);
            $grant=$db->loadAssoc();
            if(!$grant)return false;
            $token=hash_hmac('sha256','mm-download:'.(int)$item['id'].':'.$grant['token_nonce'],$secret);
            $url=Uri::root().'index.php?'.http_build_query([
                'option'=>'com_minimarket','task'=>'download.fetch',
                'item_id'=>(int)$item['id'],'token'=>$token
            ],'','&',PHP_QUERY_RFC3986);
            $lines[]=(string)$item['title'].": ".$url;
            $lines[]=Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_EXPIRES',$grant['expires_at']);
            $lines[]="";
        }
        try {
            $app=Factory::getApplication();
            $config=Factory::getConfig();
            $mailer=Factory::getMailer();
            $mailer->setSender([(string)$config->get('mailfrom'),(string)$config->get('fromname')]);
            $mailer->addRecipient($email);
            $mailer->setSubject(Text::sprintf('COM_MINIMARKET_DIGITAL_MAIL_TITLE',$orderId));
            $mailer->isHtml(false);
            $mailer->setBody(implode("\n",$lines));
            $sent=$mailer->send();
            if($sent===false || $sent instanceof \Throwable)return false;
            $ids=implode(',',array_map(static fn($a)=>(int)$a['id'],$items));
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_digital_grants'))
                ->set('last_sent_at=UTC_TIMESTAMP()')->where('order_item_id IN ('.$ids.')');
            $db->setQuery($q);$db->execute();
            return true;
        } catch(\Throwable){return false;}
    }
}
