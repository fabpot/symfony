<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HttpKernelInterface handles a Request to convert it to a Response.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface HttpKernelInterface
{
    const MASTER_REQUEST = 1;
    const SUB_REQUEST = 2;

    /**
     * Handles a Request to use it to populate a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to populate the Response instance.
     *
     * @param  Request  $request  A Request instance
     * @param  Response $response A Response instance
     * @param  integer  $type     The type of the request
     *                            (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param  Boolean  $catch    Whether to catch exceptions or not
     *
     * @return Response The response instance; if an instance was passed via the $response parameter
     *                  this method must return the same instance.
     *
     * @throws \Exception When an Exception occurs during processing
     */
    function handle(Request $request, Response $response = null, $type = self::MASTER_REQUEST, $catch = true);
}
