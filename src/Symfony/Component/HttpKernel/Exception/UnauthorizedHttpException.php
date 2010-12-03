<?php

namespace Symfony\Component\HttpKernel\Exception;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * UnauthorizedHttpException.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class UnauthorizedHttpException extends \Exception implements HttpExceptionInterface
{
    public function __construct($message = '', $code = 401, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'Unauthorized';
        }

        parent::__construct($message, $code, $previous);
    }
}
