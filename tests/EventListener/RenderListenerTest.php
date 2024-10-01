<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendTemplate;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;
use Contao\Widget;
use HeimrichHannot\TestUtilitiesBundle\Mock\TemplateMockTrait;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;
use HeimrichHannot\TwigSupportBundle\Exception\SkipTemplateException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Helper\NormalizerHelper;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RenderListenerTest extends ContaoTestCase
{
    use TemplateMockTrait;

    public function createTestInstance(array $parameters = [], ?MockBuilder $mockBuilder = null)
    {
        $container = $parameters['container'] ?? new ContainerBuilder();

        if (!isset($parameters['templateLocator'])) {
            $templateLocator = $this->createMock(TwigTemplateLocator::class);
            $templateLocator->method('getTemplatePath')->willReturnArgument(0);
            $parameters['templateLocator'] = $templateLocator;
        }

        if (!isset($parameters['eventDispatcher'])) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher->method('dispatch')->willReturnArgument(0);
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
        $parameters['twig'] = $parameters['twig'] ?? $this->createMock(Environment::class);

        if ($mockBuilder) {
            $instance = $mockBuilder->setConstructorArgs([
                $container,
                $parameters['templateLocator'],
                $parameters['eventDispatcher'],
                $parameters['requestStack'],
                $parameters['scopeMatcher'],
                $parameters['normalizer'],
                $parameters['bundleConfig'],
                $templateRenderer,
                $parameters['twig'],
            ])->getMock();
        } else {
            $instance = new RenderListener(
                $container,
                $parameters['templateLocator'],
                $parameters['eventDispatcher'],
                $parameters['requestStack'],
                $parameters['scopeMatcher'],
                $parameters['normalizer'],
                $parameters['bundleConfig'],
                $templateRenderer,
                $parameters['twig']
            );
        }

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

    public function testOnParseTemplate()
    {
        $templateLocator = $this->createMock(TwigTemplateLocator::class);
        $templateLocator->method('getTemplates')->willReturn(['test' => 'test']);

        // Template loader disabled
        $instance = $this->createTestInstance([
            'templateLocator' => $templateLocator,
        ]);
        $template = $this->mockTemplateObject(FrontendTemplate::class, 'test');
        $instance->onParseTemplate($template);
        $this->assertSame('test', $template->getName());

        // Template loader enabled, template is skipped by config
        $instance = $this->createTestInstance([
            'bundleConfig' => [
                'enable_template_loader' => true,
                'skip_templates' => ['test'],
            ],
            'templateLocator' => $templateLocator,
        ]);
        $template = $this->mockTemplateObject(FrontendTemplate::class, 'test');
        $instance->onParseTemplate($template);
        $this->assertSame('test', $template->getName());

        // Template loader enabled, template is known by contao template engine
        $loader = $this->createMock(FilesystemLoader::class);
        $loader->method('exists')->willReturn(true);
        $twig = $this->createMock(Environment::class);
        $twig->method('getLoader')->willReturn($loader);
        $instance = $this->createTestInstance([
            'bundleConfig' => [
                'enable_template_loader' => true,
            ],
            'twig' => $twig,
            'templateLocator' => $templateLocator,
        ]);
        $template = $this->mockTemplateObject(FrontendTemplate::class, 'test');
        $instance->onParseTemplate($template);
        $this->assertSame('test', $template->getName());

        // Template loader enabled
        $instance = $this->createTestInstance([
            'bundleConfig' => [
                'enable_template_loader' => true,
            ],
            'templateLocator' => $templateLocator,
        ]);

        $template = $this->mockTemplateObject(FrontendTemplate::class, 'test');
        $instance->onParseTemplate($template);
        $this->assertSame('twig_template_proxy', $template->getName());
    }

    public function testOnParseWidgetSkipTemplates()
    {
        $instance = $this->createTestInstance([
            'bundleConfig' => [
                'enable_template_loader' => true,
                'skip_templates' => ['form_text'],
            ],
        ]);

        $reflection = new ReflectionClass($instance);
        $reflectionProperty = $reflection->getProperty('templates');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($instance, ['form_row' => '', 'form_text' => '']);

        $widget = $this->mockClassWithProperties(Widget::class, ['template' => 'form_row']);
        $widget->method('inherit')->willReturn('after');
        $this->assertSame('after', $instance->onParseWidget('before', $widget));

        $widget = $this->mockClassWithProperties(Widget::class, ['template' => 'form_text']);
        $widget->method('inherit')->willReturn('after');
        $this->assertSame('before', $instance->onParseWidget('before', $widget));
    }

    public function testPrepareContaoTemplateSkipOnException()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (BeforeParseTwigTemplateEvent $event, $eventName = '') {
            if ('disabled' === $event->getTemplateName()) {
                throw new SkipTemplateException('SkipTemplateException');
            }

            return $event;
        });

        $instance = $this->createTestInstance([
            'eventDispatcher' => $dispatcher,
        ]);

        $reflection = new ReflectionClass($instance);
        $reflectionProperty = $reflection->getProperty('templates');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($instance, ['enabled' => '', 'disabled' => '']);

        $template = $this->mockTemplateObject(Template::class);
        $template->setName('enabled');
        $instance->prepareContaoTemplate($template);
        $this->assertSame('twig_template_proxy', $template->getName());

        $template = $this->mockTemplateObject(Template::class);
        $template->setName('disabled');
        $instance->prepareContaoTemplate($template);
        $this->assertSame('disabled', $template->getName());
    }

    public function testOnParseWidgetSkipOnException()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (BeforeParseTwigTemplateEvent $event, $eventName = '') {
            if ('form_text' === $event->getTemplateName()) {
                throw new SkipTemplateException('SkipTemplateException');
            }

            return $event;
        });

        $instance = $this->createTestInstance([
            'eventDispatcher' => $dispatcher,
            'bundleConfig' => [
                'enable_template_loader' => true,
            ],
        ]);

        $reflection = new ReflectionClass($instance);
        $reflectionProperty = $reflection->getProperty('templates');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($instance, ['form_row' => '', 'form_text' => '']);

        $widget = $this->mockClassWithProperties(Widget::class, ['template' => 'form_row']);
        $widget->method('inherit')->willReturn('after');
        $this->assertSame('after', $instance->onParseWidget('before', $widget));

        $widget = $this->mockClassWithProperties(Widget::class, ['template' => 'form_text']);
        $widget->method('inherit')->willReturn('after');
        $this->assertSame('before', $instance->onParseWidget('before', $widget));
    }
}
