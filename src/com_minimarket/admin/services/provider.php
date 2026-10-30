<?php
/**
 * Service provider for the MiniMarket component.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\Component;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory as ComponentDispatcherFactoryProvider;
use Joomla\CMS\Extension\Service\Provider\MVCFactory as MVCFactoryProvider;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $namespace = '\\MiniMarket\\Component\\Minimarket';

        $container->registerServiceProvider(new MVCFactoryProvider($namespace));
        $container->registerServiceProvider(new ComponentDispatcherFactoryProvider($namespace));

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                return new Component($container->get(ComponentDispatcherFactoryInterface::class));
            }
        );
    }
};
