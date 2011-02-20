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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class TestHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    public function __construct()
    {
        parent::__construct(new EventDispatcher(), $this);
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
        $response->setContent('Request: '.$request->getRequestUri());
    }
}
