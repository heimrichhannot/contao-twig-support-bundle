<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class HeimrichHannotTwigSupportExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $bundleConfig = $this->processConfiguration($configuration, $configs);
        $container->setParameter('huh_twig_support', $bundleConfig);
        $container->setParameter('huh_twig_support.template_cache_lifetime', $bundleConfig['template_cache_lifetime'] ?: 0);
    }

    public function getAlias()
    {
        return 'huh_twig_support';
    }
}
