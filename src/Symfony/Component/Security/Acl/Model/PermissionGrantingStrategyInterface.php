<?php

namespace Symfony\Component\Security\Acl\Model;

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