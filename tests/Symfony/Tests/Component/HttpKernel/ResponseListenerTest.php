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

use Symfony\Component\HttpKernel\ResponseListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterDoesNothingForSubRequests()
    {
        $response = new Response('foo');
        $event = new Event(null, 'core.response', array('request_type' => HttpKernelInterface::SUB_REQUEST, 'response' => $response));
        $this->getDispatcher()->notifyUntil($event);

        $this->assertEquals('', $response->headers->get('content-type'));
    }

    public function testFilterDoesNothingIfContentTypeIsSet()
    {
        $response = new Response('foo');
        $response->headers->set('Content-Type', 'text/plain');

        $event = new Event(null, 'core.response', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'response' => $response));
        $this->getDispatcher()->notifyUntil($event);

        $this->assertEquals('text/plain', $response->headers->get('content-type'));
    }

    public function testFilterDoesNothingIfRequestFormatIsNotDefined()
    {
        $response = new Response('foo');
        $event = new Event(null, 'core.response', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'request' => Request::create('/'), 'response' => $response));
        $this->getDispatcher()->notifyUntil($event);

        $this->assertEquals('', $response->headers->get('content-type'));
    }

    public function testFilterSetContentType()
    {
        $request = Request::create('/');
        $request->setRequestFormat('json');
        $response = new Response('foo');
        $event = new Event(null, 'core.response', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'request' => $request, 'response' => $response));
        $this->getDispatcher()->notifyUntil($event);

        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    protected function getDispatcher()
    {
        $dispatcher = new EventDispatcher();
        $listener = new ResponseListener('UTF-8');
        $dispatcher->connect('core.response', array($listener, 'filter'));

        return $dispatcher;
    }
}
