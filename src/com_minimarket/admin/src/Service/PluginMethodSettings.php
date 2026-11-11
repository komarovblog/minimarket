<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Only installed MiniMarket plugin manifest parameters. Never copy plugin
 * settings into component options and never invent an alternate provider API.
 */
final class PluginMethodSettings
{
    public static function load(DatabaseInterface $db,string $kind,int $id): ?array
    {
        if($id<1||!in_array($kind,['delivery','payment','registration'],true))return null;
        $plugin=null;
        foreach(InstalledMethodPlugins::list($db,$kind) as $row){
            if((int)$row->extension_id===$id){$plugin=$row;break;}
        }
        if($plugin===null)return null;
        $db->setQuery($db->getQuery(true)->select('params')
          ->from($db->quoteName('#__extensions'))
          ->where('extension_id='.(int)$id)
          ->where('type='.$db->quote('plugin'))
          ->where('folder='.$db->quote('minimarket')));
        $raw=(string)($db->loadResult()??'{}');
        $params=json_decode($raw,true);
        if(!is_array($params))$params=[];
        return ['plugin'=>$plugin,'form'=>self::form($plugin,$params),'params'=>$params];
    }

    public static function form(object $plugin,array $params): ?\Joomla\CMS\Form\Form
    {
        $element=(string)$plugin->element;
        if(!preg_match('/^[a-z0-9_]{2,100}$/D',$element))return null;
        $dir=JPATH_PLUGINS.'/minimarket/'.$element;
        $path=$dir.'/'.$element.'.xml';
        if(!is_file($path))return null;
        $xml=@simplexml_load_file($path,'SimpleXMLElement',LIBXML_NONET);
        if($xml===false||!isset($xml->config->fields))return null;
        $factory=Factory::getContainer()->get(FormFactoryInterface::class);
        $form=$factory->createForm('com_minimarket.pluginmethod.'.(int)$plugin->extension_id,
          ['control'=>'plugin']);
        if(!$form->load($xml->config))return null;
        $form->bind(['params'=>$params]);
        return $form;
    }

    public static function save(DatabaseInterface $db,string $kind,int $id,array $posted):void
    {
        $db->transactionStart();
        try {
            $data=self::load($db,$kind,$id);
            if($data===null||$data['form']===null)
                throw new \DomainException('COM_MINIMARKET_PLUGIN_METHOD_NOT_CONFIGURABLE');
            $form=$data['form'];
            if(!is_array($posted['params']??null))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $filtered=$form->filter(['params'=>$posted['params']]);
            if(!is_array($filtered)||!is_array($filtered['params']??null)
                ||!$form->validate($filtered))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            // Filtered native Joomla form fields only; preserve stored values
            // not submitted by the form (e.g. hidden secrets).
            $params=array_replace_recursive($data['params'],$filtered['params']);
            $json=json_encode($params,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $q=$db->getQuery(true)->update($db->quoteName('#__extensions'))
              ->set($db->quoteName('params').'='.$db->quote($json))
              ->where('extension_id='.(int)$id)->where('type='.$db->quote('plugin'))
              ->where('folder='.$db->quote('minimarket'));
            $db->setQuery($q);$db->execute();
            $db->transactionCommit();
        }catch(\Throwable $error){
            $db->transactionRollback();throw $error;
        }
    }
}
