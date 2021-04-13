<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Template;
use Contao\TemplateLoader;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Exception\TemplateNotFoundException;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Helper\NormalizerHelper;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRendererConfiguration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class RenderListener
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
    /**
     * @var TwigTemplateRenderer
     */
    protected $twigTemplateRenderer;

    /**
     * RenderListener constructor.
     */
    public function __construct(TwigTemplateLocator $templateLocator, EventDispatcherInterface $eventDispatcher, RequestStack $requestStack, ScopeMatcher $scopeMatcher, NormalizerHelper $normalizer, array $bundleConfig, TwigTemplateRenderer $twigTemplateRenderer)
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
        if (!$this->enableTemplateLoader) {
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

        if (!$this->enableTemplateLoader || !isset($this->templates[$templateName])) {
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

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeParseTwigTemplateEvent::NAME,
            new BeforeParseTwigTemplateEvent($templateName, $templateData, $widget)
        );

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

        /** @var BeforeRenderTwigTemplateEvent $event */
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeRenderTwigTemplateEvent::NAME,
            new BeforeRenderTwigTemplateEvent($twigTemplateName, $twigTemplateData, $twigTemplatePath, $contaoTemplate)
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

        if (!isset($this->templates[$templateName])) {
            throw new TemplateNotFoundException("Twig template '".$templateName."' could not be found.");
        }

        $templateData = $contaoTemplate->getData();

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeParseTwigTemplateEvent::NAME,
            new BeforeParseTwigTemplateEvent($templateName, $templateData, $contaoTemplate)
        );

        $contaoTemplate->setName('twig_template_proxy');
        $contaoTemplate->setData([
            static::TWIG_TEMPLATE => $event->getTemplateName(),
            static::TWIG_CONTEXT => $event->getTemplateData(),
        ]);
    }
}
