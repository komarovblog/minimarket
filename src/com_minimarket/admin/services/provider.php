<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory as ComponentDispatcherFactoryProvider;
use Joomla\CMS\Extension\Service\Provider\MVCFactory as MVCFactoryProvider;
use Joomla\CMS\Extension\Service\Provider\RouterFactory as RouterFactoryProvider;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use MiniMarket\Component\Minimarket\Administrator\Extension\MinimarketComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $namespace = '\\MiniMarket\\Component\\Minimarket';

        $container->registerServiceProvider(new MVCFactoryProvider($namespace));
        $container->registerServiceProvider(new ComponentDispatcherFactoryProvider($namespace));
        $container->registerServiceProvider(new RouterFactoryProvider($namespace));

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new MinimarketComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
