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
 * This class is only used by PersistentTokenRememberMeServices internally.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PersistentToken implements PersistentTokenInterface
{
    private $username;
    private $series;
    private $tokenValue;
    private $lastUsed;
    
    /**
     * Constructor
     * 
     * @param string $username
     * @param string $series
     * @param string $tokenValue
     * @param DateTime $lastUsed
     */
    public function __construct($username, $series, $tokenValue, \DateTime $lastUsed)
    {
        $this->username = $username;
        $this->series = $series;
        $this->tokenValue = $tokenValue;
        $this->lastUsed = $lastUsed;
    }
    
    /**
     * Returns the username
     * 
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Returns the series
     * 
     * @return string
     */
    public function getSeries()
    {
        return $this->series;
    }
    
    /**
     * Returns the token value
     * 
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }
    
    /**
     * Returns the time the token was last used
     * 
     * @return DateTime
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }    
}