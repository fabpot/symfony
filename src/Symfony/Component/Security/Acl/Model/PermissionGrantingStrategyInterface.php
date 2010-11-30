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
 * Interface used by permission granting implementations.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface PermissionGrantingStrategyInterface
{
    function isGranted(AclInterface $acl, $permissions, $sids, $administrativeMode = false);
    function isFieldGranted(AclInterface $acl, $field, $permissions, $sids, $adminstrativeMode = false);
}