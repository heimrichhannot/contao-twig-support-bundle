<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('huh_twig_support');

        // Keep compatibility with symfony/config < 4.2
        if (!method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->root('huh_twig_support');
        } else {
            $rootNode = $treeBuilder->getRootNode();
        }

        $rootNode
            ->children()
                ->booleanNode('enable_template_loader')
                    ->defaultFalse()
                    ->info('Enable twig templates to be loaded by contao (enabled overriding core templates and select twig templates in the contao backend).')
                ->end()
                ->arrayNode('skip_templates')
                    ->info('Template names that should be skipped by the template loader.')
            ->example(['image', 'ce_no_twig', 'mod_html5_only'])
                    ->scalarPrototype()->end()
                ->end()
                ->integerNode('template_cache_lifetime')
                    ->defaultValue(0)
                    ->info('Template cache lifetime in seconds with a value 0 causing cache to be stored indefinitely (i.e. until the files are deleted).')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
