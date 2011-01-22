<?php

namespace Symfony\Component\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Serializer serializes and unserializes data
 *
 * objects are turned into arrays by serializers, and vice versa.
 * arrays are turned into various output formats by encoders
 *
 * $serializer->serialize($obj, 'xml', array('field','field2'))
 * $serializer->decode($data, 'xml')
 * $serializer->denormalizeObject($data, 'Class', 'xml')
 *
 * TODO:
 * - Use Validator comp to check which fields are mandatory during deserialization (?)
 *   - Alternatively we could use .NET-style @serialize:NonSerialized and @serialize:OptionalField on properties
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Serializer implements SerializerInterface
{
    protected $normalizers = array();
    protected $encoders = array();
    protected $normalizerCache = array();

    public function serialize($data, $format)
    {
        if (!is_scalar($data)) {
            $data = $this->normalize($data, $format);
        }
        return $this->encode($data, $format);
    }

    public function normalizeObject($object, $format, $properties = null)
    {
        $class = get_class($object);
        if (isset($this->normalizerCache[$class][$format])) {
            return $normalizer->serialize($object, $format, $properties);
        }
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($class, $format)) {
                $this->normalizerCache[$class][$format] = $normalizer;
                return $normalizer->serialize($object, $format, $properties);
            }
        }
        throw new \UnexpectedValueException('Could not serialize object of type '.$class);
    }

    public function denormalizeObject($data, $class, $format = null)
    {
        if (isset($this->normalizerCache[$class][$format])) {
            return $normalizer->deserialize($data, $format);
        }
        $reflClass = new \ReflectionClass($class);
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($reflClass, $format)) {
                $this->normalizerCache[$class][$format] = $normalizer;
                return $normalizer->deserialize($data, $class, $format);
            }
        }
        throw new \UnexpectedValueException('Could not deserialize object of type '.$class);
    }

    public function normalize($data, $format)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = is_scalar($val) ? $val : $this->normalize($val, $format);
            }
            return $data;
        }
        if (is_object($data)) {
            return $this->normalizeObject($data, $format);
        }
        throw new \UnexpectedValueException('An unexpected value could not be serialized: '.var_export($data, true));
    }

    public function encode($data, $format)
    {
        if (!isset($this->encoders[$format])) {
            throw new \UnexpectedValueException('Could not find an encoder for the '.$format.' format');
        }
        return $this->encoders[$format]->encode($data, $format);
    }

    public function decode($data, $format)
    {
        if (!isset($this->encoders[$format])) {
            throw new \UnexpectedValueException('Could not find a decoder for the '.$format.' format');
        }
        return $this->encoders[$format]->decode($data, $format);
    }

    public function addNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizers[] = $normalizer;
        $normalizer->setSerializer($this);
    }

    public function getNormalizers()
    {
        return $this->normalizers;
    }

    public function removeNormalizer(NormalizerInterface $normalizer)
    {
        unset($this->normalizers[array_search($normalizer, $this->normalizers, true)]);
    }

    public function addEncoder($format, EncoderInterface $encoder)
    {
        $this->encoders[$format] = $encoder;
        $encoder->setSerializer($this);
    }

    public function getEncoders()
    {
        return $this->encoders;
    }

    public function removeEncoder($format)
    {
        unset($this->encoders[$format]);
    }
}
