<?php

namespace Symfony\Component\Serializer\Normalizer;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @author Nils Adermann <naderman@naderman.de>
 */
class GetSetMethodNormalizer implements NormalizerInterface
{
    protected $serializer;

    public function normalize($object, $format, $properties = null)
    {
        $reflectionObject = new \ReflectionObject($object);
        $reflectionMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        $attributes = array();
        foreach ($reflectionMethods as $method) {
            if ($this->isGetMethod($method)) {
                $attributeName = strtolower(substr($method->getName(), 3));
                $attributeValue = $method->invoke($object);

                if (!is_scalar($attributeValue)) {
                    $attributeValue = $this->serializer->normalize($attributeValue, $format);
                }

                $attributes[$attributeName] = $attributeValue;
            }
        }

        return $attributes;
    }

    public function denormalize($data, $class, $format = null)
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $attributeNames = array_keys($data);
            $params = array();
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = strtolower($constructorParameter->getName());

                if (isset($data[$paramName])) {
                    $params[] = $data[$paramName];
                    // don't run set for a parameter passed to the constructor
                    unset($data[$paramName]);
                } else if (!$constructorParameter->isOptional()) {
                    throw new \RuntimeException(
                        'Cannot create an instance of ' . $class .
                        ' from serialized data because its constructor requires ' .
                        'parameter "' . $constructorParameter->getName() .
                        '" to be present.');
                }
            }

            $object = $reflectionClass->newInstanceArgs($params);
        } else {
            $object = new $class;
        }

        foreach ($data as $attribute => $value) {
            // call unserialize on $value?
            // ignore non existant setters?
            $object->{'set' . $attribute}($value);
        }

        return $object;
    }

    /**
     * Checks if the given class has any getter method.
     */
    public function supports(\ReflectionClass $class, $format = null)
    {
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($this->isGetMethod($method)) {
                return true;
            }
        }

        return false;
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Checks if a method's name is get.* and can be called without parameters.
     *
     * @param  ReflectionMethod $method The method to check
     * @return bool                     Whether the method is a getter.
     */
    protected function isGetMethod(\ReflectionMethod $method)
    {
        return (
            0 === strpos($method->getName(), 'get') &&
            3 < strlen($method->getName()) &&
            0 === $method->getNumberOfRequiredParameters()
        );
    }
}