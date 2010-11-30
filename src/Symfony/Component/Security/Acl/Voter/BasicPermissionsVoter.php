<?php

namespace Symfony\Component\Security\Acl\Voter;

use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Util\PermissionBuilder;
use Symfony\Component\Security\Role\RoleHierarchyInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This voter provides support for the already built-in masks.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class BasicPermissionsVoter extends Voter
{
    const PERMISSION_VIEW        = 'VIEW';
    const PERMISSION_EDIT        = 'EDIT';
    const PERMISSION_CREATE      = 'CREATE';
    const PERMISSION_DELETE      = 'DELETE';
    const PERMISSION_UNDELETE    = 'UNDELETE';
    const PERMISSION_ADMINISTER  = 'ADMINISTER';
    const PERMISSION_OWNER       = 'OWNER';
    
    public function __construct(AclProviderInterface $aclProvider, RoleHierarchyInterface $roleHierarchy)
    {
        parent::__construct($aclProvider, $roleHierarchy, $this->getProcessMap());
    }
    
    protected function getProcessMap()
    {
        return array(
            self::PERMISSION_VIEW => array(
                PermissionBuilder::MASK_VIEW,
                PermissionBuilder::MASK_EDIT,
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_EDIT => array(
                PermissionBuilder::MASK_EDIT,
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_CREATE => array(
                PermissionBuilder::MASK_CREATE,
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_DELETE => array(
                PermissionBuilder::MASK_DELETE,
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_UNDELETE => array(
                PermissionBuilder::MASK_UNDELETE,
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_ADMINISTER => array(
                PermissionBuilder::MASK_ADMINISTER,
                PermissionBuilder::MASK_OWNER,
            ),
            
            self::PERMISSION_OWNER => array(
                PermissionBuilder::MASK_OWNER,
            ),
        );
    }
}