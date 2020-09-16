<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Event;

use Contao\Template;
use Contao\Widget;
use Symfony\Component\EventDispatcher\Event;

class BeforeRenderTwigTemplateEvent extends Event
{
    const NAME = 'huh.twig_support.before_render_twig_template';
    /**
     * @var string
     */
    protected $templateName;
    /**
     * @var array
     */
    protected $templateData;
    /**
     * @var Template|Widget
     */
    protected $contaoTemplate;
    /**
     * @var array
     */
    protected $templates;
    /**
     * @var string
     */
    protected $twigTemplatePath;

    /**
     * BeforeRenderTwigTemplate constructor.
     */
    public function __construct(string $templateName, array $templateData, string $twigTemplatePath, $contaoTemplate, array $templates)
    {
        $this->templateName = $templateName;
        $this->templateData = $templateData;
        $this->contaoTemplate = $contaoTemplate;
        $this->templates = $templates;
        $this->twigTemplatePath = $twigTemplatePath;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @return Template|Widget
     */
    public function getContaoTemplate()
    {
        return $this->contaoTemplate;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getTwigTemplatePath(): string
    {
        return $this->twigTemplatePath;
    }

    public function setTwigTemplatePath(string $twigTemplatePath): void
    {
        $this->twigTemplatePath = $twigTemplatePath;
    }

    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }
}
