<?php

namespace Symfony\Bundle\FrameworkBundle\Tests;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\FrameworkBundle\HttpKernel;

class HttpKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getProviderTypes
     */
    public function testHandle($type)
    {
        $request = new Request();
        $response = new Response();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->once())
            ->method('enterScope')
            ->with($this->equalTo('request'))
        ;
        $container
            ->expects($this->once())
            ->method('leaveScope')
            ->with($this->equalTo('request'))
        ;

        $dispatcher = new EventDispatcher();
        $resolver = $this->getMock('Symfony\\Component\\HttpKernel\\Controller\\ControllerResolverInterface');
        $kernel = new HttpKernel($container, $resolver);
        $kernel->setEventDispatcher($dispatcher);

        $resolver->expects($this->once())
            ->method('getController')
            ->will($this->returnValue(function() { }));
        $resolver->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(array()));

        $this->assertSame($response, $kernel->handle($request, $response, $type), '->handle() returns the response');
    }

    /**
     * @dataProvider getProviderTypes
     */
    public function testHandleRestoresThePreviousRequestOnException($type)
    {
        $request = new Request();
        $expected = new \Exception();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->once())
            ->method('enterScope')
            ->with($this->equalTo('request'))
        ;
        $container
            ->expects($this->once())
            ->method('leaveScope')
            ->with($this->equalTo('request'))
        ;

        $response = new Response();
        $dispatcher = new EventDispatcher();
        $resolver = $this->getMock('Symfony\\Component\\HttpKernel\\Controller\\ControllerResolverInterface');
        $kernel = new HttpKernel($container, $resolver);
        $kernel->setEventDispatcher($dispatcher);

        $controller = function() use($expected) {
            throw $expected;
        };

        $resolver->expects($this->once())
            ->method('getController')
            ->will($this->returnValue($controller));
        $resolver->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(array()));

        try {
            $kernel->handle($request, $response, $type);
            $this->fail('->handle() suppresses the controller exception');
        } catch (\Exception $actual) {
            if ($actual instanceof \PHPUnit_Framework_AssertionFailedError) {
                throw $actual;
            }

            $this->assertSame($expected, $actual, '->handle() throws the controller exception');
        }
    }

    public function getProviderTypes()
    {
        return array(
            array(HttpKernelInterface::MASTER_REQUEST),
            array(HttpKernelInterface::SUB_REQUEST),
        );
    }
}