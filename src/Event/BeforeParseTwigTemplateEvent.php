<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Event;

use Contao\Template;
use Symfony\Component\EventDispatcher\Event;

class BeforeParseTwigTemplateEvent extends Event
{
    const NAME = 'huh.twig_support.before_parse_twig_template';
    /**
     * @var string
     */
    protected $templateName;
    /**
     * @var array
     */
    protected $templateData;
    /**
     * @var Template
     */
    protected $contaoTemplate;
    /**
     * @var array
     */
    protected $templates;

    /**
     * BeforeParseTwigTemplateEvent constructor.
     */
    public function __construct(string $templateName, array $templateData, Template $contaoTemplate, array $templates)
    {
        $this->templateName = $templateName;
        $this->templateData = $templateData;
        $this->contaoTemplate = $contaoTemplate;
        $this->templates = $templates;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    public function getContaoTemplate(): Template
    {
        return $this->contaoTemplate;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }
}
