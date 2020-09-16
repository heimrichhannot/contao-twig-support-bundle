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
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
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
    public function __construct(TwigTemplateLocator $templateLocator, string $rootDir, EventDispatcherInterface $eventDispatcher, Environment $twig, string $env)
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
        $this->templates = $this->templateLocator->getTemplates(false);

        foreach ($this->templates as $templateName => $templatePath) {
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

        $serializer = new Serializer([
            new PropertyNormalizer(null, null, null, null, null, [
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'objContainer',
                    'arrStaticObjects',
                    'arrSingletons',
                    'arrObjects',
                ],
            ]),
        ]);

        $templateData = $serializer->normalize($widget);

        $event = $this->eventDispatcher->dispatch(
            BeforeParseTwigTemplateEvent::NAME,
            new BeforeParseTwigTemplateEvent($templateName, $templateData, $widget, $this->templates)
        );

        $widget->{static::TWIG_TEMPLATE} = $templateName;
        $widget->{static::TWIG_CONTEXT} = $templateData;

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
        $twigTemplateContext = $contaoTemplate->{static::TWIG_CONTEXT};

        $twigTemplatePath = $this->templates[$twigTemplateName];

        /** @var BeforeRenderTwigTemplateEvent $event */
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $event = $this->eventDispatcher->dispatch(
            BeforeRenderTwigTemplateEvent::NAME,
            new BeforeRenderTwigTemplateEvent($twigTemplateName, $twigTemplateContext, $twigTemplatePath, $contaoTemplate, $this->templates)
        );

        if ($contaoTemplate instanceof Template) {
            $contaoTemplate->setData($contaoTemplate->getData());
        }

        return $this->twig->render($event->getTwigTemplatePath(), $event->getTemplateData());
    }
}
