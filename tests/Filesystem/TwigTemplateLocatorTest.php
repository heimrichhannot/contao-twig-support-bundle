<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Test\Filesystem;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\TestCase\ContaoTestCase;
use Contao\ThemeModel;
use HeimrichHannot\TestUtilitiesBundle\Mock\ModelMockTrait;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TwigTemplateLocatorTest extends ContaoTestCase
{

    use ModelMockTrait;

    protected function setUp(): void
    {
        if (!defined('VERSION')) {
            if (method_exists(ContaoCoreBundle::class, 'getVersion')) {
                define('VERSION', ContaoCoreBundle::getVersion());
            } else {
                define('VERSION', '4.9');
            }
        }
    }

    public function createTestInstance(array $parameter = [], ?MockBuilder $mockBuilder = null)
    {
        if (!isset($parameter['kernel'])) {
            $parameter['kernel'] = $this->createMock(KernelInterface::class);
        }

        if (!isset($parameter['resource_finder'])) {
            $parameter['resource_finder'] = $this->createMock(ResourceFinderInterface::class);
        }

        if (!isset($parameter['scope_matcher'])) {
            $parameter['scope_matcher'] = $this->createMock(ScopeMatcher::class);
        }

        if (!isset($parameter['request_stack'])) {
            $parameter['request_stack'] = $this->createMock(RequestStack::class);
            $parameter['request_stack']->method('getCurrentRequest')->willReturn($this->createMock(Request::class));
        }

        if (!isset($parameter['cache'])) {
            $parameter['cache'] = $this->createMock(FilesystemAdapter::class);
        }

        $contaoFramework = $parameter['framework'] ?? $this->mockContaoFramework([
            ThemeModel::class => $this->mockAdapter(['findAll']),
        ]);

        if ($mockBuilder) {
            return $mockBuilder->setConstructorArgs([
                $parameter['kernel'],
                $parameter['resource_finder'],
                $parameter['request_stack'],
                $parameter['scope_matcher'],
                $this->createMock(Stopwatch::class),
                $parameter['cache'],
                $contaoFramework,
            ])->getMock();
        }

        $contaoTemplateLocator = $this->createMock(TemplateLocator::class);

        return new TwigTemplateLocator(
                $parameter['kernel'],
                $parameter['resource_finder'],
                $parameter['request_stack'],
                $parameter['scope_matcher'],
                $this->createMock(Stopwatch::class),
                $parameter['cache'],
                $contaoFramework,
            $contaoTemplateLocator
            );
    }

    public function testGetTemplateGroup()
    {
        $GLOBALS['TL_LANG']['MSC']['global'] = 'global';

        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance([], $mock);
        $instance->method('getTemplates')->willReturn([]);
        $this->assertEmpty($instance->getTemplateGroup('prefix_'));

        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance([], $mock);
        $instance->method('getTemplates')->willReturn([
            'foo_bar' => ['paths' => ['@Acme/foo_bar.html.twig']],
        ]);
        $this->assertEmpty($instance->getTemplateGroup('prefix_'));

        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance([], $mock);
        $instance->method('getTemplates')->willReturn([
            'foo_bar' => ['paths' => ['@Acme/foo_bar.html.twig']],
            'prefix_foo_bar' => ['paths' => ['@Acme/prefix_foo_bar.html.twig']],
        ]);
        $this->assertSame(['prefix_foo_bar' => 'prefix_foo_bar (@Acme)'], $instance->getTemplateGroup('prefix_'));

        $themesModelMock = $this->mockAdapter(['findAll']);
        $themesModelMock->method('findAll')->willReturn(null);
        $contaoFramework = $this->mockContaoFramework([
            ThemeModel::class => $themesModelMock,
        ]);
        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance(['framework' => $contaoFramework], $mock);
        $instance->method('getTemplates')->willReturn([
            'foo_bar' => ['paths' => ['@Acme/foo_bar.html.twig']],
            'prefix_foo_bar' => ['paths' => ['@Acme/prefix_foo_bar.html.twig', 'elements/prefix_foo_bar.html.twig']],
            'prefix_foo_bar_3' => ['paths' => ['acme/elements/prefix_foo_bar_3.html.twig']],
        ]);
        $this->assertSame([
            'prefix_foo_bar' => 'prefix_foo_bar (global, @Acme)',
            'prefix_foo_bar_3' => 'prefix_foo_bar_3 (global)',
        ], $instance->getTemplateGroup('prefix_'));

        $themesModelMock = $this->mockAdapter(['findAll']);
        $themesModelMock->method('findAll')->willReturn([
            $this->mockModelObject(ThemeModel::class, ['templates' => 'acme', 'name' => 'Default']),
            $this->mockModelObject(ThemeModel::class, ['templates' => 'foo_bar', 'name' => 'Foo Bar']),
            $this->mockModelObject(ThemeModel::class, ['templates' => '', 'name' => 'Foo Bar']),
        ]);
        $contaoFramework = $this->mockContaoFramework([
            ThemeModel::class => $themesModelMock,
        ]);
        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance(['framework' => $contaoFramework], $mock);
        $instance->method('getTemplates')->willReturn([
            'foo_bar' => ['paths' => ['@Acme/foo_bar.html.twig']],
            'hello_world' => ['paths' => ['@Acme/hello_world.html.twig']],
            'prefix_foo_bar' => ['paths' => ['@Acme/prefix_foo_bar.html.twig', 'elements/prefix_foo_bar.html.twig']],
            'prefix_foo_bar_3' => ['paths' => ['acme/elements/prefix_foo_bar_3.html.twig']],
        ]);
        $this->assertSame([
            'prefix_foo_bar' => 'prefix_foo_bar (global, @Acme)',
            'prefix_foo_bar_3' => 'prefix_foo_bar_3 (Default)',
            'hello_world' => 'hello_world (@Acme)',
        ], $instance->getTemplateGroup(['prefix_', 'hello']));

        $catchedException = false;

        try {
            $instance->getTemplateGroup(4);
        } catch (\InvalidArgumentException $e) {
            $catchedException = true;
        }
        $this->assertTrue($catchedException);

        $themesModelMock = $this->mockAdapter(['findAll']);
        $themesModelMock->method('findAll')->willThrowException(new \Exception('Some Exception'));
        $contaoFramework = $this->mockContaoFramework([
            ThemeModel::class => $themesModelMock,
        ]);
        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->setMethods(['getTemplates']);
        $instance = $this->createTestInstance(['framework' => $contaoFramework], $mock);
        $instance->method('getTemplates')->willReturn([
            'foo_bar' => ['paths' => ['@Acme/foo_bar.html.twig']],
            'prefix_foo_bar_3' => ['paths' => ['acme/elements/prefix_foo_bar_3.html.twig']],
        ]);

        $this->assertSame([
            'prefix_foo_bar_3' => 'prefix_foo_bar_3 (global)',
        ], $instance->getTemplateGroup('prefix'));
    }

    public function testGenerateContaoTwigTemplatePathsEmpty()
    {
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('getBundles')->willReturn([]);
        $kernel->method('getProjectDir')->willReturn(__DIR__.'/../Fixtures/templateLocator/empty');

        $resourceFinder = $this->getMockBuilder(ResourceFinderInterface::class)->setMethods(['find', 'findIn', 'name', 'getIterator'])->getMock();
        $resourceFinder->method('findIn')->willReturnSelf();
        $resourceFinder->method('name')->willReturnSelf();
        $resourceFinder->method('getIterator')->willReturn([]);

        $instance = $this->createTestInstance([
            'kernel' => $kernel,
            'resource_finder' => $resourceFinder,
        ]);
        $this->assertEmpty($instance->getTemplates(false, true));
    }

    public function testGenerateContaoTwigTemplatePathsBundles()
    {
        [$kernel, $resourceFinder] = $this->buildKernelAndResourceFinderForBundlesAndPath(['dolarBundle', 'ipsumBundle'], 'bundles');

        $instance = $this->createTestInstance([
            'kernel' => $kernel,
            'resource_finder' => $resourceFinder,
        ]);
        $templates = $instance->getTemplates(false, true);
        $this->assertNotEmpty($templates);
        $this->assertArrayHasKey('ce_text', $templates);

        $templates = $instance->getTemplates(true, true);
        $this->assertNotEmpty($templates);
        $this->assertArrayHasKey('ce_text.html.twig', $templates);
    }

    public function testGetTemplatePath()
    {
        [$kernel, $resourceFinder] = $this->buildKernelAndResourceFinderForBundlesAndPath(['dolarBundle', 'ipsumBundle'], 'bundles');
        $scopeMather = $this->createMock(ScopeMatcher::class);
        $scopeMather->method('isFrontendRequest')->willReturn(false);

        $instance = $this->createTestInstance([
            'kernel' => $kernel,
            'resource_finder' => $resourceFinder,
            'scope_matcher' => $scopeMather,
        ]);
        $this->assertSame('@ipsum/ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));

        [$kernel, $resourceFinder] = $this->buildKernelAndResourceFinderForBundlesAndPath(['dolarBundle', 'ipsumBundle'], 'mixed');
        $scopeMather = $this->createMock(ScopeMatcher::class);
        $scopeMather->method('isFrontendRequest')->willReturn(false);

        $instance = $this->createTestInstance([
            'kernel' => $kernel,
            'resource_finder' => $resourceFinder,
            'scope_matcher' => $scopeMather,
        ]);
        $this->assertSame('ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));
    }

    protected function buildKernelAndResourceFinderForBundlesAndPath(array $bundles, string $subpath)
    {
        $kernel = $this->createMock(Kernel::class);
        $kernelBundles = [];
        $resourcePaths = [];

        foreach ($bundles as $bundle) {
            $currentBundle = $this->createMock(BundleInterface::class);
            $bundlePath = __DIR__.'/../Fixtures/templateLocator/'.$subpath.'/'.$bundle.'/src';
            $currentBundle->method('getPath')->willReturn($bundlePath);
            $currentBundle->method('getName')->willReturn($bundle);
            $kernelBundles[$bundle] = $currentBundle;

            if (is_dir($bundlePath.'/Resources/contao')) {
                $resourcePaths[] = $bundlePath.'/Resources/contao';
            }
        }

        $kernel->method('getBundles')->willReturn($kernelBundles);
        $kernel->method('getProjectDir')->willReturn(__DIR__.'/../Fixtures/templateLocator/'.$subpath);

        $resourceFinder = new ResourceFinder($resourcePaths);

        return [$kernel, $resourceFinder];
    }
}
