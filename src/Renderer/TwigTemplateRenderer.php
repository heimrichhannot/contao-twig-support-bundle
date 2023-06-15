<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Renderer;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigTemplateRenderer implements ServiceSubscriberInterface
{
    protected $twig;
    protected $templateLocator;
    protected $contaoFramework;

    /**
     * TwigTemplateRenderer constructor.
     */
    public function __construct(Environment $twig, TwigTemplateLocator $templateLocator, ContaoFramework $contaoFramework)
    {
        $this->twig = $twig;
        $this->templateLocator = $templateLocator;
        $this->contaoFramework = $contaoFramework;
    }

    /**
     * @throws \HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError                                               Configuration
     */
    public function render(string $templateName, array $templateData = [], ?TwigTemplateRendererConfiguration $configuration = null): string
    {
        if (!$configuration) {
            $configuration = new TwigTemplateRendererConfiguration();
        }

        $buffer = null;

        if (null === $configuration->getTemplatePath()) {
            try {
                $templatePath = $this->templateLocator->getTemplatePath($templateName);
            } catch (TemplateNotFoundException $e) {
                if ($configuration->getThrowExceptionOnError()) {
                    throw $e;
                }
                $buffer = '';
                $templatePath = 'Template not found: '.$templateName;
            }
        } else {
            $templatePath = $configuration->getTemplatePath();
        }

        if (null === $buffer) {
            try {
                $buffer = $this->twig->render($templatePath, $templateData);
            } catch (LoaderError | RuntimeError | SyntaxError $e) {
                if ($configuration->getThrowExceptionOnError()) {
                    throw $e;
                }
                $buffer = '';
            } catch (\Error $e) {
                throw new \Error(sprintf('Error rendering template "%s": %s', $templatePath, $e->getMessage()), $e->getCode(), $e);
            }
        }

        $buffer = $this->addTemplateComments($configuration, $templatePath, $buffer);

        return $buffer;
    }

    public static function getSubscribedServices()
    {
        return [
        ];
    }

    protected function addTemplateComments(?TwigTemplateRendererConfiguration $configuration, string $templatePath, string $buffer): string
    {
        if ($configuration->getShowTemplateComments() && $this->contaoFramework->isInitialized() && Config::get('debugMode')) {
            $buffer = "\n<!-- TWIG TEMPLATE START: $templatePath -->\n$buffer\n<!-- TWIG TEMPLATE END: $templatePath -->\n";
        }

        return $buffer;
    }
}
