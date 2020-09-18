<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('huh_twig_support');

        $rootNode
            ->children()
                ->booleanNode('enable_template_loader')
                    ->defaultFalse()
                    ->info('Enable twig templates to be loaded by contao (enabled overriding core templates and select twig templates in the contao backend).')
                    ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
