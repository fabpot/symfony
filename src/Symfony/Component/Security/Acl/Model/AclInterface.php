<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * This interface represents an access control list (ACL) for a domain object. 
 * Each domain object can have exactly one associated ACL.
 * 
 * An ACL contains all access control entries (ACE) for a given domain object. 
 * In order to avoid needing references to the domain object itself, implementations 
 * use ObjectIdentity implementations as an additional level of indirection.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AclInterface
{
    function getClassAces();
    function getClassFieldAces($field);
    function getObjectAces();
    function getObjectFieldAces($field);
    function getObjectIdentity();
    function getParentAcl();
    
    /**
     * Whether this ACL is inheriting ACEs from a parent ACL.
     * 
     * @return Boolean
     */
    function isEntriesInheriting();
    function isFieldGranted($field, $permissions, $securityIdentities, $administrativeMode = false);
    function isGranted($permissions, $securityIdentities, $administrativeMode = false);
    
    /**
     * Whether the ACL has loaded ACEs for all of the passed security identities
     * 
     * @param mixed $securityIdentities an implementation of SecurityIdentityInterface, or an array thereof
     * @return Boolean
     */
    function isSidLoaded($securityIdentities);
}