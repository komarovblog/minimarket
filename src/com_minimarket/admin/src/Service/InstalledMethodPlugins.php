<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Installed MiniMarket plugins only; do not imply unimplemented APIs work. */
final class InstalledMethodPlugins
{
    public static function list(DatabaseInterface $db,string $kind): array
    {
        if(!in_array($kind,['delivery','payment'],true))return [];
        $prefix=$kind==='delivery'?"('delivery','shipping')":"('payment')";
        $q=$db->getQuery(true)->select(['extension_id','name','element','enabled'])
            ->from($db->quoteName('#__extensions'))
            ->where('type='.$db->quote('plugin'))
            ->where('folder='.$db->quote('minimarket'))
            ->where("SUBSTRING_INDEX(element,'_',1) IN ".$prefix)
            ->order('name ASC,extension_id ASC');
        $db->setQuery($q,0,200);
        return (array)$db->loadObjectList();
    }
}
