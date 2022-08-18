<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Template;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;
use HeimrichHannot\TwigSupportBundle\Template\TwigFrontendTemplate;

class TwigFrontendTemplateTest extends ContaoTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testInherit()
    {
        $instance = $this->getMockBuilder(TwigFrontendTemplate::class)->disableOriginalConstructor()->setMethods(null)->getMock();
        $container = $this->getContainerWithContaoConfiguration();
        System::setContainer($container);
        $this->assertEmpty($instance->inherit());

        $renderListener = $this->createMock(RenderListener::class);
        $container->set(RenderListener::class, $renderListener);
        System::setContainer($container);
        $this->assertEmpty($instance->inherit());

        $instance->setName('huh_foo_bar');
        $this->assertEmpty($instance->inherit());

        $renderListener->method('render')->willReturn('<p>Foo bar</p>');
        $this->assertSame('<p>Foo bar</p>', $instance->inherit());
    }
}
