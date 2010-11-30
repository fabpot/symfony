<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\PermissionInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Auditable ACE implementation
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Entry implements AuditableEntryInterface
{
    protected $acl;
    protected $mask;
    protected $id;
    protected $securityIdentity;
    protected $strategy;
    protected $auditFailure;
    protected $auditSuccess;
    protected $granting;
    
    /**
     * Constructor
     * 
     * @param integer $id
     * @param AclInterface $acl
     * @param SecurityIdentityInterface $sid
     * @param string $strategy
     * @param integer $mask
     * @param Boolean $granting
     * @param Boolean $auditFailure
     * @param Boolean $auditSuccess
     */
    public function __construct($id, AclInterface $acl, SecurityIdentityInterface $sid, $strategy, $mask, $granting, $auditFailure, $auditSuccess)
    {
        $this->id = $id;
        $this->acl = $acl;
        $this->securityIdentity = $sid;
        $this->strategy = $strategy;
        $this->mask = $mask;
        $this->granting = $granting;
        $this->auditFailure = $auditFailure;
        $this->auditSuccess = $auditSuccess;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getAcl()
    {
        return $this->acl;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getMask()
    {
        return $this->mask;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
    
    /**
     * {@inheritDoc}
     */
    public function isAuditFailure()
    {
        return $this->auditFailure;
    }
    
    /**
     * {@inheritDoc}
     */
    public function isAuditSuccess()
    {
        return $this->auditSuccess;
    }
    
    /**
     * {@inheritDoc}
     */
    public function isGranting()
    {
        return $this->granting;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setAuditFailure($boolean)
    {
        $this->auditFailure = $boolean;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setAuditSuccess($boolean)
    {
        $this->auditSuccess = $boolean;
    }
    
    /**
     * Sets the permission mask
     * 
     * @param integer $mask
     * @return void
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
    }
    
    /**
     * Sets the mask comparison strategy
     * 
     * @param string $strategy
     * @return void
     */
    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }
    
    /**
     * Implementation of \Serializable
     * 
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->mask,
            $this->id,
            $this->securityIdentity,
            $this->strategy,
            $this->auditFailure,
            $this->auditSuccess,
            $this->granting,
        ));
    }
    
    /**
     * Implementation of \Serializable
     * 
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        list($this->mask,
             $this->id,
             $this->securityIdentity,
             $this->strategy,
             $this->auditFailure,
             $this->auditSuccess,
             $this->granting
        ) = unserialize($serialized);
    }
}