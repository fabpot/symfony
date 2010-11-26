<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * Provides a common interface for retrieving ACLs.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AclProviderInterface
{
    function findChildren(ObjectIdentityInterface $parentOid);
    
    /**
     * Returns the ACL that belongs to the given object identity
     * 
     * @throws AclNotFoundException when there is no ACL
     * @param ObjectIdentityInterface $oid
     * @param array $sids
     * @return AclInterface
     */
    function findAcl(ObjectIdentityInterface $oid, array $sids = array());
    
    /**
     * Returns the ACLs that belong to the given object identities
     * 
     * @throws AclNotFoundException when we cannot find an ACL for all identities
     * @param array $oids an array of ObjectIdentityInterface implementations
     * @param array $sids an array of SecurityIdentityInterface implementations
     * @return \SplObjectStorage mapping the passed object identities to ACLs
     */
    function findAcls(array $oids, array $sids = array());
}