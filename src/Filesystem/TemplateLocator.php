<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\TwigSupportBundle\Filesystem;


use Contao\CoreBundle\Config\ResourceFinderInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class TemplateLocator
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
     * Return all twig files.
     *
     * @return array
     */
    public function getTwigTemplatePaths()
    {
        $bundles = $this->kernel->getBundles();
        $twigFiles = [];

        $folders = [];

        if (\is_array($bundles)) {
            foreach (array_reverse($bundles) as $key => $value) {
                $path = $this->kernel->locateResource("@$key");

                $dir = rtrim($path, '/').'/Resources/views';

                if (!is_dir($dir)) {
                    continue;
                }
                $finder = new Finder();
                $twigKey = preg_replace('/Bundle$/', '', $key);

                foreach ($finder->in($dir)->files()->name('*.twig') as $file) {
                    /** @var SplFileInfo $file */
                    $name = $file->getBasename();
                    $legacyName = false !== strpos($name, 'html.twig') ? $file->getBasename('.html.twig') : $name;

                    if (isset($this->twigFiles[$name])) {
                        continue;
                    }

                    $twigFiles[$name] = "@$twigKey/".$file->getRelativePathname();

                    if ($legacyName !== $name) {
                        $twigFiles[$legacyName] = $twigFiles[$name];
                    }
                }
            }
        }

        $twigFiles = $this->getTwigTemplatesInPaths([
            $this->contaoResourceFinder->findIn('templates')->name('*.twig')->getIterator(),
            $this->kernel->getProjectDir().'/templates'
        ],
            $twigFiles
        );

        return $twigFiles;
    }

    /**
     * @param array $paths Contains folder paths or finder result iterables
     * @return array
     */
    public function getTwigTemplatesInPaths(array $paths, array $twigFiles = []): array
    {
        foreach ($paths as $path) {
            if (is_iterable($path)) {
                $templateFolderFiles = $path;
            } elseif (is_string($path)) {
                $templateFolderFiles = (new Finder())->in($path)->name('*.twig')->getIterator();
            } else {
                throw new \InvalidArgumentException("Template paths entry must be a folder (string) or an iterable");
            }

            foreach ($templateFolderFiles as $file) {
                $name = $file->getBasename();
                $legacyName = false !== strpos($name, 'html.twig') ? $file->getBasename('.html.twig') : $name;

                /* @var SplFileInfo $file */
                $twigFiles[$name] = $file->getRealPath();

                if ($legacyName !== $name) {
                    $twigFiles[$legacyName] = $twigFiles[$name];
                }
            }
        }

        return $twigFiles;
    }
}