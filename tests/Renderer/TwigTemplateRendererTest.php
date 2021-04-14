<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Renderer;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRendererConfiguration;
use Twig\Environment;
use Twig\Error\LoaderError;

class TwigTemplateRendererTest extends ContaoTestCase
{
    /**
     * @return TwigTemplateRenderer
     *
     * @runInSeparateProcess
     */
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

        $templateLocator = $this->createMock(TwigTemplateLocator::class);
        $templateLocator->method('getTemplatePath')->willReturnCallback(function ($name) {
            switch ($name) {
                case 'none':
                    throw new TemplateNotFoundException('Not found');

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

        $configuration = (new TwigTemplateRendererConfiguration())->setShowTemplateComments(false)->setThrowExceptionOnError(false);
        $this->assertEmpty($instance->render('null', [], $configuration));

        $instance = $this->createTestInstance([
            'twig' => $twig,
            'templateLocator' => $templateLocator,
        ]);

        $hasError = false;

        try {
            $instance->render('none', []);
        } catch (TemplateNotFoundException $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $configuration = (new TwigTemplateRendererConfiguration())->setShowTemplateComments(false)->setThrowExceptionOnError(false);
        $this->assertEmpty($instance->render('none', [], $configuration));

        $configuration->setTemplatePath('template');
        $this->assertSame('template', $instance->render('template', [], $configuration));

        $configuration->setShowTemplateComments(true);
        $this->assertSame('template', $instance->render('template', [], $configuration));

        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('isInitialized')->willReturn(true);
        $instance = $this->createTestInstance([
            'twig' => $twig,
            'templateLocator' => $templateLocator,
            'framework' => $framework,
        ]);
        Config::set('debugMode', true);
        $this->assertSame("\n<!-- TWIG TEMPLATE START: template -->\ntemplate\n<!-- TWIG TEMPLATE END: template -->\n", $instance->render('template', [], $configuration));
    }
}
