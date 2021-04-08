<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Template;

use Contao\FrontendTemplate;
use Contao\System;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;

class TwigFrontendTemplate extends FrontendTemplate
{
    public function inherit()
    {
        $container = System::getContainer();

        if ($container->has(RenderListener::class)) {
            if ('twig_template_proxy' !== $this->getName()) {
                $container->get(RenderListener::class)->prepareContaoTemplate($this);
            }

            return $container->get(RenderListener::class)->render($this);
        }

        return parent::inherit();
    }
}
