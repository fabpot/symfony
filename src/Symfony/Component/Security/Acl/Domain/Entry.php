<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\PermissionInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;

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
    
    public function getAcl()
    {
        return $this->acl;
    }
    
    public function getMask()
    {
        return $this->mask;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }
    
    public function getStrategy()
    {
        return $this->strategy;
    }
    
    public function isAuditFailure()
    {
        return $this->auditFailure;
    }
    
    public function isAuditSuccess()
    {
        return $this->auditSuccess;
    }
    
    public function isGranting()
    {
        return $this->granting;
    }
    
    public function setAuditFailure($boolean)
    {
        $this->auditFailure = $boolean;
    }
    
    public function setAuditSuccess($boolean)
    {
        $this->auditSuccess = $boolean;
    }
    
    public function setMask($mask)
    {
        $this->mask = $mask;
    }
    
    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }
    
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