<?php
namespace Symfony\Component\Security\Authentication\RememberMe;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface to be implemented by persistent token classes (such as
 * Doctrine entities representing a remember-me token)
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface PersistentTokenInterface
{
    function getUsername();
    
    function getSeries();
    
    function getTokenValue();
    
    function getLastUsed();
}