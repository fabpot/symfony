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
 * LockedException is thrown if the user account is locked.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class LockedException extends AccountStatusException
{
}
