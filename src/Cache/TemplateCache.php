<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Cache;

use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class TemplateCache implements CacheWarmerInterface, CacheClearerInterface
{
    public const TEMPLATES_WITH_EXTENSION_CACHE_KEY = 'huh.twig_support.templates_with_extension';
    public const TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY = 'huh.twig_support.templates_without_extension';

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
        $cache = new FilesystemCache();
        $cache->set(static::TEMPLATES_WITH_EXTENSION_CACHE_KEY, $this->templateLocator->getTemplates(true, true));
        $cache->set(static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY, $this->templateLocator->getTemplates(false, true));
    }

    public function clear($cacheDir)
    {
        $cache = new FilesystemCache();
        $cache->deleteItem(static::TEMPLATES_WITH_EXTENSION_CACHE_KEY);
        $cache->deleteItem(static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY);
    }
}
