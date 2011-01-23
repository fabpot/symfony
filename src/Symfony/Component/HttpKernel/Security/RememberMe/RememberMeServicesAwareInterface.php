<?php

namespace Symfony\Component\HttpKernel\Security\RememberMe;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface that needs to be implemented by listeners which provide remember-me
 * capabilities.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface RememberMeServicesAwareInterface
{
    /**
     * Sets the RememberMeServices implementation to use
     *
     * @param RememberMeServicesInterface $rememberMeServices
     */
    public function setRememberMeServices(RememberMeServicesInterface $rememberMeServices);
}
