<?php

namespace Symfony\Tests\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\GenericTagConversionPass;

class GenericTagConversionPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('a')
            ->addTag('foo', $a = array('a' => 1, 'b' => 'bar', 'c' => 'moo'))
        ;
        $container
            ->register('b')
            ->addTag('foo')
            ->addTag('bar')
        ;

        $this->assertFalse($container->hasParameter('foo'));
        $this->assertFalse($container->hasParameter('bar'));

        $pass = new GenericTagConversionPass('foo', 'bar');
        $pass->process($container);

        $this->assertFalse($container->hasParameter('foo'));
        $this->assertTrue($container->hasParameter('bar'));
        $this->assertSame(array(
            'a' => array($a),
            'b' => array(array()),
        ), $container->getParameter('bar'));
    }
}