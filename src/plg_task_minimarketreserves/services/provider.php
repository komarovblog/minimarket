<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use MiniMarket\Plugin\Task\Minimarketreserves\Extension\Minimarketreserves;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class,static function(Container $container): PluginInterface {
            $plugin=new Minimarketreserves(
                (array)PluginHelper::getPlugin('task','minimarketreserves')
            );
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));
            return $plugin;
        });
    }
};
