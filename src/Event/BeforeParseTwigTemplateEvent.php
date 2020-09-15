<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
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

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @return Template
     */
    public function getContaoTemplate(): Template
    {
        return $this->contaoTemplate;
    }

    /**
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @param string $templateName
     */
    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * @param array $templateData
     */
    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }
}