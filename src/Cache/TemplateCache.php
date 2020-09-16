<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Cache;

use HeimrichHannot\TwigSupportBundle\Filesystem\TemplateLocator;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class TemplateCache implements CacheWarmerInterface, CacheClearerInterface
{
    public const TEMPLATE_CACHE_KEY = 'huh.twig_support.templates';

    /**
     * @var TemplateLocator
     */
    protected $templateLocator;

    /**
     * TemplateCacheWarmer constructor.
     */
    public function __construct(TemplateLocator $templateLocator)
    {
        $this->templateLocator = $templateLocator;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $templates = $this->templateLocator->getTwigTemplatePaths();
        $cache = new FilesystemCache();
        $cache->set(static::TEMPLATE_CACHE_KEY, $templates);
    }

    public function clear($cacheDir)
    {
        $cache = new FilesystemCache();
        $cache->deleteItem(static::TEMPLATE_CACHE_KEY);
    }
}
