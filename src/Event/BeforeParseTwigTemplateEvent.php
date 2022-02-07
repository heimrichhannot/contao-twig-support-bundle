<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Event;

use Contao\Template;
use Contao\Widget;
use Symfony\Contracts\EventDispatcher\Event;

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
     * @var Template|Widget
     */
    protected $contaoTemplate;

    /**
     * BeforeParseTwigTemplateEvent constructor.
     */
    public function __construct(string $templateName, array $templateData, $contaoTemplate)
    {
        $this->templateName = $templateName;
        $this->templateData = $templateData;
        $this->contaoTemplate = $contaoTemplate;
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

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }
}
