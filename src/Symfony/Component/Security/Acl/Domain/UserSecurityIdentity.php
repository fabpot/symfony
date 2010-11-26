<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class UserSecurityIdentity implements SecurityIdentityInterface
{
    protected $username;
    
    /**
     * Constructor
     * 
     * @param mixed $mixed Either a textual representation of the user, or an authentication token
     */
    public function __construct($mixed)
    {
        if ($mixed instanceof TokenInterface) {
            $username = (string) $mixed;
        }
        else if (is_string($mixed)) {
            $username = $mixed;
        }
        else {
            throw new \InvalidArgumentException('$mixed must either a string, or an implementation of TokenInterface.');
        }
        
        if (0 === strlen($username)) {
            throw new \InvalidArgumentException('$username must not be empty.');
        }
        
        $this->username = $username;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function equals(SecurityIdentityInterface $sid)
    {
        if (!$sid instanceof UserSecurityIdentity) {
            return false;
        }
        
        return $this->username === $sid->getUsername();
    }
    
    public function __toString()
    {
        return sprintf('UserSecurityIdentity(%s)', $this->username);
    }
}