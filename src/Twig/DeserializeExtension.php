<?php

namespace HeimrichHannot\TwigSupportBundle\Twig;

use Contao\StringUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DeserializeExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('deserialize', [$this, 'deserialize']),
        ];
    }

    public function deserialize($value, $forceArray = false)
    {
        return StringUtil::deserialize($value, $forceArray);
    }
}