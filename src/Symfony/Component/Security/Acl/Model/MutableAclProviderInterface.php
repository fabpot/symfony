<?php

namespace Symfony\Component\Security\Acl\Model;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Provides support for creating and storing ACL instances.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface MutableAclProviderInterface extends AclProviderInterface
{
    /**
     * Creates an ACL for the passed object identity
     * 
     * @param ObjectIdentityInterface $oid
     * @return MutableAclInterface
     */
    function createAcl(ObjectIdentityInterface $oid);
    
    /**
     * Deletes the ACL for the passed object identity
     * 
     * @param ObjectIdentityInterface $oid
     * @return void
     */
    function deleteAcl(ObjectIdentityInterface $oid);
    
    /**
     * Persists any changes which were made to the passed ACL
     * 
     * @param MutableAclInterface $acl
     * @return void
     */
    function updateAcl(MutableAclInterface $acl);
}