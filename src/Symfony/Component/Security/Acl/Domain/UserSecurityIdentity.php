<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Authentication\Token\TokenInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * A SecurityIdentity implementation used for actual users
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class UserSecurityIdentity implements SecurityIdentityInterface
{
    protected $username;
    
    /**
     * Constructor
     * 
     * @param string $username the username representation
     * @return void
     */
    public function __construct($username)
    {
        if (0 === strlen($username)) {
            throw new \InvalidArgumentException('$username must not be empty.');
        }
        
        $this->username = $username;
    }
    
    /**
     * Constructs a UserSecurityIdentity from a authentication token
     * 
     * @param TokenInterface $token
     * @return UserSecurityIdentity
     */
    public static function fromToken(TokenInterface $token)
    {
        return new self((string) $token);
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
     * {@inheritDoc}
     */
    public function equals(SecurityIdentityInterface $sid)
    {
        if (!$sid instanceof UserSecurityIdentity) {
            return false;
        }
        
        return $this->username === $sid->getUsername();
    }
    
    /**
     * A textual representation of this security identity.
     * 
     * This is not used for equality comparison, but only for debugging.
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf('UserSecurityIdentity(%s)', $this->username);
    }
}