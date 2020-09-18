<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Template;
use Contao\TemplateLoader;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use HeimrichHannot\TwigSupportBundle\Helper\NormalizerHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

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
     * @var Environment
     */
    protected $twig;
    /**
     * @var string
     */
    protected $env;
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
     * RenderListener constructor.
     */
    public function __construct(TwigTemplateLocator $templateLocator, string $rootDir, EventDispatcherInterface $eventDispatcher, Environment $twig, string $env, RequestStack $requestStack, ScopeMatcher $scopeMatcher, NormalizerHelper $normalizer, array $bundleConfig)
    {
        $this->templateLocator = $templateLocator;
        $this->rootDir = $rootDir;
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
        $this->env = $env;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->normalizer = $normalizer;
        $this->bundleConfig = $bundleConfig;

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
            foreach ($this->templates as $templateName => $templatePath) {
                TemplateLoader::addFile($templateName, $templatePath);
            }
        }
    }

    /**
     * @Hook("parseTemplate")
     */
    public function onParseTemplate(Template $contaoTemplate): void
    {
        $templateName = $contaoTemplate->getName();

        if (!$this->enableTemplateLoader || !isset($this->templates[$templateName])) {
            return;
        }

        $templateData = $contaoTemplate->getData();

        $event = $this->eventDispatcher->dispatch(
            BeforeParseTwigTemplateEvent::NAME,
            new BeforeParseTwigTemplateEvent($templateName, $templateData, $contaoTemplate, $this->templates)
        );

        $contaoTemplate->setName('twig_template_proxy');
        $contaoTemplate->setData([
            static::TWIG_TEMPLATE => $event->getTemplateName(),
            static::TWIG_CONTEXT => $event->getTemplateData(),
        ]);
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

        $event = $this->eventDispatcher->dispatch(
            BeforeParseTwigTemplateEvent::NAME,
            new BeforeParseTwigTemplateEvent($templateName, $templateData, $widget, $this->templates)
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
     */
    public function render($contaoTemplate): string
    {
        $twigTemplateName = $contaoTemplate->{static::TWIG_TEMPLATE};
        $twigTemplateData = $contaoTemplate->{static::TWIG_CONTEXT};

        $twigTemplatePath = $this->templates[$twigTemplateName];

        /** @var BeforeRenderTwigTemplateEvent $event */
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeRenderTwigTemplateEvent::NAME,
            new BeforeRenderTwigTemplateEvent($twigTemplateName, $twigTemplateData, $twigTemplatePath, $contaoTemplate, $this->templates)
        );

        if ($contaoTemplate instanceof Template) {
            $contaoTemplate->setData($event->getTemplateData());
        }

        $buffer = $this->twig->render($event->getTwigTemplatePath(), $event->getTemplateData());

        if (Config::get('debugMode')) {
            $strRelPath = $event->getTwigTemplatePath();
            $buffer = "\n<!-- TWIG TEMPLATE START: $strRelPath -->\n$buffer\n<!-- TWIG TEMPLATE END: $strRelPath -->\n";
        }

        return $buffer;
    }
}
