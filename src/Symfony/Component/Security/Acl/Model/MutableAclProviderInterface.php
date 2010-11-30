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
    function createAcl(ObjectIdentityInterface $oid);
    function deleteAcl(ObjectIdentityInterface $oid);
    function updateAcl(MutableAclInterface $acl);
}