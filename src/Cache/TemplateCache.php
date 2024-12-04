<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
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

    protected TwigTemplateLocator $templateLocator;
    protected FilesystemAdapter $templateCache;

    public function __construct(TwigTemplateLocator $templateLocator, FilesystemAdapter $templateCache)
    {
        $this->templateLocator = $templateLocator;
        $this->templateCache = $templateCache;
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp($cacheDir): array
    {
        $this->templateCache->save(
            $this->templateCache
                ->getItem(static::TEMPLATES_WITH_EXTENSION_CACHE_KEY)
                ->set($this->templateLocator->getTemplates(true, true))
        );
        $this->templateCache->save(
            $this->templateCache
                ->getItem(static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY)
                ->set($this->templateLocator->getTemplates(false, true))
        );

        return [];
    }

    public function clear($cacheDir): void
    {
        $this->templateCache
            ->deleteItems([
                static::TEMPLATES_WITH_EXTENSION_CACHE_KEY,
                static::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY,
            ]);
    }
}
