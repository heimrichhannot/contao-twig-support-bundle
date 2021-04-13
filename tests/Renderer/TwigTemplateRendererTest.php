<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Renderer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use Twig\Environment;
use Twig\Error\LoaderError;

class TwigTemplateRendererTest extends ContaoTestCase
{
    public function createTestInstance(array $parameters = [])
    {
        $twig = $parameters['twig'] ?? $this->createMock(Environment::class);
        $templateLocator = $parameters['templateLocator'] ?? $this->createMock(TwigTemplateLocator::class);
        $framework = $parameters['framework'] ?? $this->createMock(ContaoFramework::class);
        $instance = new TwigTemplateRenderer($twig, $templateLocator, $framework);

        return $instance;
    }

    public function testRender()
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($name, $data) {
            switch ($name) {
               case null:
               case 'null':
                   throw new LoaderError('Loader error');

               default:
                   return $name;
           }
        });

        $instance = $this->createTestInstance([
            'twig' => $twig,
        ]);

        $hasError = false;

        try {
            $instance->render('null', []);
        } catch (LoaderError $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $templateLocator = $this->createMock(TwigTemplateLocator::class);
        $templateLocator->method('getTemplatePath')->willReturnArgument(0);

        $instance = $this->createTestInstance([
            'twig' => $twig,
            'templateLocator' => $templateLocator,
        ]);

        $instance->render('template', []);
    }
}
