<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Normalizer;

use Contao\Widget;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

class ContaoWidgetNormalizer extends PropertyNormalizer
{
    public function normalize($object, $format = null, array $context = [])
    {
        if (\is_object($object) && !$object instanceof Widget) {
            return null;
        }
        $data = parent::normalize($object, $format, $context);

        $getSet = new GetSetMethodNormalizer();
        $getSet->setSerializer($this->serializer);
        $getterData = $getSet->normalize($object, $format, $context);

        $options = static::callInaccessibleMethod($object, 'getOptions');

        return array_merge($data, $getterData, $options ? ['arrOptions' => $options] : []);
    }

    public static function callInaccessibleMethod(object $entity, string $method)
    {
        $rc = new \ReflectionClass($entity);

        if ($rc->hasMethod($method)) {
            $method = $rc->getMethod($method);
            $method->setAccessible(true);

            return $method->invoke($entity);
        }

        return null;
    }
}
