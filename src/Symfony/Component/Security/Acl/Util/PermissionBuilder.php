<?php

namespace Symfony\Component\Security\Acl\Util;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This class allows you to build cumulative permissions easily, or convert
 * masks to a human-readable format.
 * 
 * <code>
 *   	$builder = new PermissionBuilder();
 *   	$builder
 *   		->add('view')
 *   		->add('create')
 *   		->add('edit')
 *   	;
 *   	var_dump($builder->getMask()); // int(7)
 *   	var_dump($builder->getPattern()); // string(32) ".............................ECV"
 * </code>
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PermissionBuilder
{
    const MASK_VIEW         = 1;      // 1 << 0
    const MASK_CREATE       = 2;      // 1 << 1
    const MASK_EDIT         = 4;      // 1 << 2
    const MASK_DELETE       = 8;      // 1 << 3
    const MASK_UNDELETE     = 16;     // 1 << 4
    const MASK_ADMINISTER   = 32;     // 1 << 5
    const MASK_OWNER        = 64;     // 1 << 6
    
    const CODE_VIEW         = 'V';
    const CODE_CREATE       = 'C';
    const CODE_EDIT         = 'E';
    const CODE_DELETE       = 'D';
    const CODE_UNDELETE     = 'U';
    const CODE_ADMINISTER   = 'A';
    const CODE_OWNER        = 'O';
    
    const ALL_OFF           = '................................';
    const OFF               = '.';
    const ON                = '*';
    
    protected $mask;
    
    /**
     * Constructor
     * 
     * @param integer $mask optional
     * @return void
     */
    public function __construct($mask = 0)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $this->mask = $mask;
    }
    
    /**
     * Adds a mask to the permission
     * 
     * @param integer $mask
     * @return PermissionBuilder
     */
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
    
    /**
     * Removes a mask from the permission
     * 
     * @param integer $mask
     * @return PermissionBuilder
     */
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
    
    /**
     * Returns a human-readable representation of the permission
     * 
     * @return string
     */
    public function getPattern()
    {
        $pattern = self::ALL_OFF;
        $length = strlen($pattern);
        $bitmask = str_pad(decbin($this->mask), $length, '0', STR_PAD_LEFT);
        
        for ($i=$length-1; $i>=0; $i--) {
            if ('1' === $bitmask[$i]) {
                try {
                    $pattern[$i] = self::getCode(1 << ($length - $i - 1));
                }
                catch (\Exception $notPredefined) {
                    $pattern[$i] = self::ON;
                }
            }
        }
        
        return $pattern;
    }
    
    /**
     * Returns the mask of this permission
     * 
     * @return integer
     */
    public function getMask()
    {
        return $this->mask;
    }
    
    /**
     * Resets the PermissionBuilder
     * 
     * @return PermissionBuilder
     */
    public function reset()
    {
        $this->mask = 0;
        
        return $this;
    }
    
    /**
     * Returns the code for the passed mask
     * 
     * @param integer $mask
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return string
     */
    public static function getCode($mask)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $reflection = new \ReflectionClass(get_called_class());
        foreach ($reflection->getConstants() as $name => $cMask) {
            if (false === strpos($name, 'MASK_')) {
                continue;
            }
            
            if ($mask === $cMask) {
                if (!defined($cName = 'self::CODE_'.substr($name, 5))) {
                    throw new \RuntimeException('There was no code defined for this mask.');
                }
                
                return constant($cName);
            }
        }
        
        throw new \InvalidArgumentException(sprintf('The mask "%d" is not supported.', $mask));
    }
}