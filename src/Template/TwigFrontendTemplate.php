<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Template;

use Contao\Config;
use Contao\FrontendTemplate;
use Contao\System;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigFrontendTemplate extends FrontendTemplate
{
    public function inherit()
    {
        $container = System::getContainer();

        if ($container->has(TwigTemplateLocator::class) && $container->has('twig')) {
            if (null === $this->strTemplate) {
                return '';
            }

            try {
                $twigTemplatePath = $container->get(TwigTemplateLocator::class)->getTemplatePath($this->strTemplate);
            } catch (TemplateNotFoundException $e) {
                return '';
            }

            try {
                $buffer = $container->get('twig')->render($twigTemplatePath, $this->getTwigContext());
            } catch (LoaderError | RuntimeError | SyntaxError $e) {
                return '';
            }

            if (Config::get('debugMode')) {
                $buffer = "\n<!-- TWIG TEMPLATE START: $twigTemplatePath -->\n$buffer\n<!-- TWIG TEMPLATE END: $twigTemplatePath -->\n";
            }

            return $buffer;
        }

        return parent::inherit();
    }

    protected function getTwigContext(): array
    {
        $context = $this->arrData;

        return $context;
    }
}
