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
 * An interface that defines an exception as an HTTP exception.
 *
 * By convention, exception code == response status code. If an Exception
 * is thrown that implements HttpExceptionInterface, the final response
 * will respect this convention.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface HttpExceptionInterface
{
}
