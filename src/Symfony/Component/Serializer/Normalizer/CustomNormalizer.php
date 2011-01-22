<?php

namespace Symfony\Component\Serializer\Normalizer;

use Symfony\Component\Serializer\SerializerInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class CustomNormalizer implements NormalizerInterface
{
    protected $manager;

    public function normalize($object, $format, $properties = null)
    {
        return $object->normalize($this, $format, $properties);
    }

    public function denormalize($data, $class, $format = null)
    {
        $object = new $class;
        $object->denormalize($this, $data, $format);
        return $object;
    }

    public function supports(\ReflectionClass $class, $format = null)
    {
        return $class->implementsInterface('Symfony\Component\Serializer\Normalizer\Normalizable');
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function getSerializer()
    {
        return $this->serializer;
    }
}
