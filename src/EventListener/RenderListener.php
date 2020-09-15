<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\TwigSupportBundle\EventListener;


use Contao\Template;
use Contao\TemplateLoader;
use Contao\Widget;
use HeimrichHannot\TwigSupportBundle\Event\BeforeParseTwigTemplateEvent;
use HeimrichHannot\TwigSupportBundle\Event\BeforeRenderTwigTemplate;
use HeimrichHannot\TwigSupportBundle\Filesystem\TemplateLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Webmozart\PathUtil\Path;

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
     * RenderListener constructor.
     */
    public function __construct(TemplateLocator $templateLocator, string $rootDir, EventDispatcherInterface $eventDispatcher, Environment $twig)
    {
        $this->templateLocator = $templateLocator;
        $this->rootDir = $rootDir;
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
    }


    /**
     * @Hook("initializeSystem")
     */
    public function onInitializeSystem(): void
    {
        $templatePaths = $this->templateLocator->getTwigTemplatePaths();

        foreach ($templatePaths as $templatePath) {
            $identifier = Path::getFilenameWithoutExtension($templatePath, '.html.twig');

            // add template to the TemplateLoader so that they show up in the backend
            $directory = Path::getDirectory($templatePath);

            TemplateLoader::addFile($identifier, Path::makeRelative($directory, $this->rootDir));

            // keep track of the relative path (inside the template path)
            $this->templates[$identifier] = Path::makeRelative($templatePath, $this->rootDir.'/templates');
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