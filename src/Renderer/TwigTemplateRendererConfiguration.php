<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Renderer;

class TwigTemplateRendererConfiguration
{
    /** @var bool */
    protected $showTemplateComments = true;
    /** @var bool */
    protected $throwExceptionOnError = true;
    /** @var string|null */
    protected $templatePath;

    public function getShowTemplateComments(): bool
    {
        return $this->showTemplateComments;
    }

    /**
     * Set to false if you don't want template comments in dev mode.
     */
    public function setShowTemplateComments(bool $showTemplateComments): self
    {
        $this->showTemplateComments = $showTemplateComments;

        return $this;
    }

    public function getThrowExceptionOnError(): bool
    {
        return $this->throwExceptionOnError;
    }

    /**
     * Set to false if no exception should be thrown when an error occurs.
     */
    public function setThrowExceptionOnError(bool $throwExceptionOnError): self
    {
        $this->throwExceptionOnError = $throwExceptionOnError;

        return $this;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    /**
     * Set a twig template path to override the twig name property and skip the twig template locator template loading step.
     */
    public function setTemplatePath(?string $templatePath): self
    {
        $this->templatePath = $templatePath;

        return $this;
    }
}
