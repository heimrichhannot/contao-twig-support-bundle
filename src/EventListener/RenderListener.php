<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\EventListener;

use Contao\Template;
use Contao\TemplateLoader;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\Cache\TemplateCache;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplate;
use HeimrichHannot\TwigSupportBundle\Filesystem\TemplateLocator;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class RenderListener
{
    const TWIG_TEMPLATE = 'twig_template';
    const TWIG_CONTEXT = 'twig_context';

    /** @var string[] */
    protected $templates = [];
    /**
     * @var TemplateLocator
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
     * RenderListener constructor.
     */
    public function __construct(TemplateLocator $templateLocator, string $rootDir, EventDispatcherInterface $eventDispatcher, Environment $twig, string $env)
    {
        $this->templateLocator = $templateLocator;
        $this->rootDir = $rootDir;
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
        $this->env = $env;
    }

    /**
     * @Hook("initializeSystem")
     */
    public function onInitializeSystem(): void
    {
        if ('dev' !== $this->env) {
            $cache = new FilesystemCache();

            if (!$cache->has(TemplateCache::TEMPLATE_CACHE_KEY)) {
                $cache->set(TemplateCache::TEMPLATE_CACHE_KEY, $this->templateLocator->getTwigTemplatePaths(false));
            }
            $templatePaths = $cache->get(TemplateCache::TEMPLATE_CACHE_KEY);
        } else {
            $templatePaths = $this->templateLocator->getTwigTemplatePaths(false);
        }
        $this->templates = $templatePaths;

        foreach ($templatePaths as $templateName => $templatePath) {
            TemplateLoader::addFile($templateName, $templatePath);
        }
    }

    /**
     * @Hook("parseTemplate")
     */
    public function onParseTemplate(Template $contaoTemplate): void
    {
        $templateName = $contaoTemplate->getName();

        if (!isset($this->templates[$templateName])) {
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

        if (!isset($this->templates[$templateName])) {
            return $buffer;
        }

        $layout = $this->getLayout();

        if ($this->isTerminationCondition($layout)) {
            return $buffer;
        }

        return $buffer;
//
//        $data = $this->classUtil->jsonSerialize(
//            $widget,
//            [],
//            [
//                'ignorePropertyVisibility' => true,
//            ]
//        );
//
//        $result = $this->applyTwigTemplate($widget->template, $data);
//
//        if (false === $result) {
//            return $buffer;
//        }
//
//        [$templateName, $templateData] = $result;
//
//        $widget->{static::TWIG_TEMPLATE} = $templateName;
//        $widget->{static::TWIG_CONTEXT} = $templateData;
//
//        $widget->template = 'twig_template_proxy';
//
//        return $widget->inherit();
    }

    /**
     * Render the template.
     *
     * @param $contaoTemplate
     */
    public function render($contaoTemplate): string
    {
//        if ($contaoTemplate instanceof Widget) {
//            $data = $this->prepareWidget($contaoTemplate);
//            $twigTemplateName = $data['arrConfiguration'][self::TWIG_TEMPLATE] ?? null;
//            $twigTemplateContext = $data ?? null;
//        } else {
        $data = $contaoTemplate->getData();
        $twigTemplateName = $data[self::TWIG_TEMPLATE] ?? null;
        $twigTemplateContext = $data[self::TWIG_CONTEXT] ?? null;
//        }

        $twigTemplatePath = $this->templates[$twigTemplateName];

        /** @var BeforeRenderTwigTemplate $event */
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeRenderTwigTemplate::NAME,
            new BeforeRenderTwigTemplate($twigTemplateName, $twigTemplateContext, $twigTemplatePath, $contaoTemplate, $this->templates)
        );

        if ($contaoTemplate instanceof Template) {
            $contaoTemplate->setData($event->getTemplateData());
        }

        return $this->twig->render($event->getTwigTemplatePath(), $event->getTemplateData());
    }
}
