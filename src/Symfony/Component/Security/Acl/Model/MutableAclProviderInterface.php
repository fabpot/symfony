<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * Provides support for creating and storing ACL instances.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface MutableAclProviderInterface extends AclProviderInterface
{
    function createAcl(ObjectIdentityInterface $oid);
    function deleteAcl(ObjectIdentityInterface $oid);
    function updateAcl(MutableAclInterface $acl);
}