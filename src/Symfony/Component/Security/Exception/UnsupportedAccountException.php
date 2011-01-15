<?php

namespace Symfony\Component\Security\Exception;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This exception is thrown when an account is reloaded from a provider which
 * doesn't support the passed implementation of AccountInterface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class UnsupportedAccountException extends AuthenticationServiceException
{
}