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
 * Defines the interface of serializers
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface NormalizerInterface
{
    function normalize($object, $format, $properties = null);
    function denormalize($data, $class, $format = null);
    function supports(\ReflectionClass $class, $format = null);
    function setSerializer(SerializerInterface $serializer);
    function getSerializer();
}
