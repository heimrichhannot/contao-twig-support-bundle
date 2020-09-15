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
use Contao\Widget;
use Symfony\Component\EventDispatcher\Event;

class BeforeRenderTwigTemplate extends Event
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
     * @return Template|Widget
     */
    public function getContaoTemplate()
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
     * @return string
     */
    public function getTwigTemplatePath(): string
    {
        return $this->twigTemplatePath;
    }

    /**
     * @param string $twigTemplatePath
     */
    public function setTwigTemplatePath(string $twigTemplatePath): void
    {
        $this->twigTemplatePath = $twigTemplatePath;
    }

    /**
     * @param array $templateData
     */
    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }


}