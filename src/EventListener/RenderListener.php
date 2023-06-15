<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\Interop\ContextFactory as ContaoContextFactory;
use Contao\Template;
use Contao\TemplateLoader;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Exception\SkipTemplateException;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Helper\NormalizerHelper;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRendererConfiguration;
use HeimrichHannot\TwigSupportBundle\Twig\Interop\ContextFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RenderListener implements ServiceSubscriberInterface
{
    const TWIG_TEMPLATE = 'twig_template';
    const TWIG_CONTEXT = 'twig_context';

    /** @var string[] */
    protected $templates = [];
    /**
     * @var TwigTemplateLocator
     */
    protected $templateLocator;
    /**
     * @var string
     */
    protected $rootDir;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var ScopeMatcher
     */
    protected $scopeMatcher;
    /**
     * @var NormalizerHelper
     */
    protected $normalizer;
    /**
     * @var array
     */
    protected $bundleConfig;
    /**
     * @var bool
     */
    protected $enableTemplateLoader = false;

    protected TwigTemplateRenderer $twigTemplateRenderer;
    private ContainerInterface $container;

    /**
     * RenderListener constructor.
     */
    public function __construct(ContainerInterface $container, TwigTemplateLocator $templateLocator, EventDispatcherInterface $eventDispatcher, RequestStack $requestStack, ScopeMatcher $scopeMatcher, NormalizerHelper $normalizer, array $bundleConfig, TwigTemplateRenderer $twigTemplateRenderer)
    {
        $this->templateLocator = $templateLocator;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->normalizer = $normalizer;
        $this->bundleConfig = $bundleConfig;
        $this->twigTemplateRenderer = $twigTemplateRenderer;

        if (isset($bundleConfig['enable_template_loader']) && true === $bundleConfig['enable_template_loader']) {
            $this->enableTemplateLoader = true;
        }
        $this->container = $container;
    }

    /**
     * @Hook("initializeSystem")
     */
    public function onInitializeSystem(): void
    {
        if (!$this->enableTemplateLoader) {
            return;
        }

        if (!$request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        $this->templates = $this->templateLocator->getTemplates(false);

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $templates = TemplateLoader::getFiles();

            foreach ($this->templates as $templateName => $templatePath) {
                if (!\array_key_exists($templateName, $templates)) {
                    TemplateLoader::addFile($templateName, $this->templateLocator->getTemplatePath($templateName));
                }
            }
        }
    }

    /**
     * @Hook("parseTemplate")
     */
    public function onParseTemplate(Template $contaoTemplate): void
    {
        if (!$this->enableTemplateLoader || $this->isSkippedTemplate($contaoTemplate->getName())) {
            return;
        }

        try {
            $this->prepareContaoTemplate($contaoTemplate);
        } catch (TemplateNotFoundException $e) {
            return;
        }
    }

    /**
     * @Hook("parseWidget")
     */
    public function onParseWidget(string $buffer, Widget $widget): string
    {
        $templateName = $widget->template;

        if (!$this->enableTemplateLoader || !isset($this->getTemplates()[$templateName]) || $this->isSkippedTemplate($templateName)) {
            return $buffer;
        }

        $templateData = $this->normalizer->normalizeObject($widget, [],
            [
                'ignorePropertyVisibility' => true,
                'includeProperties' => true,
                'ignoreMethodVisibility' => true,
            ]);

        if (isset($templateData['options']) && !empty($templateData['options'])) {
            $templateData['arrOptions'] = $templateData['options'];
        }

        try {
            $event = $this->eventDispatcher->dispatch(
                new BeforeParseTwigTemplateEvent($templateName, $templateData, $widget),
                BeforeParseTwigTemplateEvent::NAME
            );
        } catch (SkipTemplateException $e) {
            return $buffer;
        }

        $widget->{static::TWIG_TEMPLATE} = $event->getTemplateName();
        $widget->{static::TWIG_CONTEXT} = $event->getTemplateData();

        $widget->template = 'twig_template_proxy';

        return $widget->inherit();
    }

    /**
     * Render the template.
     *
     * @param $contaoTemplate
     *
     * @throws TemplateNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render($contaoTemplate): string
    {
        $twigTemplateName = $contaoTemplate->{static::TWIG_TEMPLATE};
        $twigTemplateData = $contaoTemplate->{static::TWIG_CONTEXT};

        $twigTemplatePath = $this->templateLocator->getTemplatePath($twigTemplateName);

        if ($contaoTemplate instanceof Widget) {
            $twigTemplateData['widget'] = $contaoTemplate;
        }

        $event = $this->eventDispatcher->dispatch(
            new BeforeRenderTwigTemplateEvent($twigTemplateName, $twigTemplateData, $twigTemplatePath, $contaoTemplate),
            BeforeRenderTwigTemplateEvent::NAME
        );

        if ($contaoTemplate instanceof Template) {
            $contaoTemplate->setData($event->getTemplateData());
        }

        $rendererConfiguration = (new TwigTemplateRendererConfiguration())->setTemplatePath($event->getTwigTemplatePath());

        return $this->twigTemplateRenderer->render($twigTemplateName, $event->getTemplateData(), $rendererConfiguration);
    }

    /**
     * Prepare a contao template for twig.
     */
    public function prepareContaoTemplate(Template $contaoTemplate): void
    {
        $templateName = $contaoTemplate->getName();

        if (!isset($this->getTemplates()[$templateName])) {
            throw new TemplateNotFoundException("Twig template '".$templateName."' could not be found.");
        }

        $templateData = $contaoTemplate->getData();

        try {
            $event = $this->eventDispatcher->dispatch(
                new BeforeParseTwigTemplateEvent($templateName, $templateData, $contaoTemplate),
                BeforeParseTwigTemplateEvent::NAME
            );
        } catch (SkipTemplateException $e) {
            return;
        }

        if (class_exists(ContaoContextFactory::class) && $this->container->has(ContaoContextFactory::class)) {
            $contextFactory = $this->container->get(ContaoContextFactory::class);
        } else {
            $contextFactory = new ContextFactory();
        }

        $contaoTemplate->setData($event->getTemplateData());
        $templateData = $contextFactory->fromContaoTemplate($contaoTemplate);

        $contaoTemplate->setName('twig_template_proxy');
        $contaoTemplate->setData([
            static::TWIG_TEMPLATE => $event->getTemplateName(),
            static::TWIG_CONTEXT => $templateData,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return [
            '?Contao\CoreBundle\Twig\Interop\ContextFactory',
        ];
    }

    /**
     * Check if the template is in the skipped template list.
     */
    protected function isSkippedTemplate(string $template): bool
    {
        if (!isset($this->bundleConfig['skip_templates']) || empty($this->bundleConfig['skip_templates'])) {
            return false;
        }

        return \in_array($template, $this->bundleConfig['skip_templates']);
    }

    private function getTemplates(): array
    {
        if (!$this->templates) {
            $this->templates = $this->templateLocator->getTemplates(false);
        }

        return $this->templates;
    }
}
