<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Filesystem;

use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Contao\ThemeModel;
use Contao\Validator;
use HeimrichHannot\TwigSupportBundle\Cache\TemplateCache;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;
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

    protected $requestStack;

    protected $scopeMatcher;

    /**
     * @var array|null
     */
    protected $templates;
    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    public function __construct(KernelInterface $kernel, ResourceFinderInterface $contaoResourceFinder, RequestStack $requestStack, ScopeMatcher $scopeMatcher, Stopwatch $stopwatch)
    {
        $this->kernel = $kernel;
        $this->contaoResourceFinder = $contaoResourceFinder;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Return a twig template path by template name (without or with extension).
     *
     * Options:
     * - (bool) disableCache: Set to true to disable cache. Cache is disabled by default in dev environment. Default false.
     *
     * @throws TemplateNotFoundException
     */
    public function getTemplatePath(string $templateName, array $options = []): string
    {
        $templateName = basename($templateName);
        $themeFolder = '';
        $disableCache = isset($options['disableCache']) && true === $options['disableCache'];

        if ($this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest())) {
            /* @var PageModel $objPage */
            global $objPage;

            if ('' != $objPage->templateGroup) {
                if (Validator::isInsecurePath($objPage->templateGroup)) {
                    throw new \RuntimeException('Invalid path '.$objPage->templateGroup);
                }

                $themeFolder = $objPage->templateGroup;
            }
        }

        if ((!$templates = $this->getTemplates(false, $disableCache)) || !\array_key_exists($templateName, $templates)) {
            if ((!$templates = $this->getTemplates(true, $disableCache)) || !\array_key_exists($templateName, $templates)) {
                throw new TemplateNotFoundException(sprintf('Unable to find template "%s".', $templateName));
            }
        }

        $template = $templates[$templateName];

        if (!empty($themeFolder)) {
            if ('templates' === substr($themeFolder, 0, 9)) {
                $themeFolder = trim(substr($themeFolder, 9), '/');
            }
            $pathLength = \strlen($themeFolder);

            foreach ($template['paths'] as $path) {
                if ($themeFolder === substr($path, 0, $pathLength)) {
                    return $path;
                }
            }
        }

        foreach ($template['paths'] as $path) {
            if ('@' !== substr($path, 0, 1)) {
                return $path;
            }
        }

        return end($template['paths']);
    }

    /**
     * Return all twig template files of a particular group as array.
     *
     * Configuration options:
     * - (bool) extension: Return the file extension in the file names. Default false.
     * - (bool) disableCache: Set to true to disable cache. Cache is disabled by default in dev environment. Default false.
     *
     * , array $arrAdditionalMapper=array(), $strDefaultTemplate='', string $fileExtension
     *
     * @param string|string[] $prefixes
     * @param array           $arrAdditionalMapper
     * @param string          $strDefaultTemplate
     * @param string          $fileExtension
     */
    public function getTemplateGroup($prefixes, array $configuration = []): array
    {
        if (!\is_array($prefixes) && !\is_string($prefixes)) {
            throw new \InvalidArgumentException('Only string or array are allowed!');
        }

        if (\is_string($prefixes)) {
            $prefixes = [$prefixes];
        }

        $templateNames = [];
        $disableCache = isset($configuration['disableCache']) && true === $configuration['disableCache'];

        foreach ($prefixes as $prefix) {
            $templateNames = array_merge($templateNames,
                $this->getPrefixedFiles($prefix, $configuration)
            );
        }

        try {
            $objTheme = ThemeModel::findAll(['order' => 'name']);
        } catch (\Exception $e) {
            $objTheme = null;
        }

        $options = [];

        foreach ($templateNames as $templateName) {
            if ((!$templates = $this->getTemplates(false, $disableCache)) || !\array_key_exists($templateName, $templates)) {
                continue;
            }

            $template = $templates[$templateName];

            $templatePathList = [];

            foreach ($template['paths'] as $path) {
                if ($path && '@' === substr($path, 0, 1)) {
                    $templatePathList['bundles'][] = explode('/', $path)[0];
                } else {
                    $folders = explode('/', $path);

                    if (\count($folders) <= 1) {
                        $templatePathList['global'] = $path;
                    } else {
                        foreach ($objTheme as $theme) {
                            $themeFolder = substr($objTheme->templates, 10);

                            if ($themeFolder === substr($path, 0, \strlen($themeFolder))) {
                                $templatePathList['themefolders'][] = $theme->name;
                            }
                        }
                    }
                }
            }
            $optionLabel = '';

            if (isset($templatePathList['global'])) {
                $optionLabel .= $GLOBALS['TL_LANG']['MSC']['global'].', ';
            }

            if (isset($templatePathList['themefolders']) && !empty($templatePathList['themefolders'])) {
                $optionLabel .= implode(', ', $templatePathList['themefolders']).', ';
            }

            if (isset($templatePathList['bundles']) && !empty($templatePathList['bundles'])) {
                $optionLabel .= implode(', ', $templatePathList['bundles']);
            }

            $options[$templateName] = $templateName.' ('.trim($optionLabel, ', ').')';
        }

        return $options;
    }

    /**
     * Return the files matching a prefix as array.
     *
     * Configuration options:
     * - (bool) extension: Return the file extension in the file names. Default false.
     * - (bool) disableCache: Set to true to disable cache. Cache is disabled by default in dev environment. Default false.
     *
     * @param string $prefix The prefix (e.g. "moo_")
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array An array of matching files
     */
    public function getPrefixedFiles(string $prefix, array $configuration = [])
    {
        $disableCache = isset($configuration['disableCache']) && true === $configuration['disableCache'];
        $extension = isset($configuration['extension']) && true === $configuration['extension'];

        if (rtrim($prefix, '_)') === $prefix) {
            $prefix .= '($|_)';
        }

        return array_values(preg_grep('/^'.$prefix.'/', array_keys($this->getTemplates($extension, $disableCache))));
    }

    /**
     * Return a list of all twig templates and their paths.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTemplates(bool $extension = false, bool $disableCache = false): array
    {
        if (!$this->templates) {
            if ('dev' === $this->kernel->getEnvironment() || $disableCache) {
                $this->templates = $this->generateContaoTwigTemplatePaths(false);
            } else {
                $cacheKey = TemplateCache::TEMPLATES_WITHOUT_EXTENSION_CACHE_KEY;

                if ($extension) {
                    $cacheKey = TemplateCache::TEMPLATES_WITH_EXTENSION_CACHE_KEY;
                }

                $cache = new FilesystemAdapter(TemplateCache::CACHE_POOL_NAME);
                $cacheItem = $cache->getItem($cacheKey);

                if (!$cacheItem->isHit()) {
                    $cacheItem->set($this->generateContaoTwigTemplatePaths(false));
                    $cache->save($cacheItem);
                }
                $templates = $cache->getItem($cacheKey)->get();

                if (!\is_array($templates)) {
                    // clean invalid cache entry
                    $templates = $this->generateContaoTwigTemplatePaths(false);
                    $cache->deleteItem($cacheKey);
                }
                $this->templates = $templates;
            }
        }

        return $this->templates;
    }

    /**
     * Return twig templates in a given path.
     *
     * @param iterable|string $dir
     */
    public function getTwigTemplatesInPath($dir, ?string $twigKey = null, bool $extension = false): array
    {
        $stopwatchname = 'TwigTemplateLocator::getTwigTemplatesInPath()';
        $this->stopwatch->start($stopwatchname);

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
                $twigFiles[$name]['paths'][] = Path::makeRelative($file->getRealPath(), $this->kernel->getProjectDir().'/templates');
            } else {
                $twigFiles[$name]['paths'][] = "@$twigKey/".$file->getRelativePathname();
            }
        }
        $this->stopwatch->stop($stopwatchname);

        return $twigFiles;
    }

    /**
     * Return all twig file paths in the typical folders.
     */
    protected function generateContaoTwigTemplatePaths(bool $extension = false): array
    {
        $bundles = $this->kernel->getBundles();
        $twigFiles = [];

        if (\is_array($bundles)) {
            foreach ($bundles as $key => $bundle) {
                $path = $bundle->getPath();

                $dir = rtrim($path, '/').'/Resources/views';

                if (!is_dir($dir)) {
                    continue;
                }
                $twigKey = preg_replace('/Bundle$/', '', $key);

                $twigFiles = array_merge_recursive($twigFiles, $this->getTwigTemplatesInPath($dir, $twigKey, $extension));
            }
        }

        // Bundle template folders
        $twigFiles = array_merge_recursive($twigFiles, $this->getTwigTemplatesInPath(
            $this->contaoResourceFinder->findIn('templates')->name('*.twig')->getIterator(), null, $extension));

        // Project template folder
        $twigFiles = array_merge_recursive($twigFiles, $this->getTwigTemplatesInPath(
            $this->kernel->getProjectDir().'/templates', null, $extension));

        return $twigFiles;
    }
}
