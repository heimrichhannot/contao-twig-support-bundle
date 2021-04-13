<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendTemplate;
use Contao\TestCase\ContaoTestCase;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Helper\NormalizerHelper;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class RenderListenerTest extends ContaoTestCase
{
    public function createTestInstance(array $parameters = [])
    {
        if (!isset($parameters['templateLocator'])) {
            $templateLocator = $this->createMock(TwigTemplateLocator::class);
            $templateLocator->method('getTemplatePath')->willReturnArgument(0);
            $parameters['templateLocator'] = $templateLocator;
        }

        if (!isset($parameters['eventDispatcher'])) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher->method('dispatch')->willReturnArgument(1);
            $parameters['eventDispatcher'] = $eventDispatcher;
        }

        if (!isset($parameters['twig'])) {
            $twig = $this->createMock(Environment::class);
            $twig->method('render')->willReturnArgument(0);
            $parameters['twig'] = $twig;
        }

        if (!isset($parameters['requestStack'])) {
            $parameters['requestStack'] = $this->createMock(RequestStack::class);
        }

        if (!isset($parameters['scopeMatcher'])) {
            $parameters['scopeMatcher'] = $this->createMock(ScopeMatcher::class);
        }

        if (!isset($parameters['normalizer'])) {
            $parameters['normalizer'] = $this->createMock(NormalizerHelper::class);
        }

        if (!isset($parameters['bundleConfig'])) {
            $parameters['bundleConfig'] = [];
        }

        $templateRenderer = $parameters['templateRenderer'] ?? $this->createMock(TwigTemplateRenderer::class);

        $instance = new RenderListener(
            $parameters['templateLocator'],
            $parameters['eventDispatcher'],
            $parameters['requestStack'],
            $parameters['scopeMatcher'],
            $parameters['normalizer'],
            $parameters['bundleConfig'],
            $templateRenderer
        );

        return $instance;
    }

    public function testRender()
    {
        $templateRenderer = $this->createMock(TwigTemplateRenderer::class);
        $templateRenderer->method('render')->willReturnCallback(function ($template, $data) {
            TestCase::assertArrayHasKey('widget', $data);

            return $template;
        });
        $contaoTemplate = $this->mockClassWithProperties(Widget::class, [
            RenderListener::TWIG_TEMPLATE => 'widget_template',
            RenderListener::TWIG_CONTEXT => [],
        ]);
        $instance = $this->createTestInstance([
            'templateRenderer' => $templateRenderer,
        ]);
        $instance->render($contaoTemplate);

        $templateRenderer = $this->createMock(TwigTemplateRenderer::class);
        $templateRenderer->method('render')->willReturnCallback(function ($template, $data) {
            TestCase::assertArrayNotHasKey('widget', $data);

            return $template;
        });
        $contaoTemplate = $this->mockClassWithProperties(FrontendTemplate::class, [
            RenderListener::TWIG_TEMPLATE => 'widget_template',
            RenderListener::TWIG_CONTEXT => [],
        ]);
        $instance = $this->createTestInstance([
            'templateRenderer' => $templateRenderer,
        ]);
        $instance->render($contaoTemplate);
    }
}
