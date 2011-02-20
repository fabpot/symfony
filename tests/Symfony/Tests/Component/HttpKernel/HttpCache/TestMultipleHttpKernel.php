<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel\HttpCache;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class TestMultipleHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    protected $bodies;
    protected $statuses;
    protected $headers;
    protected $catch;
    protected $call;

    public function __construct($responses)
    {
        $this->bodies   = array();
        $this->statuses = array();
        $this->headers  = array();
        $this->call     = false;

        foreach ($responses as $response) {
            $this->bodies[]   = $response['body'];
            $this->statuses[] = $response['status'];
            $this->headers[]  = $response['headers'];
        }

        parent::__construct(new EventDispatcher(), $this);
    }

    public function handle(Request $request, Response $response = null, $type = HttpKernelInterface::MASTER_REQUEST, $catch = false)
    {
        if (null === $response) {
            $response = new Response();
        }

        return parent::handle($request, $response, $type, $catch);
    }

    public function getController(Request $request)
    {
        return array($this, 'callController');
    }

    public function getArguments(Request $request, Response $response, $controller)
    {
        return array($request, $response);
    }

    public function callController(Request $request, Response $response)
    {
        $this->called = true;

        $response->setContent(array_shift($this->bodies));
        $response->setStatusCode(array_shift($this->statuses));
        $response->headers = new ResponseHeaderBag(array_shift($this->headers));
    }

    public function hasBeenCalled()
    {
        return $this->called;
    }

    public function reset()
    {
        $this->call = false;
    }
}
