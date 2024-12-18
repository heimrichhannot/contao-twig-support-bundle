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
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\TestCase\ContaoTestCase;
use Contao\ThemeModel;
use Doctrine\DBAL\Connection;
use HeimrichHannot\TestUtilitiesBundle\Mock\ModelMockTrait;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use PHPUnit\Framework\MockObject\MockBuilder;
use Psr\Cache\CacheItemInterface;
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

        if (!isset($parameter['locator'])) {
            $parameter['locator'] = $this->createMock(TemplateLocator::class);
        }

        if ($mockBuilder) {
            return $mockBuilder->setConstructorArgs([
                $parameter['kernel'],
                $parameter['resource_finder'],
                $parameter['request_stack'],
                $parameter['scope_matcher'],
                $this->createMock(Stopwatch::class),
                $parameter['cache'],
                $contaoFramework,
                $parameter['locator'],
            ])->getMock();
        }


        return new TwigTemplateLocator(
                $parameter['kernel'],
                $parameter['resource_finder'],
                $parameter['request_stack'],
                $parameter['scope_matcher'],
                $this->createMock(Stopwatch::class),
                $parameter['cache'],
                $contaoFramework,
            $parameter['locator']
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

    public function testGetTemplatePath()
    {
        $instance = $this->createTestInstance($this->prepareTemplateLoader([]));
        $this->assertSame('@Contao_App/content_element/text.html.twig', $instance->getTemplatePath('text', ['disableCache' => true]));
        $this->assertSame('@Contao_App/content_element/text.html.twig', $instance->getTemplatePath('text.html.twig', ['disableCache' => true]));
        $this->assertSame('@Contao_App/content_element/text.html.twig', $instance->getTemplatePath('content_element/text', ['disableCache' => true]));
        $this->assertSame('@Contao_App/content_element/text.html.twig', $instance->getTemplatePath('content_element/text.html.twig', ['disableCache' => true]));
        $this->assertSame('@Contao_App/form_text.html.twig', $instance->getTemplatePath('form_text', ['disableCache' => true]));
        $this->assertSame('@Contao_App/form_text.html.twig', $instance->getTemplatePath('form_text.html.twig', ['disableCache' => true]));

        $this->assertSame('ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));
        $this->assertSame('ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));

        $parameters = $this->prepareTemplateLoader([]);
        $scopeMather = $this->createMock(ScopeMatcher::class);
        $scopeMather->method('isFrontendRequest')->willReturn(true);
        $parameters['scope_matcher'] = $scopeMather;
        $instance = $this->createTestInstance($parameters);

        $this->assertSame('ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));
        $this->assertSame('@Contao_App/ce_headline.html.twig', $instance->getTemplatePath('ce_headline', ['disableCache' => true]));
        $this->assertSame('@Contao_a/ce_html.html.twig', $instance->getTemplatePath('ce_html', ['disableCache' => true]));
        $this->assertSame('@b/elements/ce_image.html.twig', $instance->getTemplatePath('ce_image', ['disableCache' => true]));
        $this->assertSame('@b/elements/ce_image.html.twig', $instance->getTemplatePath('ce_image.html.twig', ['disableCache' => true]));

        $GLOBALS['objPage'] = (object) ['templateGroup' => 'customtheme'];
        $this->assertSame('@Contao_Theme_customtheme/ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));
        $this->assertSame('@Contao_Theme_customtheme/ce_headline.html.twig', $instance->getTemplatePath('ce_headline', ['disableCache' => true]));
        $this->assertSame('@Contao_a/ce_html.html.twig', $instance->getTemplatePath('ce_html', ['disableCache' => true]));

        $GLOBALS['objPage'] = (object) ['templateGroup' => 'anothertheme'];
        $this->assertSame('@Contao_Theme_anothertheme/ce_text.html.twig', $instance->getTemplatePath('ce_text', ['disableCache' => true]));
        $this->assertSame('@Contao_App/ce_headline.html.twig', $instance->getTemplatePath('ce_headline', ['disableCache' => true]));
        $this->assertSame('@Contao_Theme_anothertheme/ce_html.html.twig', $instance->getTemplatePath('ce_html', ['disableCache' => true]));

        unset($GLOBALS['objPage']);
    }

    public function testGetTemplates()
    {
        $mock = $this->getMockBuilder(TwigTemplateLocator::class)->onlyMethods(['generateContaoTwigTemplatePaths']);
        $parameters = $this->prepareTemplateLoader([]);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturnOnConsecutiveCalls(false, true);
        $cacheItem->method('get')->willReturn([]);

        $cache = $this->createMock(FilesystemAdapter::class);
        $cache->method('getItem')->willReturn($cacheItem);
        $parameters['cache'] = $cache;

        $instance = $this->createTestInstance($parameters, $mock);
        $instance->expects($this->once())->method('generateContaoTwigTemplatePaths')->willReturn([]);

        $instance->getTemplates();
        $instance->getTemplates();
    }

    private function prepareTemplateLoader(array $parameters): array
    {
        $projectDir = __DIR__.'/../Fixtures/TwigTemplateLocator';
        $kernel = $this->createMock(Kernel::class);
        $bundles = [];
        $bundleMetaData = [];
        $kernelBundles = [
            'a' => $projectDir . '/vendor/example/a',
            'b' => $projectDir . '/vendor/example/b/src',
        ];
        foreach ($kernelBundles as $bundle => $bundlePath) {
            $currentBundle = $this->createMock(BundleInterface::class);
            $currentBundle->method('getPath')->willReturn($bundlePath);
            $currentBundle->method('getName')->willReturn($bundle);
            $kernelBundles[$bundle] = $currentBundle;
            $bundles[$bundle] = BundleInterface::class;
            $bundleMetaData[$bundle] = ['path' => $bundlePath];
        }

        $kernel->method('getBundles')->willReturn($kernelBundles);
        $kernel->method('getProjectDir')->willReturn($projectDir);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn([
            'templates/customtheme',
            'templates/anothertheme',
        ]);

        $templateLocator = new TemplateLocator(
            $projectDir,
            $bundles,
            $bundleMetaData,
            new ThemeNamespace(),
            $connection
        );

        $parameters['kernel'] = $kernel;
        $parameters['locator'] = $templateLocator;

        return $parameters;
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


        $bundleMetaData = [];
        foreach ($kernelBundles as $bundle) {
            $bundleMetaData[$bundle->getName()] = ['path' => $bundle->getPath()];
        }
        $templateLocator = new TemplateLocator(__DIR__.'/../Fixtures/templateLocator/'.$subpath, [], [], new ThemeNamespace(), $this->createMock(Connection::class));

        return [$kernel, $resourceFinder, $templateLocator];
    }
}
