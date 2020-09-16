<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Filesystem;

use Contao\CoreBundle\Config\ResourceFinderInterface;
use HeimrichHannot\TwigSupportBundle\Cache\TemplateCache;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use SplFileInfo;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\PathUtil\Path;

class TwigTemplateLocator
{
    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var ResourceFinderInterface
     */
    protected $contaoResourceFinder;

    public function __construct(KernelInterface $kernel, ResourceFinderInterface $contaoResourceFinder)
    {
        $this->kernel = $kernel;
        $this->contaoResourceFinder = $contaoResourceFinder;
    }

    /**
     * Return a twig template path by template name (without or with extension).
     *
     * @throws TemplateNotFoundException
     */
    public function getTemplatePath(string $name, bool $disableCache = false): string
    {
        if (($templates = $this->getTemplates(false, $disableCache)) && \array_key_exists($name, $templates)) {
            return $templates[$name];
        } elseif (($templates = $this->getTemplates(true, $disableCache)) && \array_key_exists($name, $templates)) {
            return $templates[$name];
        }

        throw new TemplateNotFoundException(sprintf('Unable to find template "%s".', $name));
    }

    /**
     * Return a list of all twig templates and their paths.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTemplates(bool $extension = false, bool $disableCache = false): array
    {
        if ('dev' !== $this->kernel->getEnvironment() && !$disableCache) {
            $cacheKey = TemplateCache::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY;

            if ($extension) {
                $cacheKey = TemplateCache::TEMPLATES_WITH_EXTENSION_CACHE_KEY;
            }

            $cache = new FilesystemCache();

            if (!$cache->has($cacheKey)) {
                $cache->set($cacheKey, $this->getTwigTemplatePaths(false));
            }

            return $cache->get($cacheKey);
        }

        return $this->getTwigTemplatePaths(false);
    }

    /**
     * @param iterable|string $dir
     */
    public function getTwigTemplatesInPath($dir, ?string $twigKey = null, bool $extension = false): array
    {
        if (is_iterable($dir)) {
            $files = $dir;
        } elseif (\is_string($dir)) {
            $files = (new Finder())->in($dir)->files()->name('*.twig')->getIterator();
        } else {
            throw new \InvalidArgumentException('Template paths entry must be a folder (string) or an iterable');
        }

        $twigFiles = [];

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $name = $file->getBasename();

            if (!$extension) {
                $name = Path::getFilenameWithoutExtension($name, '.html.twig');
            }

            if (!$twigKey) {
                $twigFiles[$name] = Path::makeRelative($file->getRealPath(), $this->kernel->getProjectDir().'/templates');
            } else {
                $twigFiles[$name] = "@$twigKey/".$file->getRelativePathname();
            }
        }

        return $twigFiles;
    }

    /**
     * Return all twig file path.
     *
     * @return array
     */
    protected function getTwigTemplatePaths(bool $extension = false)
    {
        $bundles = $this->kernel->getBundles();
        $twigFiles = [];

        if (\is_array($bundles)) {
            foreach (array_reverse($bundles) as $key => $value) {
                $path = $this->kernel->locateResource("@$key");

                $dir = rtrim($path, '/').'/Resources/views';

                if (!is_dir($dir)) {
                    continue;
                }
                $twigKey = preg_replace('/Bundle$/', '', $key);

                $twigFiles = array_merge($twigFiles, $this->getTwigTemplatesInPath($dir, $twigKey, $extension));
            }
        }

        // Bundle template folders
        $twigFiles = array_merge($twigFiles, $this->getTwigTemplatesInPath(
            $this->contaoResourceFinder->findIn('templates')->name('*.twig')->getIterator(), null, $extension));

        // Project template folder
        $twigFiles = array_merge($twigFiles, $this->getTwigTemplatesInPath(
            $this->kernel->getProjectDir().'/templates', null, $extension));

        return $twigFiles;
    }
}
