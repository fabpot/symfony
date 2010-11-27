<?php

namespace Symfony\Component\Security\Acl\Util;

class PermissionBuilder
{
    const MASK_VIEW         = 1;      // 1 << 0
    const MASK_CREATE       = 2;      // 1 << 1
    const MASK_EDIT         = 4;      // 1 << 2
    const MASK_DELETE       = 8;      // 1 << 3
    const MASK_UNDELETE     = 16;     // 1 << 4
    const MASK_ADMINISTER   = 32;     // 1 << 5
    const MASK_OWNER        = 64;     // 1 << 6
    
    protected $mask;
    
    public function __construct()
    {
        $this->mask = 0;
    }
    
    public function add($mask)
    {
        if (is_string($mask) && defined($name = 'self::MASK_'.strtoupper($mask))) {
            $mask = constant($name);
        }
        else if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $this->mask |= $mask;
        
        return $this;
    }
    
    public function remove($mask)
    {
        if (is_string($mask) && defined($name = 'self::MASK_'.strtoupper($mask))) {
            $mask = constant($name);
        }
        else if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $this->mask &= ~$mask;
        
        return $this;
    }
    
    public function get()
    {
        return $this->mask;
    }
    
    public function reset()
    {
        $this->mask = 0;
        
        return $this;
    }
}