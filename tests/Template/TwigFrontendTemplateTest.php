<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Template;

use Contao\Config;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Template\TwigFrontendTemplate;
use Twig\Environment;
use Twig\Error\LoaderError;

class TwigFrontendTemplateTest extends ContaoTestCase
{
    public function testInherit()
    {
        $instance = $this->getMockBuilder(TwigFrontendTemplate::class)->disableOriginalConstructor()->setMethods(null)->getMock();
        $container = $this->mockContainer();
        System::setContainer($container);
        $this->assertEmpty($instance->inherit());

        $locator = $this->createMock(TwigTemplateLocator::class);
        $locator->method('getTemplatePath')->willThrowException(new TemplateNotFoundException());

        $twig = $this->createMock(Environment::class);

        $container->set(TwigTemplateLocator::class, $locator);
        $container->set('twig', $twig);

        System::setContainer($container);
        $this->assertEmpty($instance->inherit());

        $instance->setName('huh_foo_bar');
        $this->assertEmpty($instance->inherit());

        $locator = $this->createMock(TwigTemplateLocator::class);
        $locator->method('getTemplatePath')->willReturn('huh_foo_bar.html.twig');
        $container->set(TwigTemplateLocator::class, $locator);

        $twig->method('render')->willThrowException(new LoaderError('Loader error'));
        $container->set('twig', $twig);

        System::setContainer($container);
        $this->assertEmpty($instance->inherit());

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<p>Foo bar</p>');
        $container->set('twig', $twig);
        $this->assertSame('<p>Foo bar</p>', $instance->inherit());

        Config::set('debugMode', true);
        $this->assertSame("\n<!-- TWIG TEMPLATE START: huh_foo_bar.html.twig -->\n<p>Foo bar</p>\n<!-- TWIG TEMPLATE END: huh_foo_bar.html.twig -->\n", $instance->inherit());
    }
}
