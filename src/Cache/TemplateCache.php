<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Cache;

use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class TemplateCache implements CacheWarmerInterface, CacheClearerInterface
{
    public const CACHE_POOL_NAME = 'huh_twig_support';
    public const TEMPLATES_WITH_EXTENSION_CACHE_KEY = 'templates_with_extension';
    public const TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY = 'templates_without_extension';

    /**
     * @var TwigTemplateLocator
     */
    protected $templateLocator;

    /**
     * TemplateCacheWarmer constructor.
     */
    public function __construct(TwigTemplateLocator $templateLocator)
    {
        $this->templateLocator = $templateLocator;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $cache = new FilesystemAdapter(static::CACHE_POOL_NAME);
        $cache->save($cache->getItem(static::TEMPLATES_WITH_EXTENSION_CACHE_KEY)->set($this->templateLocator->getTemplates(true, true)));
        $cache->save($cache->getItem(static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY)->set($this->templateLocator->getTemplates(false, true)));
    }

    public function clear($cacheDir)
    {
        $cache = new FilesystemAdapter('huh_twig_support');
        $cache->deleteItems([static::TEMPLATES_WITH_EXTENSION_CACHE_KEY, static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY]);
    }
}
