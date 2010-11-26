<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class FieldEntry extends Entry
{
    protected $field;
    
    public function __construct($id, AclInterface $acl, $field, SecurityIdentityInterface $sid, $strategy, $mask, $granting, $auditFailure, $auditSuccess)
    {
        parent::__construct($id, $acl, $sid, $strategy, $mask, $granting, $auditFailure, $auditSuccess);
        
        $this->field = $field;
    }
    
    public function getField()
    {
        return $this->field;
    }
}