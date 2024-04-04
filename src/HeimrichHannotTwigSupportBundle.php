<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle;

use HeimrichHannot\TwigSupportBundle\DependencyInjection\HeimrichHannotTwigSupportExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotTwigSupportBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new HeimrichHannotTwigSupportExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}