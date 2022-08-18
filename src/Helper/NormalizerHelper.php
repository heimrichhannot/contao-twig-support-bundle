<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TwigSupportBundle\Helper;

class NormalizerHelper
{
    /**
     * Serialize a class object to JSON by iterating over all public getters (get(), is(), ...).
     *
     * Options:
     * - includeProperties
     * - ignorePropertyVisibility
     * - ignoreMethods
     * - ignoreMethodVisibility
     * - skippedMethods
     *
     * @param       $object
     * @param array $data
     * @param array $options
     *
     * @throws \ReflectionException if the class or method does not exist
     */
    public function normalizeObject($object, $data = [], $options = []): array
    {
        $class = \get_class($object);

        $rc = new \ReflectionClass($object);

        // get values of properties
        if (isset($options['includeProperties']) && $options['includeProperties']) {
            foreach ($rc->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                $property = $rc->getProperty($propertyName);

                if (isset($options['ignorePropertyVisibility']) && $options['ignorePropertyVisibility']) {
                    $property->setAccessible(true);
                }

                $data[$propertyName] = $property->getValue($object);

                if (\is_object($data[$propertyName])) {
                    if (!($data[$propertyName] instanceof \JsonSerializable)) {
                        unset($data[$propertyName]);

                        continue;
                    }

                    $data[$propertyName] = $this->normalizeObject($data[$propertyName]);
                }
            }
        }

        if (isset($options['ignoreMethods']) && $options['ignoreMethods']) {
            return $data;
        }

        // get values of methods
        if (isset($options['ignoreMethodVisibility']) && $options['ignoreMethodVisibility']) {
            $methods = $rc->getMethods();
        } else {
            $methods = $rc->getMethods(\ReflectionMethod::IS_PUBLIC);
        }

        // add all public getter Methods
        foreach ($methods as $method) {
            // get()
            if (false !== ('get' === substr($method->name, 0, \strlen('get')))) {
                $start = 3; // highest priority
            } // is()
            elseif (false !== ('is' === substr($method->name, 0, \strlen('is')))) {
                $name = substr($method->name, 2, \strlen($method->name));
                $start = !$rc->hasMethod('has'.ucfirst($name)) && !$rc->hasMethod('get'.ucfirst($name)) ? 2 : 0;
            } elseif (false !== ('has' === substr($method->name, 0, \strlen('has')))) {
                $name = substr($method->name, 3, \strlen($method->name));
                $start = !$rc->hasMethod('is'.ucfirst($name)) && !$rc->hasMethod('get'.ucfirst($name)) ? 3 : 0;
            } else {
                continue;
            }

            // skip methods with parameters
            $rm = new \ReflectionMethod($class, $method->name);

            if ($rm->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            if (isset($options['skippedMethods']) && \is_array($options['skippedMethods']) && \in_array($method->name, $options['skippedMethods'])) {
                continue;
            }

            $property = lcfirst(substr($method->name, $start));

            if (!$method->isPublic()) {
                $method->setAccessible(true);
                $data[$property] = $method->invoke($object);
            } else {
                $data[$property] = @$object->{$method->name}();
            }

            if (\is_object($data[$property])) {
                if (!($data[$property] instanceof \JsonSerializable)) {
                    unset($data[$property]);

                    continue;
                }
                $data[$property] = $this->normalizeObject($data[$property]);
            }
        }

        return $data;
    }
}
