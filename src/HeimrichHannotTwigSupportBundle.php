<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle;

use HeimrichHannot\TwigSupportBundle\DependencyInjection\HeimrichHannotTwigSupportExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotTwigSupportBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new HeimrichHannotTwigSupportExtension();
    }
}
