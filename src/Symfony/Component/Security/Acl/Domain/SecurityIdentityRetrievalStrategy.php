<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Authorization\Voter\AuthenticatedVoter;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Strategy for retrieving security identities
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecurityIdentityRetrievalStrategy implements SecurityIdentityRetrievalStrategyInterface
{
    protected $roleHierarchy;
    protected $authenticationTrustResolver;
    
    /**
     * Constructor
     * 
     * @param RoleHierarchyInterface $roleHierarchy
     * @param AuthenticationTrustResolver $authenticationTrustResolver
     * @return void
     */
    public function __construct(RoleHierarchyInterface $roleHierarchy, AuthenticationTrustResolver $authenticationTrustResolver)
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = array();
        $sids[] = UserSecurityIdentity::fromToken($token);
        
        // add all reachable roles
        foreach ($this->roleHierarchy->getReachableRoles($token->getRoles()) as $role) {
            $sids[] = new RoleSecurityIdentity($role);
        }
        
        // add built-in special roles
        if ($this->authenticationTrustResolver->isFully($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_FULLY);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        }        
        else if ($this->authenticationTrustResolver->isRememberMe($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        }
        else if ($this->authenticationTrustResolver->isAnonymous($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        }
        
        return $sids;        
    }
}