<?php

namespace Symfony\Component\Security\Acl\Model;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

/**
 * Interface for retrieving security identities from tokens
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SecurityIdentityRetrievalStrategyInterface
{
    /**
     * Retrieves the available security identities for the given token
     * 
     * @param TokenInterface $token
     * @return array of SecurityIdentityInterface implementations
     */
    function getSecurityIdentities(TokenInterface $token);
}