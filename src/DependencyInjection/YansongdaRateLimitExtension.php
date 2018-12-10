<?php

namespace Yansongda\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class YansongdaRateLimitExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('yansongda_rate_limit.enable', $config['enable']);
        $container->setParameter('yansongda_rate_limit.redis_client', 'snc_redis.'.$config['redis_client']);
        $container->setParameter('yansongda_rate_limit.response', $config['response']);
        $container->setParameter('yansongda_rate_limit.display_headers', $config['display_headers']);
        $container->setParameter('yansongda_rate_limit.headers', $config['headers']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->getDefinition('yansongda_rate_limit.kernel')->replaceArgument(
            1,
            new Reference($container->getParameter('yansongda_rate_limit.redis_client'))
        );
    }
}
