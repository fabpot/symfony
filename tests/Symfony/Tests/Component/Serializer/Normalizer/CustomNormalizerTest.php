<?php

namespace Symfony\Tests\Component\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\Normalizable;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class CustomNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->normalizer = new CustomNormalizer;
        $this->normalizer->setSerializer($this->getMock('Symfony\Component\Serializer\Serializer'));
    }

    public function testSerialize()
    {
        $obj = new Dummy;
        $obj->foo = 'foo';
        $obj->xmlFoo = 'xml';
        $this->assertEquals('foo', $this->normalizer->normalize($obj, 'json'));
        $this->assertEquals('xml', $this->normalizer->normalize($obj, 'xml'));
    }

    public function testDeserialize()
    {
        $obj = $this->normalizer->denormalize('foo', __NAMESPACE__.'\Dummy', 'xml');
        $this->assertEquals('foo', $obj->xmlFoo);
        $this->assertNull($obj->foo);

        $obj = $this->normalizer->denormalize('foo', __NAMESPACE__.'\Dummy', 'json');
        $this->assertEquals('foo', $obj->foo);
        $this->assertNull($obj->xmlFoo);
    }

    public function testSupports()
    {
        $this->assertTrue($this->normalizer->supports(new \ReflectionClass(get_class(new Dummy))));
        $this->assertFalse($this->normalizer->supports(new \ReflectionClass('stdClass')));
    }
}

class Dummy implements Normalizable
{
    public $foo;
    public $xmlFoo;

    public function normalize(NormalizerInterface $normalizer, $format, $properties = null)
    {
        return $format === 'xml' ? $this->xmlFoo : $this->foo;
    }

    public function denormalize(NormalizerInterface $normalizer, $data, $format = null)
    {
        if ($format === 'xml') {
            $this->xmlFoo = $data;
        } else {
            $this->foo = $data;
        }
    }
}