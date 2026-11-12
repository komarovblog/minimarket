<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\DigitalAsset;

/** No direct asset URL; paid entitlements with account, signed link and cap. */
final class DownloadController extends BaseController
{
    public function fetch():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='GET')throw new \RuntimeException('Not found',404);
        $user=$app->getIdentity();
        if((int)$user->id<1||!empty($user->block))throw new \RuntimeException('Not found',404);
        $id=$app->input->getInt('item_id',0);
        $token=$app->input->getString('token','');
        if($id<1||!preg_match('/^[a-f0-9]{64}$/D',$token))
            throw new \RuntimeException('Not found',404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $q=$db->getQuery(true)->select([
                    'i.digital_asset_key','i.digital_filename','i.delivery_type',
                    'g.token_nonce','g.download_count','g.max_downloads','g.expires_at',
                    'o.user_id','o.payment_status','o.status','o.deleted_at'
                ])
                ->from($db->quoteName('#__minimarket_order_items','i'))
                ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=i.order_id')
                ->join('INNER',$db->quoteName('#__minimarket_digital_grants','g').' ON g.order_item_id=i.id')
                ->where('i.id='.$id);
            $db->setQuery((string)$q.' FOR UPDATE');
            $row=$db->loadAssoc();
            $secret=(string)Factory::getConfig()->get('secret','');
            if(!$row||strlen($secret)<16||(int)$row['user_id']!==(int)$user->id
                ||$row['payment_status']!=='paid'||$row['delivery_type']!=='digital'
                ||$row['status']==='cancelled'||$row['deleted_at']!==null
                ||(int)$row['download_count']>=(int)$row['max_downloads']
                ||strtotime($row['expires_at'].' UTC')<=time())
                throw new \DomainException('Not found');
            $expected=hash_hmac('sha256','mm-download:'.$id.':'.$row['token_nonce'],$secret);
            if(!hash_equals($expected,$token))throw new \DomainException('Not found');
            $file=DigitalAsset::path((string)$row['digital_asset_key']);
            if($file===null)throw new \DomainException('Not found');
            $name=basename(str_replace('\\','/',(string)$row['digital_filename']));
            if($name===''||preg_match('/[\x00-\x1f"\\\\\/]/',$name))
                throw new \DomainException('Not found');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_digital_grants'))
                ->set('download_count=download_count+1')->where('order_item_id='.$id);
            $db->setQuery($q);$db->execute();
            $db->transactionCommit();
        } catch(\Throwable) {
            $db->transactionRollback();
            throw new \RuntimeException('Not found',404);
        }
        $app->setHeader('Content-Type','application/octet-stream',true);
        $app->setHeader('Content-Disposition',"attachment; filename*=UTF-8''".rawurlencode($name),true);
        $app->setHeader('Cache-Control','private, no-store, max-age=0',true);
        $app->setHeader('X-Content-Type-Options','nosniff',true);
        $app->setHeader('Referrer-Policy','no-referrer',true);
        $app->setHeader('X-Robots-Tag','noindex, nofollow',true);
        $app->setHeader('Content-Length',(string)filesize($file),true);
        $app->sendHeaders();
        while(ob_get_level()>0)ob_end_clean();
        $handle=fopen($file,'rb');
        if($handle===false)throw new \RuntimeException('Not found',404);
        try{fpassthru($handle);}finally{fclose($handle);}
        $app->close();
    }
}
