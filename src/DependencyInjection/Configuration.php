<?php

namespace Yansongda\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Yansongda\RateLimitBundle\Exceptions\InvalidConfigException;

class Configuration implements ConfigurationInterface
{
    const HTTP_TOO_MANY_REQUEST = 429;

    /**
     * Generates the configuration tree builder.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder('yansongda_rate_limit');

        $tree->root('yansongda_rate_limit')->children()
            ->booleanNode('enable')
                ->defaultTrue()->end()
            ->scalarNode('redis_client')
                ->defaultValue('default')->end()
            ->booleanNode('display_headers')
                ->defaultTrue()->end()
            ->arrayNode('headers')->addDefaultsIfNotSet()->children()
                ->scalarNode('limit')
                    ->defaultValue('X-RateLimit-Limit')->end()
                ->scalarNode('remaining')
                    ->defaultValue('X-RateLimit-Remaining')->end()
                ->scalarNode('reset')
                    ->defaultValue('X-RateLimit-Reset')->end()
                ->end()->end()
            ->arrayNode('response')->addDefaultsIfNotSet()->children()
                ->scalarNode('message')
                    ->defaultValue('Out Of Limit')->end()
                ->integerNode('code')
                    ->defaultValue(self::HTTP_TOO_MANY_REQUEST)->end()
                ->scalarNode('exception')
                    ->defaultNull()
                    ->validate()
                        ->always(function ($item) {
                            if ($item && !is_subclass_of($item, '\Exception')) {
                                throw new InvalidConfigException("[{$item}] Must Be Instanceof \Exception");
                            }

                            return $item;
                        })->end()
                    ->end()->end()->end();

        return $tree;
    }
}
