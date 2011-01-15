<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel;

use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../Logger.php';

class ControllerResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetController()
    {
        $logger = new Logger();
        $resolver = new ControllerResolver($logger);

        $request = Request::create('/');
        $this->assertFalse($resolver->getController($request), '->getController() returns false when the request has no _controller attribute');
        $this->assertEquals(array('Unable to look for the controller as the "_controller" parameter is missing'), $logger->getLogs('err'));

        $request->attributes->set('_controller', 'Symfony\Tests\Component\HttpKernel\ControllerResolverTest::testGetController');
        $controller = $resolver->getController($request);
        $this->assertInstanceOf('Symfony\Tests\Component\HttpKernel\ControllerResolverTest', $controller[0], '->getController() returns a PHP callable');
        $this->assertEquals(array('Using controller "Symfony\Tests\Component\HttpKernel\ControllerResolverTest::testGetController"'), $logger->getLogs('info'));

        $request->attributes->set('_controller', $lambda = function () {});
        $controller = $resolver->getController($request);
        $this->assertSame($lambda, $controller);

        $request->attributes->set('_controller', 'foo');
        try {
            $resolver->getController($request);
            $this->fail('->getController() throws an \InvalidArgumentException if the _controller attribute is not well-formatted');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->getController() throws an \InvalidArgumentException if the _controller attribute is not well-formatted');
        }

        $request->attributes->set('_controller', 'foo::bar');
        try {
            $resolver->getController($request);
            $this->fail('->getController() throws an \InvalidArgumentException if the _controller attribute contains a non-existent class');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->getController() throws an \InvalidArgumentException if the _controller attribute contains a non-existent class');
        }

        $request->attributes->set('_controller', 'Symfony\Tests\Component\HttpKernel\ControllerResolverTest::bar');
        try {
            $resolver->getController($request);
            $this->fail('->getController() throws an \InvalidArgumentException if the _controller attribute contains a non-existent method');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->getController() throws an \InvalidArgumentException if the _controller attribute contains a non-existent method');
        }
    }

    public function testGetArguments()
    {
        $resolver = new ControllerResolver();

        $request = Request::create('/');
        $controller = array(new self(), 'testGetArguments');
        $this->assertEquals(array(), $resolver->getArguments($request, $controller), '->getArguments() returns an empty array if the method takes no arguments');

        $request = Request::create('/');
        $request->attributes->set('foo', 'foo');
        $controller = array(new self(), 'controllerMethod1');
        $this->assertEquals(array('foo'), $resolver->getArguments($request, $controller), '->getArguments() returns an array of arguments for the controller method');

        $request = Request::create('/');
        $request->attributes->set('foo', 'foo');
        $controller = array(new self(), 'controllerMethod2');
        $this->assertEquals(array('foo', null), $resolver->getArguments($request, $controller), '->getArguments() uses default values if present');

        $request->attributes->set('bar', 'bar');
        $this->assertEquals(array('foo', 'bar'), $resolver->getArguments($request, $controller), '->getArguments() overrides default values if provided in the request attributes');

        $request = Request::create('/');
        $request->attributes->set('foo', 'foo');
        $controller = function ($foo) {};
        $this->assertEquals(array('foo'), $resolver->getArguments($request, $controller));

        $request = Request::create('/');
        $request->attributes->set('foo', 'foo');
        $request->attributes->set('foobar', 'foobar');
        $controller = array(new self(), 'controllerMethod3');

        try {
            $resolver->getArguments($request, $controller);
            $this->fail('->getArguments() throws a \RuntimeException exception if it cannot determine the argument value');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\RuntimeException', $e, '->getArguments() throws a \RuntimeException exception if it cannot determine the argument value');
        }
    }

    protected function controllerMethod1($foo)
    {
    }

    protected function controllerMethod2($foo, $bar = null)
    {
    }

    protected function controllerMethod3($foo, $bar = null, $foobar)
    {
    }
}
