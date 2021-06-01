<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Filesystem;

use HeimrichHannot\TwigSupportBundle\Filesystem\TemplateContext;
use PHPUnit\Framework\TestCase;

class TemplateContextTest extends TestCase
{
    public function testTemplateContext()
    {
        $context = new TemplateContext('hello', '@Acme/hello.twig', ['bundle' => 'AcmeBundle', 'pathname' => '/var/bundle/hello.twig']);
        $this->assertSame('hello', $context->getName());
        $this->assertSame('@Acme/hello.twig', $context->getPath());
        $this->assertSame('AcmeBundle', $context->getBundle());
        $this->assertSame('/var/bundle/hello.twig', $context->getPathname());

        $context = new TemplateContext('hello', '@Acme/hello.twig', ['bundle' => null, 'pathname' => '/var/bundle/hello.twig']);
        $this->assertSame('hello', $context->getName());
        $this->assertSame('@Acme/hello.twig', $context->getPath());
        $this->assertNull($context->getBundle());
        $this->assertSame('/var/bundle/hello.twig', $context->getPathname());
    }
}
