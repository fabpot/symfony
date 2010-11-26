<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * This method can be implemented by domain objects which you want to store
 * ACLs for if they do not have a getId() method, or getId() does not return
 * a unique identifier.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface DomainObjectInterface
{
    /**
     * Returns a unique identifier for this domain object.
     * 
     * @return integer 
     */
    function getObjectIdentifier();
}