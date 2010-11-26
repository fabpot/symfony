<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * AclCache Interface
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AclCacheInterface
{
    /**
     * Removes an ACL from the cache
     * 
     * @param string $primaryKey a serialized primary key
     * @return void
     */
    function evictFromCacheById($primaryKey);
    
    /**
     * Removes an ACL from the cache
     * 
     * The ACL which is returned, must reference the passed object identity.
     * 
     * @param ObjectIdentityInterface $oid
     * @return void
     */
    function evictFromCacheByIdentity(ObjectIdentityInterface $oid);
    
    /**
     * Removes all ACLs for identities of a certain type from the cache
     * 
     * @param string $classType
     * @return void
     */
    function evictFromCacheByType($classType);
    
    function getFromCacheById($primaryKey);
    function getFromCacheByIdentity(ObjectIdentityInterface $oid);
    function putInCache(AclInterface $acl);
    function clearCache();
}