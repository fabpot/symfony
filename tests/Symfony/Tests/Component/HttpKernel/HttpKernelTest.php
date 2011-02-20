<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel;

use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;

class HttpKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testHandleWhenControllerThrowsAnExceptionAndRawIsTrue()
    {
        $kernel = new HttpKernel(new EventDispatcher(), $this->getResolver(function () { throw new \RuntimeException(); }));

        $kernel->handle(new Request(), new Response(), HttpKernelInterface::MASTER_REQUEST, true);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testHandleWhenControllerThrowsAnExceptionAndRawIsFalseAndNoListenerIsRegistered()
    {
        $kernel = new HttpKernel(new EventDispatcher(), $this->getResolver(function () { throw new \RuntimeException(); }));

        $kernel->handle(new Request(), new Response(), HttpKernelInterface::MASTER_REQUEST, false);
    }

    public function testHandleWhenControllerThrowsAnExceptionAndRawIsFalse()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.exception', function ($event)
        {
            $event->get('response')->setContent($event->get('exception')->getMessage());
            $event->setProcessed();
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { throw new \RuntimeException('foo'); }));

        $this->assertEquals('foo', $kernel->handle(new Request(), new Response())->getContent());
    }

    public function testHandleWhenAListenerReturnsAResponse()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.request', function ($event)
        {
            $event->setProcessed();
            $event->get('response')->setContent('hello');
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver());

        $this->assertEquals('hello', $kernel->handle(new Request(), new Response())->getContent());
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testHandleWhenNoControllerIsFound()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(false));

        $kernel->handle(new Request(), new Response());
    }

    /**
     * @expectedException LogicException
     */
    public function testHandleWhenNoControllerIsNotACallable()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver('foobar'));

        $kernel->handle(new Request(), new Response());
    }

    public function testHandleWhenControllerDoesNotReturnAResponseButAViewIsRegistered()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.view', function ($event)
        {
            $event->get('response')->setContent($event->get('parameters'));
            $event->setProcessed(true);
        });
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { return 'foo'; }));

        $this->assertEquals('foo', $kernel->handle(new Request(), new Response())->getContent());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testHandleWhenNoViewCanProcessActionResult()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.view', function ($event)
        {
            // we just don't handle it
        });
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { return 'foo'; }));

        $kernel->handle(new Request(), new Response());
    }

    public function testHandleWithAResponseListener()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.response', function ($event)
        {
            $event->get('response')->setContent('foo');
        });
        $kernel = new HttpKernel($dispatcher, $this->getResolver());

        $this->assertEquals('foo', $kernel->handle(new Request(), new Response())->getContent());
    }

    protected function getResolver($controller = null)
    {
        if (null === $controller) {
            $controller = function(Response $response) { $response->setContent('Hello'); };
        }

        return new TestResolver($controller);
    }
}

class TestResolver extends ControllerResolver
{
    protected $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function getController(Request $request)
    {
        return $this->controller;
    }
}
