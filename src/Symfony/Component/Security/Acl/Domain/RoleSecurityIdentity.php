<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Role\Role;

/**
 * A SecurityIdentity implementation for roles
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RoleSecurityIdentity implements SecurityIdentityInterface
{
    protected $role;

    /**
     * Constructor
     *
     * @param mixed $role a Role instance, or its string representation
     * @return void
     */
    public function __construct($role)
    {
        if ($role instanceof Role) {
            $role = $role->getRole();
        }

        $this->role = $role;
    }

    /**
     * Returns the role name
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritDoc}
     */
    public function equals(SecurityIdentityInterface $sid)
    {
        if (!$sid instanceof RoleSecurityIdentity) {
            return false;
        }

        return $this->role === $sid->getRole();
    }

    /**
     * Returns a textual representation of this security identity.
     *
     * This is solely used for debugging purposes, not to make an equality decision.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('RoleSecurityIdentity(%s)', $this->role);
    }
}