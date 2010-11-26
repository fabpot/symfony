<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Role\Role;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class RoleSecurityIdentity implements SecurityIdentityInterface
{
    protected $role;
    
    public function __construct($role)
    {
        if ($role instanceof Role) {
            $role = $role->getRole();
        }
        
        $this->role = $role;
    }
    
    public function getRole()
    {
        return $this->role;
    }
    
    public function equals(SecurityIdentityInterface $sid)
    {
        if (!$sid instanceof RoleSecurityIdentity) {
            return false;
        }
        
        return $this->role === $sid->getRole();
    }
    
    public function __toString()
    {
        return sprintf('RoleSecurityIdentity(%s)', $this->role);
    }
}