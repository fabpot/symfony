<?php

namespace Symfony\Component\Security\Acl\Voter;

/**
 * This class is a lightweight wrapper around field vote requests which does
 * not violate any interface contracts.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FieldVote
{
    protected $domainObject;
    protected $field;
    
    public function __construct($domainObject, $field)
    {
        $this->domainObject = $domainObject;
        $this->field = $field;
    }
    
    public function getDomainObject()
    {
        return $this->domainObject;
    }
    
    public function getField()
    {
        return $this->field;
    }
}