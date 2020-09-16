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
use Webmozart\PathUtil\Path;

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
    public function getTwigTemplatePaths(bool $extension = false)
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

    /**
     * @param iterable|string $dir
     * @param string|null $twigKey
     * @param bool $extension
     * @return array
     */
    public function getTwigTemplatesInPath($dir, ?string $twigKey = null, bool $extension = false): array
    {
        if (is_iterable($dir)) {
            $files = $dir;
        } elseif (is_string($dir)) {
            $files = (new Finder())->in($dir)->files()->name('*.twig')->getIterator();
        } else {
            throw new \InvalidArgumentException("Template paths entry must be a folder (string) or an iterable");
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
}